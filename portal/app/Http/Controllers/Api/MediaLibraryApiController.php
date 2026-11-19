<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteMediaItem;
use App\Support\AuditTrail;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaLibraryApiController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:image,video'],
            'search' => ['nullable', 'string', 'max:150'],
            'is_published' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = WebsiteMediaItem::query()
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(array_key_exists('is_published', $data), fn ($query) => $query->where('is_published', $data['is_published']))
            ->when($data['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('display_order')->latest('published_at')->paginate($data['per_page'] ?? 20);

        return response()->json(['status' => 'success', 'data' => $items->through(fn ($item) => $this->format($item))]);
    }

    public function store(Request $request)
    {
        $data = $this->validateItem($request);
        $item = new WebsiteMediaItem($this->attributes($data, $request));
        $item->created_by = $request->user()->id;
        $item->save();

        AuditTrail::log('website_media_create', $item, ['type' => $item->type, 'title' => $item->title]);
        return response()->json(['status' => 'success', 'data' => $this->format($item)], 201);
    }

    public function show(WebsiteMediaItem $media)
    {
        return response()->json(['status' => 'success', 'data' => $this->format($media)]);
    }

    public function update(Request $request, WebsiteMediaItem $media)
    {
        $data = $this->validateItem($request, true);
        $oldPaths = [$media->file_path, $media->thumbnail_path];
        $attributes = $this->attributes($data, $request, $media);
        $media->fill($attributes);
        $media->save();

        foreach ($oldPaths as $path) {
            if ($path && !in_array($path, [$media->file_path, $media->thumbnail_path], true)) {
                Storage::disk('public')->delete($path);
            }
        }

        AuditTrail::log('website_media_update', $media, ['type' => $media->type, 'title' => $media->title]);
        return response()->json(['status' => 'success', 'data' => $this->format($media->fresh())]);
    }

    public function publish(Request $request, WebsiteMediaItem $media)
    {
        $data = $request->validate(['publish' => ['required', 'boolean']]);
        $published = (bool) $data['publish'];
        $media->update([
            'is_published' => $published,
            'published_at' => $published ? ($media->published_at ?? now()) : null,
        ]);
        AuditTrail::log('website_media_publish', $media, ['is_published' => $published]);

        return response()->json(['status' => 'success', 'data' => $this->format($media->fresh())]);
    }

    public function destroy(WebsiteMediaItem $media)
    {
        $id = $media->id;
        foreach ([$media->file_path, $media->thumbnail_path] as $path) {
            if ($path) Storage::disk('public')->delete($path);
        }
        $media->delete();
        AuditTrail::log('website_media_delete', null, ['media_id' => $id]);

        return response()->json(['status' => 'success', 'message' => 'Media item deleted.']);
    }

    private function validateItem(Request $request, bool $partial = false): array
    {
        $rules = [
            'type' => [$partial ? 'sometimes' : 'required', 'in:image,video'],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'file' => ['nullable', 'file', 'max:102400'],
            'thumbnail' => ['nullable', 'image', 'max:10240'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ];
        $data = $request->validate($rules);
        $type = $data['type'] ?? $request->route('media')?->type;

        if (!$partial && $type === 'image' && !$request->hasFile('file')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'An image file is required.']);
        }
        if (!$partial && $type === 'video' && !$request->hasFile('file') && empty($data['external_url'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['external_url' => 'Provide a video file or a valid hosted video URL.']);
        }
        if ($partial && $type !== ($request->route('media')?->type)) {
            if ($type === 'image' && !$request->hasFile('file')) {
                throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'An image file is required when changing the media type to image.']);
            }
            if ($type === 'video' && !$request->hasFile('file') && empty($data['external_url'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'A video file or hosted video URL is required when changing the media type.']);
            }
        }
        if ($type === 'video' && !empty($data['external_url']) && !$this->isAllowedVideoUrl($data['external_url'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['external_url' => 'Use an approved HTTPS video hosting URL.']);
        }
        if ($request->hasFile('file')) {
            $allowedMimes = $type === 'image'
                ? ['jpg', 'jpeg', 'png', 'webp', 'gif']
                : ['mp4', 'webm', 'mov', 'm4v'];
            $request->validate(['file' => ['required', 'file', 'mimes:' . implode(',', $allowedMimes), 'max:102400']]);
        }

        return $data;
    }

    private function isAllowedVideoUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        $allowedHosts = array_map('strtolower', (array) config('services.media_hub.video_hosts', [
            'youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com',
        ]));

        return in_array($host, $allowedHosts, true);
    }

    private function attributes(array $data, Request $request, ?WebsiteMediaItem $existing = null): array
    {
        $type = $data['type'] ?? $existing?->type;
        $attributes = [
            'type' => $type,
            'title' => array_key_exists('title', $data) ? $data['title'] : $existing?->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $existing?->description,
            'category' => array_key_exists('category', $data) ? $data['category'] : $existing?->category,
            'external_url' => array_key_exists('external_url', $data) ? $data['external_url'] : $existing?->external_url,
            'is_published' => array_key_exists('is_published', $data) ? (bool) $data['is_published'] : ($existing?->is_published ?? false),
            'published_at' => $data['published_at'] ?? ($existing?->published_at ?? (($data['is_published'] ?? false) ? now() : null)),
            'display_order' => $data['display_order'] ?? $existing?->display_order ?? 0,
        ];

        if ($existing && $type !== $existing->type) {
            $attributes['file_path'] = null;
            $attributes['thumbnail_path'] = null;
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $attributes['file_path'] = $file->storeAs('website-media', FileType::safeHashName($file), 'public');
        }
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $attributes['thumbnail_path'] = $thumbnail->storeAs('website-media/thumbnails', FileType::safeHashName($thumbnail), 'public');
        }

        if ($type === 'image') {
            $attributes['external_url'] = null;
        }

        return $attributes;
    }

    private function format(WebsiteMediaItem $item): array
    {
        $data = $item->toArray();
        $data['file_url'] = $item->file_path ? Storage::disk('public')->url($item->file_path) : null;
        $data['thumbnail_url'] = $item->thumbnail_path ? Storage::disk('public')->url($item->thumbnail_path) : null;
        return $data;
    }
}
