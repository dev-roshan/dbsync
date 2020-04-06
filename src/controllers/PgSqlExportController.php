<?php namespace devroshan\dbsync\controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\ExportLog;
use File;
use ZipArchive;
use Storage;
use Validator;
use App\Models\BackpackUser;

class PgSqlExportController extends Controller
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $db;

    public function __construct()
    {
        $this->host=env('DB_HOST');
        $this->port=env('DB_PORT');
        $this->user=env('DB_USERNAME');
        $this->pass=env('DB_PASSWORD');
        $this->db=env('DB_DATABASE'); 
        $this->conn=env('DB_CONNECTION');
        $this->updated_at='';
        $this->hasData=true;
        // $this->host=env('DB_HOST');
        // $this->port=env('DB_PORT');
        // $this->user=env('DB_USERNAME');
        // $this->pass=env('DB_PASSWORD');
        // $this->db='test2'; 
    }
    
    /**
     * export dump file 
     */
    public function export(Request $request){
        // increasing memory size and execution time
        ini_set('memory_limit', '500M');
        ini_set('max_execution_time', 600);
        // retreiving all tables
        $tables=DB::connection($this->conn)->select("SELECT table_schema,table_name FROM information_schema.tables where table_schema = 'public'");
        
        $master_tables=[];
        $data_tables=[];
        $data_tables_fk=[];
        foreach($tables as $key=>$table)
        {
            
            // dont export migrations table and export_logs table
            if($table->table_name!=="migrations" && $table->table_name!=="failed_jobs" && $table->table_name!=="export_logs"){
                // checking foreign keys
                
                $fk=DB::connection($this->conn)->select("SELECT
                tc.table_schema, 
                tc.constraint_name, 
                tc.table_name, 
                kcu.column_name, 
                ccu.table_schema AS foreign_table_schema,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name 
                FROM 
                information_schema.table_constraints AS tc 
                JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name=?",[$table->table_name]);
                // assigning table to master table without fk
                if(count($fk)===0){
                $master_tables[$table->table_name]=$table;
                }
                else{
                $data_tables_fk[$table->table_name]=$fk;
                }
            }
         
        }
        // ordering table with fk
        $ordered_data_tables=[];
        foreach($data_tables_fk as $key=>$fk){
            foreach($fk as $fk){
                if(!array_key_exists($fk->table_name,$master_tables)){
                    if(!array_key_exists($fk->table_name,$ordered_data_tables)){
                        $ordered_data_tables[$fk->table_name]=$fk->table_name;
                    }
                }
            }
        }

        $export_data=ExportLog::where([['exported_by_client_id',env('CLIENT_ID')],['exported_for_client_id',env('EXPORT_CLIENT_ID')]])->orderBy('created_at','desc')->first();
        if(!$export_data){
            $file_path=$this->exportAllData($master_tables,$ordered_data_tables);
            $file=$file_path.'.zip';
            
            return response()->json([
                'has_data' => $this->hasData,
                'file'=> $file
            ]);
        }
        else{
            $this->updated_at=$export_data->updated_at;
            $file_path=$this->exportLatestData($master_tables,$ordered_data_tables);
            $file=$file_path.'.zip';
            return response()->json([
                'has_data' => $this->hasData,
                'file'=> $file
            ]);
        }
        
    }

    /**
     * Update the export log.
     *
     * @return void
     */
    public function updateExportLog($file_path){
        $el=new ExportLog();
        $el->file_path=$file_path;
        $el->exported_by_client_id=env('CLIENT_ID');
        $el->exported_for_client_id=env('EXPORT_CLIENT_ID');
        $el->save();
    }

    /**
     * exporting all data for the first time
     */
    public function exportAllData($master_tables,$ordered_data_tables){
        $dt = Carbon::now();
        $post_path='/storage/backup/'.$dt->toDateString().'_'.$dt->toTimeString().'_backup';
        $path = public_path().$post_path;
        $yes=File::makeDirectory($path, $mode = 0777, true, true);
        // $yes=Storage::makeDirectory(public_path() . "/backup/hello/"); 
        foreach($master_tables as $mt){
            $file_name="master_".$mt->table_name.".xml";
            $file_path=$path.'/'.$file_name;
            $this->createXmlFile($file_path,$mt->table_name,false);
        }
        $i=1;
        foreach($ordered_data_tables as $odt){
            $file_name="dt_".$i++.'_'.$odt.".xml";
            $file_path=$path.'/'.$file_name;
            $this->createXmlFile($file_path,$odt,false);
        }
        if(!$this->dir_is_empty($path)){
        $this->updateExportLog($post_path);
        $file_path=$path.'/'.'export_logs.xml';
        $this->createXmlFile($file_path,'export_logs',false);
        }
        
        $this->zipFile($path);
        return $post_path;
    }

    /**
     * create xml file for table
     * @param $file full path of file, $table table name
     */
    public function createXmlFile($file,$table,$latest){
        // check if for latest or all data
        if($latest)
            $query="select * from ".$table." where updated_at>='".$this->updated_at."'";
        else
            $query='select * from '.$table;
        
        $data=DB::connection($this->conn)->select($query);
        $query="select column_name, data_type from information_schema.columns where table_name = '".$table."'";
        $data_type=DB::connection($this->conn)->select($query);
        $data_type=json_decode(json_encode($data_type), true);
        
        if(count($data)!=0){
            $dom   = new \DOMDocument( '1.0', 'utf-8' );
            $dom   ->formatOutput = True;
    
            $root  = $dom->createElement( $table );
            $dom   ->appendChild( $root );
            
            foreach($data as $row){
                $i=0;
                $node = $dom->createElement( $table );
                foreach( $row as $key => $val )
                {
                    // getting column data type
                    $arr = array_filter($data_type, function($ar) use ($key) {
                        return ($ar['column_name'] == $key);
                    });
                    $arr=reset($arr);
                    $column_data_type=next($arr);
                    
                    if (strpos($column_data_type, 'text') !== false || strpos($column_data_type, 'character') !== false) {
                        $child = $dom->createElement( $key );
                        $child ->appendChild( $dom->createCDATASection( $val) );
                    }
                    else{
                            $child = $dom->createElement( $key ,$val );
                      
                    }
                    // $child ->appendChild( $dom->createCDATASection( $val) );
                    $node  ->appendChild( $child );
                    $i++;
                }
                $root->appendChild( $node );
            }
            $dom->save($file );
        }
      
    }

    /**
     * export data after the export log exported_at
     */
    public function exportLatestData($master_tables,$ordered_data_tables){

        $dt = Carbon::now();
        $post_path='/storage/backup/'.$dt->toDateString().'_'.$dt->toTimeString().'_backup';
        $path = public_path().$post_path;
        $yes=File::makeDirectory($path, $mode = 0777, true, true);
        // $yes=Storage::makeDirectory(public_path() . "/backup/hello/"); 
        foreach($master_tables as $mt){
            $file_name="master_".$mt->table_name.".xml";
            $file_path=$path.'/'.$file_name;
            $this->createXmlFile($file_path,$mt->table_name,true);
        }
        $i=1;
        foreach($ordered_data_tables as $odt){
            $file_name="dt_".$i++.'_'.$odt.".xml";
            $file_path=$path.'/'.$file_name;
            $this->createXmlFile($file_path,$odt,true);
        }
        if(!$this->dir_is_empty($path)){
            $this->updateExportLog($post_path);
            $file_path=$path.'/'.'export_logs.xml';
            $this->createXmlFile($file_path,'export_logs',true);
        }
        $this->zipFile($path);
        return $post_path;

    }

    /**
     * zip the backup folder
     * @param $path is the full path of folder to compress
     */
    public function zipFile($path){
        // Get real path for our folder
        $rootPath = $path;

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($path.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($rootPath),
        \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file)
        {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
        }
        // Zip archive will be created only after closing object
        try{
            $zip->close();
        }
        catch(\Exception $e){
            $this->hasData=false;
            // $zip->addFile('No_data.txt', "There is no data for this client to export.");
            // $zip->close();
        }
    }


    /**
     * check if directory is empty
     */
    function dir_is_empty($dir) {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
          if ($entry != "." && $entry != "..") {
            closedir($handle);
            return FALSE;
          }
        }
        closedir($handle);
        return TRUE;
      }


}