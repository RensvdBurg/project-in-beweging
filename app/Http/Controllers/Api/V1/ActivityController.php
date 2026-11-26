<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityComment;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * Activiteiten: de community-feed, eigen activiteiten, plaatsen, likes en reacties.
 */
class ActivityController extends Controller
{
    private const PER_PAGE = 5;

    private const MAX_IMAGE_KILOBYTES = 10 * 1024;

    private const IMAGE_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/heic' => 'heic',
    ];

    /**
     * "Samen in beweging": activiteiten van iedereen, nieuwste eerst.
     */
    public function feed(Request $request): AnonymousResourceCollection
    {
        return ActivityResource::collection(
            Activity::query()->forFeed($request->user())->paginate($this->perPage($request))
        );
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        return ActivityResource::collection(
            Activity::query()->whereBelongsTo($request->user())->forFeed($request->user())->paginate($this->perPage($request))
        );
    }

    public function show(Request $request, Activity $activity): ActivityResource
    {
        return new ActivityResource($this->forFeed($request, $activity));
    }

    /**
     * Activiteit plaatsen; de afbeelding (optioneel) komt als multipart-bestand mee.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimetypes:'.implode(',', array_keys(self::IMAGE_MIME_TYPES)), 'max:'.self::MAX_IMAGE_KILOBYTES],
        ], [
            'title.required' => 'Vul een titel in.',
            'title.max' => 'De titel mag maximaal 150 tekens bevatten.',
            'description.required' => 'Vul een beschrijving in.',
            'description.max' => 'De beschrijving mag maximaal 2000 tekens bevatten.',
            'image.uploaded' => 'De afbeelding kon niet worden geüpload. Kies een kleinere afbeelding.',
            'image.max' => 'De afbeelding mag maximaal 10 MB groot zijn.',
            'image.*' => 'Kies een afbeelding (JPG, PNG, WebP, GIF of HEIC).',
        ]);

        $user = $request->user();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = self::IMAGE_MIME_TYPES[$file->getMimeType()] ?? 'jpg';
            $imagePath = $file->storeAs("activities/{$user->id}", Str::uuid().'.'.$extension, Activity::IMAGE_DISK);
        }

        $activity = $user->activities()->create([
            'title' => trim($request->string('title')),
            'description' => trim($request->string('description')),
            'image_path' => $imagePath,
        ]);

        AuditLog::record('activity_created', "{$user->username} heeft de activiteit \"{$activity->title}\" geplaatst.", $user);

        return response()->json([
            'message' => 'Je activiteit is toegevoegd.',
            'activity' => new ActivityResource($this->forFeed($request, $activity)),
        ], 201);
    }

    /**
     * Like aan- of uitzetten; geeft de bijgewerkte activiteit terug.
     */
    public function like(Request $request, Activity $activity): ActivityResource
    {
        $activity->likes()->toggle($request->user()->id);

        return new ActivityResource($this->forFeed($request, $activity));
    }

    public function comment(Request $request, Activity $activity): JsonResponse
    {
        $request->validate(['body' => ['required', 'string', 'max:1000']], [
            'body.max' => 'Een reactie mag maximaal 1000 tekens bevatten.',
            'body.*' => 'Schrijf eerst een reactie.',
        ]);

        $activity->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim((string) $request->input('body')),
        ]);

        return (new ActivityResource($this->forFeed($request, $activity)))->response()->setStatusCode(201);
    }

    /**
     * Reactie verwijderen; alleen de schrijver mag dat.
     */
    public function destroyComment(Request $request, ActivityComment $comment): ActivityResource
    {
        abort_unless($comment->user_id === $request->user()->id, 403, 'Je kunt alleen je eigen reacties verwijderen.');

        $activity = $comment->activity;
        $comment->delete();

        return new ActivityResource($this->forFeed($request, $activity));
    }

    private function forFeed(Request $request, Activity $activity): Activity
    {
        return Activity::query()->forFeed($request->user())->findOrFail($activity->id);
    }

    private function perPage(Request $request): int
    {
        return max(1, min(50, $request->integer('per_page', self::PER_PAGE)));
    }
}
