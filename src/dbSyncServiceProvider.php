<?php namespace devroshan\dbsync;

// use devroshan\databasesync\commands\DeveloperCommand;
// use devroshan\databasesync\commands\Generate;
// use devroshan\databasesync\commands\MigrateData;
// use devroshan\databasesync\controllers\scaffolding\singletons\ColumnSingleton;
// use devroshan\databasesync\helpers\MiscellanousSingleton;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use devroshan\dbsync\commands\dbsyncInstallCommand;
use devroshan\dbsync\commands\convertPkToUuidCommad;
use App;

class dbSyncServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */

    public function boot()
    {        

        // Register views
        $this->loadViewsFrom(__DIR__.'/views', 'dbsync');
        $this->publishes([
            __DIR__.'/assets' => public_path('vendor/dbsync'),
        ], 'dbsync');

        $this->app->register('devroshan\dbsync\dbrouteServiceProvider');
        require __DIR__.'/routes.php';
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {                                   
        require __DIR__.'/helpers/Helper.php';

        // Singletons
        $this->app->singleton('dbsync', function ()
        {
            return true;
        });
        $this->registerDbsyncCommand();
        $this->commands('dbsyncinstall');
        $this->commands('pktouuid');

        // Register additional library
        // $this->app->register('Intervention\Image\ImageServiceProvider');
    }

    // command registering
    private function registerDbsyncCommand()
    {
        $this->app->singleton('dbsyncinstall',function() {
            return new dbsyncInstallCommand;
        });

        $this->app->singleton('pktouuid',function() {
            return new convertPkToUuidCommad;
        });
      
    }



}