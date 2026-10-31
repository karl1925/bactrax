<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Move historical procurement files out of the web-accessible storage disk. */
class SecureProcurementFiles extends Command
{
    protected $signature = 'procurements:secure-files {--dry-run : Report files without moving or deleting them}';
    protected $description = 'Move files under public procurement storage to the private procurement disk.';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('procurements');
        $files = collect($public->allFiles('procurements'))->reject(fn ($path) => str_contains($path, '/.DS_Store') || str_ends_with($path, '.DS_Store'));

        if ($this->option('dry-run')) {
            $this->table(['File', 'Private copy exists'], $files->map(fn ($path) => [$path, $private->exists($path) ? 'yes' : 'no'])->all());
            $this->info($files->count().' file(s) found. No files changed.');
            return self::SUCCESS;
        }

        $moved = 0;
        foreach ($files as $path) {
            if (! str_starts_with($path, 'procurements/') || str_contains($path, '..')) {
                $this->error("Skipped unexpected path: {$path}");
                continue;
            }

            $source = $public->readStream($path);
            if (! is_resource($source)) {
                $this->error("Could not read {$path}");
                return self::FAILURE;
            }
            $bytes = stream_get_contents($source);
            fclose($source);
            if (! is_string($bytes)) {
                $this->error("Could not read {$path}");
                return self::FAILURE;
            }

            if (! $private->put($path, $bytes) || ! $private->exists($path) || hash_file('sha256', $private->path($path)) !== hash('sha256', $bytes)) {
                $this->error("Private copy verification failed for {$path}; public original was retained.");
                return self::FAILURE;
            }

            if (! $public->delete($path)) {
                $this->error("Private copy succeeded, but public copy could not be removed: {$path}");
                return self::FAILURE;
            }
            $moved++;
        }

        $this->info("Secured {$moved} procurement file(s). Private copies were verified before public copies were removed.");
        return self::SUCCESS;
    }
}
