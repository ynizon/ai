<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Movie;

class scanMovies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'scan:movies';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan movies files';

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
		
		DB::update("update movies set status = 0");
		$nb_errors = 0;
		$nb_files = 0;
		$scan = "";
		
		if (file_exists(storage_path()."/scan_movies.txt")){
			$json = json_decode(file_get_contents(storage_path()."/scan_movies.txt"),true);
			$nb_files = $json["nb_files"];
			$nb_errors = $json["nb_errors"];
			$scan = $json["scan"];
		}
		$this->scan_directory(config("app.MOVIE_FOLDER"),$scan, $nb_files, $nb_errors);
		$this->scan_directory(config("app.CARTOON_FOLDER"),$scan, $nb_files, $nb_errors);
		DB::delete("delete from movies where status = ?",array(0));
		
		if (file_exists(storage_path()."/scan_movies.txt")){
			unlink(storage_path()."/scan_movies.txt");
		}
		
		$this->info("Fichiers scannés : " .$nb_files);
		$this->info("Fichiers en erreurs : " .$nb_errors);
	}
	
	
	//Scan recursif des repertoires
	private function scan_directory($dir, $scan = "", &$nb_files = 0, &$nb_errors = 0){
		//Si c est la ou on etait , alors on reprend
		if ($dir == $scan or $scan == ""){
			$scan = "";
			DB::delete("delete from movies where directory = ?",array($dir));
			$files = scandir($dir);
			foreach ($files as $file){
				if ($file != ".." and $file != "."){
					if (is_dir($dir."/".$file)){
						$this->scan_directory($dir."/".$file, $scan, $nb_files, $nb_errors);
					}else{
						foreach (config("app.MOVIES_FILES") as $ext){
							if (stripos($file,".".$ext) !== false){
								//Analyse du fichier
								
								$movie = new Movie();
								//On rajoute le fichier en base					
								$movie->directory = $dir;
								$movie->filename = $file;
								$movie->status = -1;
								$movie->save();
								
								try{
									$movie->name = $this->remove($file);									
									$movie->status = 1;
									$movie->save();
									
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
			file_put_contents(storage_path()."/scan_movies.txt",json_encode($json));
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
	
	private function remove($s){
		foreach (config("app.MOVIES_FILES") as $ext){
			$s = str_ireplace($ext," ",$s);
		}		
		
		$s = str_ireplace("1080p","",$s);
		$s = str_ireplace("720p","",$s);
		$s = str_ireplace("1080","",$s);
		$s = str_ireplace("720","",$s);
		$s = str_ireplace("."," ",$s);
		
		$s = str_ireplace("x264","",$s);$
		$s = str_ireplace("ac3","",$s);
		
		$s = str_ireplace("dts","",$s);
		$s = str_ireplace("hdrip","",$s);
		$s = str_ireplace("vostfr","",$s);
		$s = str_ireplace("multi blueray","",$s);
		$s = str_ireplace("brrip","",$s);
		$s = str_ireplace("dvdrip","",$s);
		$s = str_ireplace("blueray","",$s);
		$s = str_ireplace("bluray","",$s);
		$s = str_ireplace("xvid","",$s);
		$s = str_ireplace("truefrench","",$s);
		$s = str_ireplace("french","",$s);
		$s = str_ireplace("bdrip","",$s);
		
		return $s;
	}
}
