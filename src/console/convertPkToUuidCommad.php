<?php namespace devroshan\dbsync\commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use DB;
use Cache;
use Request;
use App;
use Schema;

class convertPkToUuidCommad extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dbsync:convert_to_uuid';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pk integer to uuid Command';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('  --------- :===: please wait for a moment  :==: ---------------  ');
        $this->info('====================================================================');
        $this->findDefaultMigrationFile();
        
    }

    /**
     * find the default migration file and update it 
     */
    private function findDefaultMigrationFile(){
        // string to search in a filename.
        $searchString=array();
        $searchString[0] = 'create_users_table';
        $searchString[1] = 'create_failed_jobs_table';

        // all files in my/dir with the extension 
        // .php 
        $files = glob(base_path().'/database/migrations/*.php');

        // array populated with files found 
        // containing the search string.
        $filesFound = array();

        // iterate through the files and determine 
        // if the filename contains the search string.
        foreach($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            // determines if the search string is in the filename.
            if(strpos(strtolower($name), strtolower($searchString[0]))) {
                $filesFound[] = $file;
            } 

            if(strpos(strtolower($name), strtolower($searchString[1]))) {
                $filesFound[] = $file;
            } 
        }

        // change bigincrement to uuid
        foreach($filesFound as $file){
            $this->makeChanges($file);
        }
        $this->initSchema();
        
    }
    /**
     * make changes to default migration file
     */
    private function makeChanges($file){
        $this->info($file);
        $string_to_replace="bigIncrements('id')";
        $replace_with="uuid('id')->primary()";
        $content=file_get_contents($file);
        $content_chunks=explode($string_to_replace, $content);
        $content=implode($replace_with, $content_chunks);
        file_put_contents($file, $content);
        $string_to_replace="->id()";
        $replace_with="->uuid('id')->primary()";
        $content=file_get_contents($file);
        $content_chunks=explode($string_to_replace, $content);
        $content=implode($replace_with, $content_chunks);
        file_put_contents($file, $content);
        $this->info('file modified.');
    }

    /**
     * drop all table and runs migration
     */
    private function initSchema(){
        // copying migration default file to vendor
        // $fp=realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
        // $fpl=$fp.'/files/DatabaseMigrationRepository.php';
        // copy($fpl,base_path().'/vendor/laravel/framework/src/Illuminate/Database/Migrations/DatabaseMigrationRepository.php');             
        Schema::dropAllTables();
        // changing migration id data type to uuid
        // if(Schema::hasTable('migrations')){
        // DB::statement('drop table migrations');
        // }
        // DB::statement('CREATE TABLE migrations (
        //                 migration varchar(255) NOT NULL,
        //                 batch int4 NOT NULL,
        //                 id uuid NOT NULL,
        //                 CONSTRAINT migrations_pkey PRIMARY KEY (id))'
        //              );
        $output=shell_exec('php artisan migrate:refresh 2>&1; echo $?');
        $this->info($output);
        
    }
  
}
