<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Media::where('user_id', auth()->id());

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by purpose if provided
        if ($request->has('purpose')) {
            $query->whereHas('relatedResource', function ($q) use ($request) {
                // Since we don't have a direct purpose field in current schema,
                // we'll filter by related_resource instead
                $q->where('related_resource', $request->purpose);
            })->orWhere('related_resource', $request->purpose);
        }

        // Filter by related resource
        if ($request->has('related_resource')) {
            $query->where('related_resource', $request->related_resource);
        }

        // Filter by related_id
        if ($request->has('related_id')) {
            $query->where('related_id', $request->related_id);
        }

        // Sort by newest first
        $query->orderBy('created_at', 'desc');

        $media = $query->paginate(20);

        return MediaResource::collection($media);
    }

    public function store(StoreMediaRequest $request): JsonResource
    {
        try {
            $file = $request->file('file');
            $purpose = $request->input('purpose', 'general');
            
            // Generate unique filename
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Organize files by purpose and date
            $directory = $purpose . '/' . date('Y/m');
            
            // Store file
            $path = $file->storeAs($directory, $fileName, 'public');
            
            if (!$path) {
                return response()->json(['error' => 'Failed to store file'], 500);
            }

            // Create media record
            $media = Media::create([
                'user_id' => auth()->id(),
                'file_name' => $fileName,
                'path' => $path,
                'type' => $this->getFileType($file->getClientMimeType()),
                'status' => 'ready',
                'related_resource' => $request->input('related_resource'),
                'related_id' => $request->input('related_id'),
            ]);

            Log::info('Media uploaded successfully', [
                'media_id' => $media->id,
                'user_id' => auth()->id(),
                'filename' => $fileName
            ]);

            return new MediaResource($media);

        } catch (\Exception $e) {
            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json(['error' => 'Failed to upload file'], 500);
        }
    }

    public function show(Media $media): JsonResource
    {
        // Check if user owns the media or is admin
        if ($media->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized access to media');
        }

        return new MediaResource($media);
    }

    public function update(UpdateMediaRequest $request, Media $media): JsonResource
    {
        // Check if user owns the media or is admin
        if ($media->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized access to media');
        }

        try {
            $media->update($request->validated());

            Log::info('Media updated successfully', [
                'media_id' => $media->id,
                'user_id' => auth()->id(),
                'updated_fields' => array_keys($request->validated())
            ]);

            return new MediaResource($media->fresh());

        } catch (\Exception $e) {
            Log::error('Media update failed', [
                'error' => $e->getMessage(),
                'media_id' => $media->id,
                'user_id' => auth()->id()
            ]);

            return response()->json(['error' => 'Failed to update media'], 500);
        }
    }

    public function destroy(Media $media): JsonResponse
    {
        // Check if user owns the media or is admin
        if ($media->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized access to media');
        }

        try {
            // Delete file from storage
            if ($media->path && Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }

            if ($media->thumbnail_url && Storage::disk('public')->exists($media->thumbnail_url)) {
                Storage::disk('public')->delete($media->thumbnail_url);
            }

            // Delete database record
            $media->delete();

            Log::info('Media deleted successfully', [
                'media_id' => $media->id,
                'user_id' => auth()->id()
            ]);

            return response()->json(['message' => 'Media deleted successfully']);

        } catch (\Exception $e) {
            Log::error('Media deletion failed', [
                'error' => $e->getMessage(),
                'media_id' => $media->id,
                'user_id' => auth()->id()
            ]);

            return response()->json(['error' => 'Failed to delete media'], 500);
        }
    }

    private function getFileType(string $mimeType): string
    {
        if (Str::startsWith($mimeType, 'image/')) {
            return 'image';
        }
        
        if (Str::startsWith($mimeType, 'video/')) {
            return 'video';
        }

        return 'image'; // default fallback
    }
}