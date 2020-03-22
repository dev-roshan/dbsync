for fresh laravel project only
    => > php artisan dbsync:convert_to_uuid

for existing laravel project
    =>have to do everything mannually what is done in the convert_to_uuid command

and

=> > php artisan dbsync:install

Place these thing in all your model
    =>use ```App\Uuids;``` (import this at top)

    inside model class add these
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