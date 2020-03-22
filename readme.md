For fresh laravel project onlu
To change your primary key from integer to [uuid](https://en.wikipedia.org/wiki/Universally_unique_identifier) 
   >php artisan dbsync:convert_to_uuid

For existing project 
    >php artisan dbsync:install

Place these thing in all your model
    =>use ```App\Uuids;``` (import this at top)
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
