<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ScraperController;

class scraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:amazon {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //get the url argument passed
        $url = $this->argument('url');
        //no url passed            
        if (!$url) {
            //throw error message
            echo collect(['error' => 'no url submitted'])->toJson(), "\n";
            return false;
        }        
        //call the scraper function
        ScraperController::run([
            'url' => $url,
            'store' => 'amazon'
        ]);
    }
}
