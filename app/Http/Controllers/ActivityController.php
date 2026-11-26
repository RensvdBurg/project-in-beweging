<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\Api\ApiException;
use App\Services\Api\UpMoveApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eigen activiteiten. Ophalen en plaatsen gaat via de API; de afbeelding gaat als
 * bestand mee en wordt op de server bewaard.
 */
class ActivityController extends Controller
{
    /**
     * Aantal activiteiten in het overzicht en per pagina op de detailpagina.
     */
    public const PER_PAGE = 5;

    private const MAX_IMAGE_KILOBYTES = 10 * 1024;

    private const IMAGE_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/heic' => 'heic',
    ];

    public function __construct(private readonly UpMoveApi $api) {}

    /**
     * Formulier om een activiteit toe te voegen + overzicht van de laatste vijf.
     */
    public function index(): View
    {
        $latest = $this->api->myActivities(1, self::PER_PAGE);

        return view('activities.index', [
            'activities' => $latest->getCollection(),
            'activityCount' => $latest->total(),
            'isNative' => $this->runningInNativeApp(),
            'statusMessage' => session('status_message', ''),
            'statusType' => session('status_type', 'success'),
        ]);
    }

    /**
     * Detailpagina met afbeeldingen, vijf activiteiten per pagina.
     * Alleen bereikbaar als de gebruiker meer dan vijf activiteiten heeft.
     */
    public function all(Request $request): View|RedirectResponse
    {
        $activities = $this->api->myActivities(max(1, $request->integer('page', 1)), self::PER_PAGE);

        if ($activities->total() <= self::PER_PAGE) {
            return redirect()->route('activities.index');
        }

        if ($activities->isEmpty() && $activities->currentPage() > 1) {
            return redirect()->route('activities.all', ['page' => $activities->lastPage()]);
        }

        return view('activities.all', ['activities' => $activities]);
    }

    public function store(Request $request): RedirectResponse
    {
        // De afbeelding controleren we hier al, zodat een te groot bestand niet eerst naar de server gaat.
        $validator = Validator::make($request->all(), [
            'image' => ['nullable', 'file', 'mimetypes:'.implode(',', array_keys(self::IMAGE_MIME_TYPES)), 'max:'.self::MAX_IMAGE_KILOBYTES],
            'native_image_path' => ['nullable', 'string'],
        ], [
            'image.uploaded' => 'De afbeelding kon niet worden geüpload. Kies een kleinere afbeelding.',
            'image.max' => 'De afbeelding mag maximaal 10 MB groot zijn.',
            'image.*' => 'Kies een afbeelding (JPG, PNG, WebP, GIF of HEIC).',
        ]);

        if ($validator->fails()) {
            return $this->backWithError($request, $validator->errors()->first());
        }

        $image = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $image = ['path' => (string) $file->getRealPath(), 'name' => $file->getClientOriginalName() ?: 'afbeelding.jpg'];
        } elseif ($request->filled('native_image_path')) {
            $image = $this->resolveNativeImage((string) $request->input('native_image_path'));

            if ($image === null) {
                return $this->backWithError($request, 'De gekozen afbeelding kon niet worden gelezen. Kies een andere afbeelding.');
            }
        }

        try {
            $message = $this->api->storeActivity(
                trim((string) $request->input('title', '')),
                trim((string) $request->input('description', '')),
                $image,
            );
        } catch (ApiException $exception) {
            if ($exception->isUnauthenticated() || $exception->isConnectionError()) {
                throw $exception;
            }

            return $this->backWithError($request, $exception->firstError());
        } finally {
            // De camera-plugin ruimt zijn kopie niet zelf op.
            if ($image !== null && $request->filled('native_image_path') && ! $request->hasFile('image')) {
                @unlink($image['path']);
            }
        }

        return redirect()->route('activities.index')->with([
            'status_message' => $message,
            'status_type' => 'success',
        ]);
    }

    /**
     * Serveert de afbeelding van een activiteit via een ondertekende URL
     * (zie Activity::imageUrl()). Draait op de server, zonder sessie-middleware,
     * dus zonder route model binding: we zoeken alleen het pad op.
     */
    public function image(int $activity): StreamedResponse
    {
        $path = Activity::query()->whereKey($activity)->value('image_path');
        $disk = Storage::disk(Activity::IMAGE_DISK);

        abort_if($path === null || ! $disk->exists($path), 404);

        return $disk->response($path, headers: [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function backWithError(Request $request, string $message): RedirectResponse
    {
        return redirect()->route('activities.index')
            ->withInput($request->only('title', 'description'))
            ->with(['status_message' => $message, 'status_type' => 'error']);
    }

    /**
     * Controleert een pad dat de galerijkiezer van de NativePHP-camera-plugin teruggaf.
     *
     * Het pad komt uit de browser en is dus niet te vertrouwen: we accepteren het
     * alleen in de native app, alleen uit de map "Gallery" van de plugin, alleen
     * bestanden met de naam die de plugin zelf geeft, en alleen echte afbeeldingen.
     *
     * @return array{path: string, name: string}|null
     */
    private function resolveNativeImage(string $path): ?array
    {
        if (! $this->runningInNativeApp()) {
            return null;
        }

        $realPath = realpath($path);

        if (
            $realPath === false
            || ! is_file($realPath)
            || basename(dirname($realPath)) !== 'Gallery'
            || ! str_starts_with(basename($realPath), 'gallery_selected_')
            || filesize($realPath) > self::MAX_IMAGE_KILOBYTES * 1024
        ) {
            return null;
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($realPath);
        $extension = self::IMAGE_MIME_TYPES[$mimeType] ?? null;

        return $extension !== null ? ['path' => $realPath, 'name' => 'afbeelding.'.$extension] : null;
    }

    private function runningInNativeApp(): bool
    {
        return (bool) config('nativephp-internal.running', false);
    }
}
