<?php
namespace devroshan\dbsync;


use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class dbrouteServiceProvider extends RouteServiceProvider
{
    protected $namespace='devroshan\dbsync';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        // $this->mapApiRoutes();

        $this->mapWebRoutes();
    }



    protected function mapWebRoutes()
    {
        Route::namespace($this->namespace)
            ->group(__DIR__ . '/routes.php');
    }
}