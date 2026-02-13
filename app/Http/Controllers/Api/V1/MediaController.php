<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends BaseApiController
{
    /**
     * Store a newly uploaded media file.
     *
     * POST /api/v1/media
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        // Debug incoming request (temporary)
        \Log::info('MediaController.store incoming', [
            'keys' => array_keys($request->all()),
            'hasFile' => $request->hasFile('file'),
            'allFiles' => array_keys($request->allFiles()),
            'original_purpose' => $request->input('purpose'),
        ]);

        // Defensive normalization: map frontend 'other' to 'general'
        if (is_string($request->input('purpose')) && strtolower($request->input('purpose')) === 'other') {
            $request->merge(['purpose' => 'general']);
        }

        $validated = $request->validated();
        $file = $request->file('file');
        
        // Determine file type
        $mimeType = $file->getMimeType();
        $type = str_starts_with($mimeType, 'image/') ? 'image' : 'video';
        
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Determine storage path based on purpose
        $purpose = $validated['purpose'] ?? 'general';
        $storagePath = $purpose . '/' . date('Y/m');
        
        // Store file
        $path = $file->storeAs($storagePath, $filename, 'public');
        
        // Create media record
        $media = Media::create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'type' => $type,
            'size' => $file->getSize(),
            'purpose' => $purpose,
            'related_resource' => $validated['related_resource'] ?? null,
            'related_id' => $validated['related_id'] ?? null,
            'status' => 'ready',
        ]);
        
        // TODO: Dispatch job for thumbnail generation if image
        // TODO: Dispatch job for video processing if video
        
        return $this->success(
            new MediaResource($media),
            'Media uploaded successfully',
            201
        );
    }

    /**
     * Display the specified media.
     *
     * GET /api/v1/media/{media}
     */
    public function show(Media $media): JsonResponse
    {
        return $this->success(
            new MediaResource($media),
            'Media retrieved successfully'
        );
    }

    /**
     * Remove the specified media.
     *
     * DELETE /api/v1/media/{media}
     */
    public function destroy(Media $media): JsonResponse
    {
        // Check if user owns the media or is admin
        if ($media->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return $this->error(
                'You do not have permission to delete this media',
                403
            );
        }
        
        // Delete file from storage
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }
        
        // Delete thumbnail if exists
        $pathInfo = pathinfo($media->path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        if (Storage::disk('public')->exists($thumbnailPath)) {
            Storage::disk('public')->delete($thumbnailPath);
        }
        
        // Delete media record
        $media->delete();
        
        return $this->success(
            null,
            'Media deleted successfully'
        );
    }
}