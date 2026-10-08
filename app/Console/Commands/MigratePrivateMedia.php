<?php

namespace App\Console\Commands;

use App\Models\SmallGroup;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePrivateMedia extends Command
{
    protected $signature = 'access:migrate-private-media {--dry-run : Report files without moving them}';

    protected $description = 'Move member and small-group photos from public storage to private storage';

    public function handle(): int
    {
        $paths = User::query()->whereNotNull('profile_photo_path')->pluck('profile_photo_path')
            ->merge(SmallGroup::query()->whereNotNull('photo_path')->pluck('photo_path'))
            ->filter()->unique()->values();

        $moved = 0;
        $alreadyPrivate = 0;
        $missing = 0;

        foreach ($paths as $path) {
            if (Storage::disk('local')->exists($path)) {
                $alreadyPrivate++;
                if (! $this->option('dry-run') && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }

                continue;
            }

            if (! Storage::disk('public')->exists($path)) {
                $missing++;
                $this->warn('Missing: '.$path);

                continue;
            }

            if (! $this->option('dry-run')) {
                Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                Storage::disk('public')->delete($path);
            }
            $moved++;
        }

        $verb = $this->option('dry-run') ? 'would move' : 'moved';
        $this->info("{$moved} file(s) {$verb}; {$alreadyPrivate} already private; {$missing} missing.");

        return self::SUCCESS;
    }
}
