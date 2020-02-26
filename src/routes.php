<?php
// Routing without any middleware
Route::group(['middleware' => ['web',config('backpack.base.middleware_key', 'admin')], 'namespace' => 'controllers'], function (){    
    Route::prefix('dbsync/')->group(function () {
        // pgsql routes
        if(env('DB_CONNECTION')==='pgsql'){
            // export
            Route::get('export', 'PgSqlSyncController@export');
            // sync
            Route::get('sync', 'PgSqlSyncController@import');
        }
    });
});