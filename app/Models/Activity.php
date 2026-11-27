<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

#[Fillable(['title', 'description', 'image_path'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * Afbeeldingen staan op de "public"-schijf (storage/app/public) en worden
     * via een eigen route geserveerd, zodat het ook zonder `storage:link`
     * werkt (in de browser én in de NativePHP-app).
     */
    public const IMAGE_DISK = 'public';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ActivityComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ActivityComment::class);
    }

    /**
     * Gebruikers die deze activiteit leuk vinden (tabel `activity_likes`).
     *
     * @return BelongsToMany<User, $this>
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_likes')->withTimestamps();
    }

    /**
     * Community-overzicht: activiteiten van iedereen, nieuwste eerst, met de
     * maker, de reacties, het aantal likes/reacties en of de kijker zelf al liket.
     *
     * @param  Builder<Activity>  $query
     */
    #[Scope]
    protected function forFeed(Builder $query, User $viewer): void
    {
        $query->with(['user:id,username', 'comments' => fn ($q) => $q->oldest()->oldest('id')->with('user:id,username')])
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked_by_viewer' => fn ($q) => $q->where('users.id', $viewer->id)])
            ->latest()
            ->latest('id');
    }

    /**
     * In de app komt een activiteit uit de API: de afbeelding staat dan op de server.
     */
    private ?string $remoteImageUrl = null;

    public function setRemoteImageUrl(?string $url): void
    {
        $this->remoteImageUrl = $url;
    }

    public function imageUrl(): ?string
    {
        if ($this->remoteImageUrl !== null) {
            return $this->remoteImageUrl;
        }

        if ($this->image_path === null) {
            return null;
        }

        // Browser: direct het statische bestand via de storage-link, zonder PHP-request.
        // De NativePHP-app serveert geen bestanden buiten public/, dus daar via de route.
        if (! config('nativephp-internal.running', false) && is_dir(public_path('storage'))) {
            return asset('storage/'.$this->image_path);
        }

        return URL::signedRoute('activities.image', $this);
    }

    protected static function booted(): void
    {
        static::deleted(function (Activity $activity): void {
            if ($activity->image_path !== null) {
                Storage::disk(self::IMAGE_DISK)->delete($activity->image_path);
            }
        });
    }
}
