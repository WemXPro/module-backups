<?php

namespace Modules\Backups\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Backups\Console\Commands\BackupCommand;

class BackupsServiceProvider extends ServiceProvider
{
    protected array $commands = [
        BackupCommand::class,
    ];
    protected string $moduleName = 'Backups';
    protected string $moduleNameLower = 'backups';

    public function boot(): void
    {
        $this->registerViews();
        $this->commands($this->commands);

        // Auto backup
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            if (settings('backups::auto-backup', true)) {
                $schedule->command('backup --action=create --type=all')
                    ->cron("0 */" . settings('backups::every-hours', 12) . " * * *");
            }
        });
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
