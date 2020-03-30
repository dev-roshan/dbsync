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

class PgSqlImportController extends Controller
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
        // $this->db=env('DB_DATABASE'); 
        // $this->db="test1"; 
        $this->conn=env('DB_CONNECTION_2');

        $this->inserted_count=0;
        $this->updated_count=0;
        
    }

    /**
     * Update the import log.
     *
     * @return void
     */
    public function updateImportLog($file_path){
        // $el=new ExportLog();
        // $el->file_path=$file_path;
        // $el->client_id="3f2d5b20-5883-11ea-bf61-03914d8796ac";
        // $el->save();
    }
    
    /**
     * export data after the export log exported_at
     */
    public function importLatestData($master_tables,$ordered_data_tables){
        $path = public_path().'/file';
        $cmd="PGPASSWORD='root' /usr/bin/psql -c 'COPY (SELECT * FROM branches) TO STDOUT;' -h localhost -d test -U postgres > /opt/lampp/htdocs/backpack/files.csv 2>&1; echo $?";
        // $cmd='PGPASSWORD="'.$this->pass.'" psql -c "COPY (SELECT * FROM branches) TO STDOUT;" -h localhost -d test -U postgres > '.$path.' test 2>&1; echo $?';
        $shell_output=shell_exec($cmd);

    }

    /**
     * zip the backup files
     */
    public function zipFile($path,$post_path){
        // Get real path for our folder
        $rootPath = $path;

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open(public_path().$post_path.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

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
        $zip->close();
    }

    /**
     * importing and syncing
     */
    public function import(Request $request){
        // increasing memory size and execution time
        ini_set('memory_limit', '500M');
        ini_set('max_execution_time', 300);

        $validator = Validator::make($request->all(), [
            'file' => 'required' 
        ]);
        if(!$validator->fails()){

        $ext=$request->file->getClientOriginalExtension();
        if($ext==="zip"){
            $file_name=$request->file->getClientOriginalName();
            $request->file->storeAs('public/backup_import',$file_name);
            $file=public_path().'/storage/backup_import/'.$file_name;
            $unzipPath=public_path().'/storage/backup_import/'.pathinfo($file_name)['filename'];
            $success=$this->unZipFile($file,$unzipPath);
            if($success){
                $check=$this->iterateFileAndCheckViolation($unzipPath);
                // unique key validation
                if(!$check['check']){
                    return response()->json(['error' => !$check['check'],'logfile'=>'/storage'.$check['log_file']]);
                }
                else{
                    // foreign key check disabled
                    // DB::connection('pgsql2')->select(DB::raw("SET session_replication_role = 'replica'"));
                    // $this->insertAndUpdateData($unzipPath);
                    // DB::connection('pgsql2')->select(DB::raw("SET session_replication_role = 'origin'"));
                    // return response()->json([['error' => false],['inserted_data'=>$this->inserted_count],['updated_data'=>$this->updated_count]]);
                    return response()->json(['error' => false,'check'=>'No unique key violation']);
    
                }

            }
            else{
                return response()->json(['error' => 'Unable to extract file'], 404);
            }

            // $unzipPath="/opt/lampp/htdocs/ward/public/storage/backup/2020-03-11_04_27_02_backup/kup/";

        }
        else{
            return response()->json(['error' => 'not a valid file.'], 404);
        }
    }
    else{
        return response()->json(['error' => $validator->messages()->first()], 404);
    }
        
    }

    /**
     * extracting compress zip file
     * @return boolean
     */
    public function unZipFile($zipfile,$unzipPath){
        $zip = new ZipArchive;
        $res = $zip->open($zipfile);
        if ($res === TRUE) {
        $zip->extractTo($unzipPath);
        $zip->close();
            return true;
        } 
        else{
            return false;
        }
    }

    /**
     * iterate dump files and check for unique constraints violation
     */
    public function iterateFileAndCheckViolation($path){
       $check=true;
       $acutual_path=$path.'/kup';
        // creating log file
        $log_file_name= '/dbsync_logs/'.Carbon::now()->toDateString().'_'.Carbon::now()->toTimeString().'.log';
       Storage::disk('public')->put($log_file_name,'unique key violations');
       

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($acutual_path),
            \RecursiveIteratorIterator::LEAVES_ONLY
            );
    
        foreach ($files as $name => $file)
        {
            // check if file is xml file or not
            if (strpos($file->getFileName(), '.xml') !== false) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $xml = simplexml_load_file($filePath);
                $table=$xml->getName();
                
                // checking if unique constraints exists in table
                $query="select kcu.table_schema,
                        kcu.table_name,
                        tco.constraint_name,
                        kcu.ordinal_position as position,
                        kcu.column_name as key_column,
                        tco.constraint_type as con_type
                        from information_schema.table_constraints tco
                        join information_schema.key_column_usage kcu 
                            on kcu.constraint_name = tco.constraint_name
                            and kcu.constraint_schema = tco.constraint_schema
                            and kcu.constraint_name = tco.constraint_name
                        where tco.constraint_type = 'UNIQUE' and kcu.table_schema='public' and kcu.table_name ='".$table."'";
                $unique_keys=DB::connection($this->conn)->select($query);
                if(count($unique_keys)!== 0){
                    // getting uk columns name
                    $uk=array_column($unique_keys, 'key_column');
                    // iterating xml element
                    $data=[];
                    foreach ($xml->children() as $row) {
                        foreach($row as $key=> $child) {
                                $data[$key]=trim($row->{$key});
                        }
                        // for now assuming pk to be id
                        //todo
                        $query="select * from ".$table." where id!= '".$data['id']."' and ";
                        $i=0;
                        foreach($uk as $key=> $ukeys){
                            if($data[$ukeys]!=""){
                                // checking for alphanumeric value 
                                if(ctype_alnum($data[$ukeys])){
                                    $i==0?$query=$query."(".$ukeys."='".$data[$ukeys]."'":$query=$query." and ".$ukeys."='".$data[$ukeys]."'";
                                }
                                else{
                                    $data[$ukeys]=preg_replace("/[^[:alnum:]]/u",'',$data[$ukeys]);
                                    $i==0?$query=$query."(regexp_replace(".$ukeys."::varchar,'[^[:alnum:]]','','g')='".$data[$ukeys]."'":$query=$query." and regexp_replace(".$ukeys."::varchar,'[^[:alnum:]]','','g')='".$data[$ukeys]."'";
                                }

                                $i++;
                            }
                            // $query=$query.$ukeys."='".$data[$ukeys]."'";
                            
                        }
                        $query=$query.")";
                        $exist=DB::connection($this->conn)->select($query);
                            if($exist){
                                $violation_exists[$table][]=$exist;
                                $appendTofile="Check for these unique keys ";
                                foreach($uk as $uks){
                                    $appendTofile=$appendTofile.$uks." ,";
                                }
                                Storage::append('/public'.$log_file_name, $table);
                                Storage::append('/public'.$log_file_name, $appendTofile);
                                foreach($exist as $ex){
                                    $appendTofile=json_encode($ex);
                                    Storage::append('/public'.$log_file_name, $appendTofile);
                                }

                            }
                        
                    }
                }

            }
        } 
        if(isset($violation_exists))
            $check=false;
        else 
            $violation_exists=[];

        return ["check"=>$check,"violations"=>$violation_exists,"log_file"=>$log_file_name];
    }

    /**
     * insert new data and update existing data
     */
    public function insertAndUpdateData($unzipPath){
        $acutual_path=$unzipPath.'/kup';
        // creating log file
        // $log_file_name= '/dbsync_logs/'.Carbon::now()->toDateString().'_'.Carbon::now()->toTimeString().'.log';
        // Storage::disk('public')->put($log_file_name,'unique key violations');
        $update_log_file= '/dbsync_logs/'.Carbon::now()->toDateString().'_'.Carbon::now()->toTimeString().'.log';
       

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($acutual_path),
            \RecursiveIteratorIterator::LEAVES_ONLY
            );
        foreach ($files as $name => $file)
        {
            // check if file is xml file or not
            if (strpos($file->getFileName(), '.xml') !== false) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $xml = simplexml_load_file($filePath);
                $table=$xml->getName();
                DB::connection($this->conn)->select(DB::raw("ALTER TABLE ".$table." DISABLE TRIGGER ALL"));
                // iterating through every data
                $data=[];
                foreach ($xml->children() as $row) {
                    foreach($row as $key=> $child) {
                            $data[$key]=trim($row->{$key});
                    }

                    $data_exist=DB::connection($this->conn)->table($table)->where('id',$data['id'])->get();
                    if(count($data_exist)!=0){
                        $this->updateData($data_exist,$data,$table,$update_log_file);
                    }
                    else{
                        $this->insertData($data,$table);
                    }
                }
                DB::connection($this->conn)->select(DB::raw("ALTER TABLE ".$table." ENABLE TRIGGER ALL"));
            }
        }
       
    }

    /**
     * insert new data
     */
    public function insertData($data,$table){
        try {
           DB::table($table)->insert($data);
        } catch (\Exception $e) {
            // removing empty data and key before insert
            $sql="insert into ".$table." (";
            $i=0;
            $keys="";
            $to_insert_data="";
            foreach($data as $key=>$dt){
                if($dt!=""){
                    $i==0? $keys=$keys.$key:$keys=$keys.",".$key;
                    $i==0? $to_insert_data=$to_insert_data."'".$dt."'":$to_insert_data=$to_insert_data.","."'".$dt."'";
                }
                $i++;
            }
            $sql=$sql.$keys.") values (".$to_insert_data.")";
            DB::select(DB::raw($sql));
            $this->inserted_count++;
            
        }
    }

    /**
     * update existing data
     */
    public function updateData($data_exist,$data,$table,$log_file){
        // converting collection to array
        $data_exist=json_decode(json_encode($data_exist->toArray()), true)[0];
        // diff between array
        $diff=array_diff($data,$data_exist);
        if(count($diff)!=0){
            if($data["updated_at"]>$data_exist["updated_at"]){
                $sql="UPDATE ".$table." SET ";
                $post_sql="";
                $i=0;
                foreach($diff as $key=>$dff){
                    $i==0?$post_sql=$post_sql.$key."= '".$dff."'":$post_sql=$post_sql.",".$key."= '".$dff."'";
                    $i++;
                }
                $sql=$sql.$post_sql." WHERE id='".$data["id"]."'";
                DB::select(DB::raw($sql));
                $appendTofile=$table."\n";
                $appendTofile=$appendTofile."old data \n";
                $appendTofile=$appendTofile.json_encode($data_exist);
                $appendTofile=$appendTofile."\n updated data";
                $appendTofile=$appendTofile.json_encode($data_exist);
                Storage::append('/public'.$log_file, $appendTofile);
                $this->updated_count++;
            }
            else if($data["updated_at"]<$data_exist["updated_at"]){

            }
            else{

            }
        }
    }

}