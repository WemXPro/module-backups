<?php

namespace Modules\Backups\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Backups';

    protected string $moduleNameLower = 'backups';

    protected string $moduleNamespace = 'Modules\Backups\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapAdminRoutes();
        $this->registerConfig();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    protected function mapAdminRoutes(): void
    {
        Route::prefix('admin')
            ->middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Backups', '/Routes/admin.php'));
    }
}
