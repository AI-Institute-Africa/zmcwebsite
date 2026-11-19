<?php

namespace App\Mail\Transporters;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class SendPulseTransport extends AbstractTransport
{
    protected string $apiKey;

    protected Client $client;

    public function __construct(?string $apiKey, ?string $baseUrl)
    {
        if (blank($apiKey) || blank($baseUrl)) {
            throw new \InvalidArgumentException('Configure the SendPulse API key and base URL before selecting this mailer.');
        }

        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $envelope = $message->getEnvelope();

        $headers = [];

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type'];

        foreach ($email->getHeaders()->all() as $name => $header) {
            if (in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $attachments = [];

        if ($email->getAttachments()) {
            foreach ($email->getAttachments() as $attachment) {
                $attachmentHeaders = $attachment->getPreparedHeaders();
                $contentType = $attachmentHeaders->get('Content-Type')->getBody();
                $disposition = $attachmentHeaders->getHeaderBody('Content-Disposition');
                $filename = $attachmentHeaders->getHeaderParameter('Content-Disposition', 'filename');

                $content = str_replace("\r\n", '', $attachment->bodyToString());

                $item = [
                    'content_type' => $contentType,
                    'content' => base64_encode($content),
                    'filename' => $filename,
                ];

                if ($disposition === 'inline') {
                    $item['content_id'] = $attachment->hasContentId() ? $attachment->getContentId() : $filename;
                }

                $attachments[] = $item;
            }
        }

        $payload = [
            'email' => [
                'html' => base64_encode($email->getHtmlBody() ?: ''),
                'text' => $email->getTextBody() ?: '',
                'subject' => $email->getSubject() ?: '',
                'from' => [
                    'name' => $envelope->getSender()->getName() ?: '',
                    'email' => $envelope->getSender()->getAddress(),
                ],
                'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
                'attachments' => $attachments,
                'auto_plain_text' => true,
            ],
        ];

        $replyTo = $this->stringifyAddresses($email->getReplyTo());
        if (! empty($replyTo)) {
            $payload['email']['reply_to'] = $replyTo;
        }

        $cc = $this->stringifyAddresses($email->getCc());
        if (! empty($cc)) {
            $payload['email']['cc'] = $cc;
        }

        $bcc = $this->stringifyAddresses($email->getBcc());
        if (! empty($bcc)) {
            $payload['email']['bcc'] = $bcc;
        }

        if (! empty($headers)) {
            $payload['email']['headers'] = $headers;
        }

        try {
            $response = $this->client->post('/smtp/emails', [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $result = json_decode($body, true);

            if ($statusCode !== Response::HTTP_OK && $statusCode !== Response::HTTP_CREATED) {
                $message = is_array($result) ? ($result['message'] ?? 'Unknown error from SendPulse API') : 'Invalid response from SendPulse API';

                throw new TransportException(
                    sprintf('SendPulse API error: %s', $message),
                    $statusCode
                );
            }
        } catch (TransportException $e) {
            throw $e;
        } catch (GuzzleException $e) {
            throw new TransportException(
                sprintf('Request to SendPulse API failed. Reason: %s.', $e->getMessage()),
                is_int($e->getCode()) ? $e->getCode() : 0,
                $e
            );
        } catch (Exception $e) {
            throw new TransportException(
                sprintf('Request to SendPulse API failed. Reason: %s.', $e->getMessage()),
                is_int($e->getCode()) ? $e->getCode() : 0,
                $e
            );
        }

        if (isset($result['id'])) {
            $email->getHeaders()->addHeader('X-SendPulse-Email-ID', $result['id']);
        }
    }

    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), function (Address $address) use ($email) {
            return in_array($address, array_merge($email->getCc(), $email->getBcc()), true) === false;
        });
    }

    protected function stringifyAddresses(array $addresses): array
    {
        return array_map(function (Address $address) {
            return [
                'name' => $address->getName() ?: '',
                'email' => $address->getAddress(),
            ];
        }, $addresses);
    }

    public function __toString(): string
    {
        return 'sendpulse';
    }
}
