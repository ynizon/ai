<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use MyLaravelMP3;
use App\Song;

class scanSongs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'scan:songs';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan songs files';

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
        //
		//Scan les chansons y compris les sous repertoires	
		set_time_limit(0);
		
		DB::update("update songs set status = 0");
		$nb_errors = 0;
		$nb_files = 0;
		$scan = "";
		
		if (file_exists(storage_path()."/scan.txt")){
			$json = json_decode(file_get_contents(storage_path()."/scan.txt"),true);
			$nb_files = $json["nb_files"];
			$nb_errors = $json["nb_errors"];
			$scan = $json["scan"];
		}
		$this->scan_directory(config("app.MUSIC_FOLDER"),$scan, $nb_files, $nb_errors);
		DB::delete("delete from songs where status = ?",array(0));
		
		if (file_exists(storage_path()."/scan.txt")){
			unlink(storage_path()."/scan.txt");
		}
		
		$this->info("Fichiers scannés : " .$nb_files);
		$this->info("Fichiers en erreurs : " .$nb_errors);
	}
	
	
	//Scan recursif des repertoires
	private function scan_directory($dir, $scan = "", &$nb_files = 0, &$nb_errors = 0){
		//Si c est la ou on etait , alors on reprend
		if ($dir == $scan or $scan == ""){
			$scan = "";
			DB::delete("delete from songs where directory = ?",array($dir));
			$files = scandir($dir);
			foreach ($files as $file){
				if ($file != ".." and $file != "."){
					if (is_dir($dir."/".$file)){
						$this->scan_directory($dir."/".$file, $scan, $nb_files, $nb_errors);
					}else{
						foreach (config("app.MUSIC_FILES") as $ext){
							if (stripos($file,".".$ext) !== false){
								//Analyse du fichier
								//echo $file;
								//$audio = new Mp3Info($dir."/".$file,true);
								//echo var_dump($audio);exit();
								
								$song = new Song();
								//On rajoute le fichier en base					
								$song->directory = $dir;
								$song->filename = $file;
								$song->status = -1;
								$song->save();
								
								try{
									MyLaravelMP3::load($dir."/".$file);
									$song->name = MyLaravelMP3::getTitle();
									$song->album = MyLaravelMP3::getAlbum();
									$song->artist = MyLaravelMP3::getArtist();
									$song->status = 1;
									$song->save();
									
									$nb_files++;
								}catch(\Exception $e){
									//Next...
									$nb_errors++;
								}
								
							}
						}
					}
				}
			}
			$json = [];
			$json["updated"] = date("Y-m-d H:i:s");
			$json["nb_files"] = $nb_files;
			$json["nb_errors"] = $nb_errors;
			$json["scan"] = $scan;
			file_put_contents(storage_path()."/scan.txt",json_encode($json));
		}else{
			//Sinon, on reparcourt a partir de l'endroit, ou on etait
			$files = scandir($dir);
			foreach ($files as $file){
				if ($file != ".." and $file != "."){
					if (is_dir($dir."/".$file)){
						$this->scan_directory($dir."/".$file, $scan, $nb_files, $nb_errors);
					}
				}
			}
		}
	}	
}
