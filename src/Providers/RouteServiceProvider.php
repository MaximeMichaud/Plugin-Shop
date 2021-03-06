<?php

namespace Azuriom\Plugin\Shop\Providers;

use Azuriom\Extensions\Plugin\BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * @var string
     */
    protected $namespace = 'Azuriom\Plugin\Shop\Controllers';

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function loadRoutes()
    {
        $this->mapApiRoutes();

        $this->mapPluginsRoutes();

        $this->mapAdminRoutes();
    }

    protected function mapPluginsRoutes()
    {
        Route::prefix($this->pluginName)
            ->middleware('web')
            ->namespace($this->namespace)
            ->name($this->pluginName.'.')
            ->group(plugin_path($this->pluginName.'/routes/web.php'));
    }

    protected function mapApiRoutes()
    {
        Route::prefix($this->pluginName.'/api')
            ->middleware('api')
            ->namespace($this->namespace.'\Api')
            ->name($this->pluginName.'.')
            ->group(plugin_path($this->pluginName.'/routes/api.php'));
    }

    protected function mapAdminRoutes()
    {
        Route::prefix('admin/'.$this->pluginName)
            ->middleware('admin-access')
            ->namespace($this->namespace.'\Admin')
            ->name($this->pluginName.'.admin.')
            ->group(plugin_path($this->pluginName.'/routes/admin.php'));
    }
}
