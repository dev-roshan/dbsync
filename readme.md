```composer require devroshan/dbsync```

For fresh laravel project only
To change your primary key from integer to [uuid](https://en.wikipedia.org/wiki/Universally_unique_identifier) 
   >php artisan dbsync:convert_to_uuid

For existing project 
   >php artisan dbsync:install

publish vendor files
   >php artisan vendor:publish --tag='dbsync'

Place these thing in all your model
    => ```use App\Uuids;``` (import this at top)

Create Symlink of storage
   >php artisan storage:link
       
Inside model class add these
        ```
        use Uuids;
        public $incrementing = false;
        protected $keyType= "string";
        ```

And in your blade file
```
@include('dbsync::export')
@include('dbsync::import')
```
Note:
For Import :it import data of env DB_CONNECTION connected database.

For Export :place one variable name DB_CONNECTION_2 as
            ```DB_CONNECTION_2=pgsql2```
            <br/>
            and in config/database.php configure connection for the database to import or sync<br/>
            ```'pgsql2' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => 'database_name',
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],```
