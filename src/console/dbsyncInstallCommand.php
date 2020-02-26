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
        
        // if(!Schema::hasTable('export_log')){
        //     Schema::create('export_log', function (Blueprint $table) {
        //         $table->bigIncrements('id');
        //         $table->string('name');
        //         $table->string('email')->unique();
        //         $table->timestamp('email_verified_at')->nullable();
        //         $table->string('password');
        //         $table->rememberToken();
        //         $table->timestamps();
        //     });
        // }
        $this->info('Completed.');
    }
  
}
