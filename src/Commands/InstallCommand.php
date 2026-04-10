<?php

namespace PredatorStudio\LiveTable\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'live-table:install';

    protected $description = 'Install the LiveTable package (publish config & views)';

    public function handle(): int
    {
        $theme = $this->choice(
            'Which CSS framework would you like to use?',
            ['bootstrap', 'tailwind'],
            'bootstrap',
        );

        $this->publishConfig($theme);
        $this->publishViews($theme);
        $this->publishMigrations();

        if ($theme === 'tailwind') {
            $this->showTailwindInstructions();
        }

        $this->components->info('LiveTable installed successfully.');

        return self::SUCCESS;
    }

    private function publishConfig(string $theme): void
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'live-table-config',
            '--force' => true,
        ]);

        $path = config_path('live-table.php');

        if (file_exists($path)) {
            file_put_contents(
                $path,
                preg_replace(
                    "/'theme'\s*=>[^\n]+/",
                    "'theme' => '$theme',",
                    file_get_contents($path),
                ),
            );
        }

        $this->components->task('Config published');
    }

    private function publishViews(string $theme): void
    {
        $this->callSilently('vendor:publish', [
            '--tag' => "live-table-views-{$theme}",
        ]);

        $this->components->task("Views published ({$theme})");
    }

    private function publishMigrations(): void
    {
        $migrationExists = collect(glob(database_path('migrations/*.php')))
            ->contains(fn (string $path) => str_contains($path, 'create_live_table_states_table'));

        if ($migrationExists) {
            $this->components->task('Migrations already exist – skipping');

            return;
        }

        $this->callSilently('vendor:publish', [
            '--tag' => 'live-table-migrations',
        ]);

        $this->components->task('Migrations published (run php artisan migrate to create live_table_states)');
    }

    private function showTailwindInstructions(): void
    {
        $this->newLine();
        $this->components->warn('Tailwind CSS – add the package path to your tailwind.config.js:');
        $this->newLine();
        $this->line('  content: [');
        $this->line('      // ... your existing paths');
        $this->line("      <fg=green>'./vendor/predatorstudio/live-table/resources/views/**/*.blade.php'</>,");
        $this->line('  ],');
        $this->newLine();
    }
}
