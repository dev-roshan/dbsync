<?php
// Routing without any middleware
Route::group(['middleware' => ['web',config('backpack.base.middleware_key', 'admin')], 'namespace' => 'controllers'], function (){    
// Route::group(['middleware' => ['web'], 'namespace' => 'controllers'], function (){    
    Route::prefix('dbsync/')->group(function () {
        // pgsql routes
        if(env('DB_CONNECTION')==='pgsql'){
            // export
            Route::get('export', 'PgSqlExportController@export')->name('dbsync_export');
            // sync
            Route::post('sync', 'PgSqlImportController@import')->name('dbsync_import');
        }
    });
});