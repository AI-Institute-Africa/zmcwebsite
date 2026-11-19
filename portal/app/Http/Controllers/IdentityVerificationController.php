<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class IdentityVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_document' => 'required|file|extensions:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('id_document');
        $path = $file->storeAs('temp_id_documents', FileType::safeHashName($file));

        try {
            $extractedText = $this->extractTextFromDocument($file, $path);

            if (empty($extractedText)) {
                throw new \Exception('No text could be extracted from the document');
            }

            $extractedData = $this->parseExtractedText($extractedText);

            if (!$extractedData['name'] || !$extractedData['id_number']) {
                return response()->json([
                    'error' => 'Could not find name or ID number in the document',
                    'extracted_text' => $extractedText
                ], 422);
            }

            $user = auth()->user();
            $verificationResult = $this->compareDocumentData($user, $extractedData);

            Storage::delete($path);

            if ($verificationResult['matched']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document verified successfully',
                    'data' => $extractedData
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $verificationResult['reason'],
                'extracted_data' => $extractedData
            ], 422);
        } catch (\Exception $e) {
            Storage::delete($path);
            return response()->json([
                'error' => 'Failed to process document: ' . $e->getMessage()
            ], 422);
        }
    }

    private function extractTextFromDocument($file, string $path): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fullPath = storage_path('app/' . $path);

        if (config('services.openai.api_key')) {
            return $this->extractWithOpenAI($fullPath, $extension);
        }

        if ($this->isTesseractInstalled()) {
            return $this->extractWithTesseract($fullPath, $extension);
        }

        if ($extension === 'pdf') {
            return $this->extractFromPdf($fullPath);
        }

        throw new \Exception('No extraction method available. Please install Tesseract or configure OpenAI.');
    }

    private function extractWithOpenAI(string $filePath, string $extension): string
    {
        $fileContent = base64_encode(file_get_contents($filePath));
        $mimeType = $extension === 'pdf' ? 'application/pdf' : 'image/' . $extension;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.openai.endpoint'), [
            'model' => config('services.openai.model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Extract all text from this document. Return only the raw text found in the document, without any additional commentary.'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$fileContent}"
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 1000
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        throw new \Exception('OpenAI extraction failed: ' . $response->body());
    }

    private function extractWithTesseract(string $filePath, string $extension): string
    {
        if ($extension === 'pdf') {
            $images = $this->convertPdfToImages($filePath);
            $text = '';
            foreach ($images as $image) {
                $text .= shell_exec("tesseract " . escapeshellarg($image) . " stdout");
                unlink($image);
            }
            return $text;
        }

        return shell_exec("tesseract " . escapeshellarg($filePath) . " stdout");
    }

    private function extractFromPdf(string $filePath): string
    {
        if (class_exists(\Spatie\PdfToText\Pdf::class)) {
            return \Spatie\PdfToText\Pdf::getText($filePath);
        }

        if (shell_exec('which pdftotext')) {
            $outputFile = $filePath . '.txt';
            shell_exec("pdftotext " . escapeshellarg($filePath) . " " . escapeshellarg($outputFile));
            $text = file_exists($outputFile) ? file_get_contents($outputFile) : '';
            @unlink($outputFile);
            return $text;
        }

        throw new \Exception('No PDF text extraction method available');
    }

    private function isTesseractInstalled(): bool
    {
        $output = shell_exec('which tesseract');
        return !empty(trim($output));
    }

    private function convertPdfToImages(string $pdfPath): array
    {
        $images = [];
        $outputPattern = storage_path('app/temp/page_%d.png');

        if (shell_exec('which gs')) {
            shell_exec("gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -sOutputFile=" . escapeshellarg($outputPattern) . " " . escapeshellarg($pdfPath));
        } elseif (shell_exec('which convert')) {
            shell_exec("convert -density 300 " . escapeshellarg($pdfPath) . " -quality 90 " . escapeshellarg($outputPattern));
        } else {
            throw new \Exception('Ghostscript or ImageMagick required for PDF processing');
        }

        for ($i = 1; $i <= 10; $i++) {
            $imgPath = storage_path("app/temp/page_{$i}.png");
            if (file_exists($imgPath)) {
                $images[] = $imgPath;
            } else {
                break;
            }
        }

        return $images;
    }

    private function parseExtractedText(string $text): array
    {
        $patterns = [
            'name' => [
                '/Name:\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/',
                '/Full Name:\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/',
                '/(?:Name|Full name|Applicant)[\s:]+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/i',
            ],
            'id_number' => [
                '/(?:ID|Identification|ID Number|National ID|Identity Number)[\s:]+([A-Z0-9\-]+)/i',
                '/\b([0-9]{10,})\b/',
                '/\b([A-Z]{1,2}[0-9]{6,})\b/i',
            ]
        ];

        $extractedData = ['name' => null, 'id_number' => null];

        foreach ($patterns['name'] as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $extractedData['name'] = trim($matches[1]);
                break;
            }
        }

        foreach ($patterns['id_number'] as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $extractedData['id_number'] = trim($matches[1]);
                break;
            }
        }

        return $extractedData;
    }

    private function compareDocumentData(User $user, array $extractedData): array
    {
        $normalizeString = function ($string) {
            return strtolower(preg_replace('/\s+/', '', trim($string)));
        };

        $normalizeId = function ($idNumber) {
            return preg_replace('/[^A-Z0-9]/i', '', strtoupper(trim($idNumber)));
        };

        $nameMatch = $normalizeString($extractedData['name']) === $normalizeString($user->name);
        $idMatch = $normalizeId($extractedData['id_number']) === $normalizeId($user->id_number ?? '');

        if ($nameMatch && $idMatch) {
            return ['matched' => true, 'reason' => null];
        } elseif (!$nameMatch && !$idMatch) {
            return ['matched' => false, 'reason' => 'Both name and ID number do not match'];
        } elseif (!$nameMatch) {
            return ['matched' => false, 'reason' => 'Name does not match'];
        }

        return ['matched' => false, 'reason' => 'ID number does not match'];
    }
}
