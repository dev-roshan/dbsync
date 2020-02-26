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

class PgSqlSyncController extends Controller
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
    }
    // export dump file 
    public function export(Request $request){
        $dt = Carbon::now();
        $file_name='/backup/'.$dt->toDateString().'_'.$dt->toTimeString().".dump";
        $file_path=public_path().$file_name;
        $cmd='PGPASSWORD="'.$this->pass.'" pg_dump -h '.$this->host.' -p '.$this->port.' -U '.$this->user.' -F c -b -v -f '.$file_path.' test 2>&1; echo $?';
        // $cmd='PGPASSWORD="root" pg_dump -h 127.0.0.1 -p 5432 -U postgres -F c -b -v -f /opt/lampp/htdocs/backpack/public/backup/2020-02-25_08:15:59.dump test 2>&1; echo $?';
        $shell_output=shell_exec($cmd);
        return response()->json([
            'data' => $shell_output,
            'file'=> $file_name
        ]);
    }

    public function import(Request $request){

    }

}