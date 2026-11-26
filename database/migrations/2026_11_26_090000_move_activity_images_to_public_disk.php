<?php

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Verplaatst bestaande activiteit-afbeeldingen van de private "local"-schijf
 * (storage/app/private/activities) naar de "public"-schijf (storage/app/public/activities).
 * De paden in `activities.image_path` blijven gelijk.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->move(Storage::disk('local'), Storage::disk('public'));
    }

    public function down(): void
    {
        $this->move(Storage::disk('public'), Storage::disk('local'));
    }

    private function move(Filesystem $from, Filesystem $to): void
    {
        foreach ($from->allFiles('activities') as $path) {
            if (! $to->exists($path)) {
                $stream = $from->readStream($path);
                $to->writeStream($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $from->delete($path);
        }

        $from->deleteDirectory('activities');
    }
};
