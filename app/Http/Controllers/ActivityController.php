<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as ValidatorInstance;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Formulier om een activiteit toe te voegen + overzicht van de laatste vijf.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('activities.index', [
            'activities' => $user->activities()->latest()->latest('id')->limit(self::PER_PAGE)->get(),
            'activityCount' => $user->activities()->count(),
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
        $user = $request->user();

        if ($user->activities()->count() <= self::PER_PAGE) {
            return redirect()->route('activities.index');
        }

        $activities = $user->activities()->latest()->latest('id')->paginate(self::PER_PAGE);

        if ($activities->isEmpty() && $activities->currentPage() > 1) {
            return redirect()->route('activities.all', ['page' => $activities->lastPage()]);
        }

        return view('activities.all', ['activities' => $activities]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimetypes:'.implode(',', array_keys(self::IMAGE_MIME_TYPES)), 'max:'.self::MAX_IMAGE_KILOBYTES],
            'native_image_path' => ['nullable', 'string'],
        ], [
            'title.required' => 'Vul een titel in.',
            'title.max' => 'De titel mag maximaal 150 tekens bevatten.',
            'description.required' => 'Vul een beschrijving in.',
            'description.max' => 'De beschrijving mag maximaal 2000 tekens bevatten.',
            'image.uploaded' => 'De afbeelding kon niet worden geüpload. Kies een kleinere afbeelding.',
            'image.max' => 'De afbeelding mag maximaal 10 MB groot zijn.',
            'image.*' => 'Kies een afbeelding (JPG, PNG, WebP, GIF of HEIC).',
        ]);

        $nativeImage = null;

        $validator->after(function (ValidatorInstance $validator) use ($request, &$nativeImage): void {
            if ($request->filled('native_image_path') && ! $request->hasFile('image')) {
                $nativeImage = $this->resolveNativeImage((string) $request->input('native_image_path'));

                if ($nativeImage === null) {
                    $validator->errors()->add('native_image_path', 'De gekozen afbeelding kon niet worden gelezen. Kies een andere afbeelding.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('activities.index')
                ->withInput($request->only('title', 'description'))
                ->with(['status_message' => $validator->errors()->first(), 'status_type' => 'error']);
        }

        $user = $request->user();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $this->storeUploadedImage($request->file('image'), $user->id);
        } elseif ($nativeImage !== null) {
            $imagePath = $this->storeNativeImage($nativeImage, $user->id);
        }

        $user->activities()->create([
            'title' => trim($request->string('title')),
            'description' => trim($request->string('description')),
            'image_path' => $imagePath,
        ]);

        return redirect()->route('activities.index')->with([
            'status_message' => 'Je activiteit is toegevoegd.',
            'status_type' => 'success',
        ]);
    }

    /**
     * Serveert de afbeelding van een activiteit via een ondertekende URL
     * (zie Activity::imageUrl()). Draait zonder sessie-middleware, dus zonder
     * route model binding: we zoeken alleen het pad op.
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

    private function storeUploadedImage(UploadedFile $file, int $userId): string
    {
        $extension = self::IMAGE_MIME_TYPES[$file->getMimeType()] ?? 'jpg';

        return $file->storeAs("activities/{$userId}", Str::uuid().'.'.$extension, Activity::IMAGE_DISK);
    }

    /**
     * @param  array{path: string, extension: string}  $image
     */
    private function storeNativeImage(array $image, int $userId): string
    {
        $path = "activities/{$userId}/".Str::uuid().'.'.$image['extension'];

        $stream = fopen($image['path'], 'rb');
        Storage::disk(Activity::IMAGE_DISK)->put($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        // De camera-plugin ruimt zijn kopie niet zelf op.
        @unlink($image['path']);

        return $path;
    }

    /**
     * Controleert een pad dat de galerijkiezer van de NativePHP-camera-plugin teruggaf.
     *
     * Het pad komt uit de browser en is dus niet te vertrouwen: we accepteren het
     * alleen in de native app, alleen uit de map "Gallery" van de plugin, alleen
     * bestanden met de naam die de plugin zelf geeft, en alleen echte afbeeldingen.
     *
     * @return array{path: string, extension: string}|null
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

        return $extension !== null ? ['path' => $realPath, 'extension' => $extension] : null;
    }

    private function runningInNativeApp(): bool
    {
        return (bool) config('nativephp-internal.running', false);
    }
}
