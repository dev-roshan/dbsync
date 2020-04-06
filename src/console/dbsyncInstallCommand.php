<?php namespace devroshan\dbsync\commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Illuminate\Database\Schema\Blueprint;
use DB;
use Cache;
use Request;
use App;
use Schema;

class dbsyncInstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dbsync:install';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dbsync Installation Command';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('  --------- :===: please wait for a moment  :==: ---------------  ');
        $this->info('====================================================================');
        
        // copying uuid file
        $fp=realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $fpl=$fp.'/files/Uuids.php';
        copy($fpl,base_path().'/app/Uuids.php');             
        
        if(!Schema::hasTable('export_logs')){
            Schema::create('export_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('exported_by_client_id')->nullable();
                $table->uuid('exported_for_client_id')->nullable();
                $table->longText('file_path');
                $table->boolean('is_synced')->default(0);
                $table->timestamp('exported_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamps();
            });
        }
        $fpl=$fp.'/files/ExportLog.php';
        copy($fpl,base_path().'/app/ExportLog.php'); 

        $this->info('Completed.');
    }
  
}
