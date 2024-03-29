<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class youtube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'youtube:download {id}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download youtube video to MP3';

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
        $file = storage_path()."/tmp.mp3";
		if (file_exists($file)){
			unlink($file);
		}

		$id = $this->argument('id');
		if ($id != ""){
		    $url = 'https://www.youtube.com/watch?v='.$id;
            $cmd = 'youtube-dl --prefer-ffmpeg --ffmpeg-location /opt/ffmpeg/ffmpeg --output "'.storage_path().'/tmp.%(ext)s" --extract-audio --audio-format mp3 '.$url;
			$log = exec($cmd);
			//echo $log;
			$this->info("Fichier téléchargé");
		}
	}
}
