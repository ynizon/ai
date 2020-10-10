<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use wapmorgan\Mp3Info\Mp3Info;
use App\Song;
use DB;
use MyLaravelMP3;
use Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\SonosController;
use App\Providers\HelperServiceProvider;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	//Page d accueil
	public function home(Request $request){
		$salle = strtolower($request->input("salle"));
		$radio = $request->input("radio");
		$artist = $request->input("artist");
		$count = Song::count();
		return view("welcome", compact("salle","radio","artist","count"));
	}
	
	//Lance un download depuis Youtube avec youtube-dl, puis renvoie vers le fichier
	public function youtube(Request $request){
		set_time_limit(0);
		$file = storage_path()."/tmp.mp3";
		if (file_exists($file)){
			unlink($file);
		}
		
		Artisan::call('youtube:download',["id"=>$request->input("youtube_id")]);
		
		if (file_exists($file)){			
			header("location: /download_youtube?rnd=".uniqid());
		}
	}
	
	//Recupere un fichier MP3 pour le streamer
	public function mp3(Request $request){		
		$filename = '';
		if ($request->input("url") != ""){
			$filename = $request->input("url");	
		}
		if(file_exists($filename)) {
			header('Content-Disposition: inline;filename="'.basename($filename).'"');
			header('Content-Type: audio/mpeg');
			header('Content-length: '.filesize($filename));
			header('Cache-Control: no-cache');
			header("Content-Transfer-Encoding: chunked"); 
			header('X-Pad: avoid browser bug');

			readfile($filename);
		} else {
			header("HTTP/1.0 404 Not Found");
		}
	}
	
	//Donwload le fichier retour MP3 de Youtube
	public function download_youtube(){
		$file = storage_path()."/tmp.mp3";
		if (file_exists($file)){
			echo file_get_contents($file);
		}
	}	

	//Scan les fichiers
	public function scan(){
		Artisan::call('scan:movies',[]);
		Artisan::call('scan:songs',[]);		
	}
	
	//Des/activation de l'alarme
	public function alarme($mode, Request $request){		
		if ($request->input("password") == config("app.PASSWORD")){
			switch ($mode){
				case "off":
				case "disarmed":
					HelperServiceProvider::setAlarm("disarmed");
					break;
				case "on":
				case "armed":
					HelperServiceProvider::setAlarm("armed");
					break;
				case "partial":
					HelperServiceProvider::setAlarm("partial");
					break;
									
			}
			sleep(5);
		}
		$statuslabel = HelperServiceProvider::getAlarm();
		echo "ALARME EN MODE ".$statuslabel;
	}
	
	//Envoi la couleur de lalarme au lapin
	public function karotz(){		
		$sAlarme = "";
		if (file_exists(storage_path()."/alarme.key")){
			$sAlarme = file_get_contents(storage_path()."/alarme.key");
		}
		$statuslabel = HelperServiceProvider::getAlarm();
		
		$sColor = "000000";
		if ($statuslabel != "disarmed" and $statuslabel != "INCONNU"){
			//Rouge
			$sColor = "FF0000";
		}else{
			//Vert OK
			$sColor = "00FF00";
		}

		//On envoie un ordre de changement qu'en cas de detection de changement du status de l'alarme
		if (trim($statuslabel)!=""){
			//$fp = fopen("ok.txt","a+");
			if ($sAlarme != $statuslabel){
				$json = json_decode(file_get_contents("http://".config("app.IP_KAROTZ")."/cgi-bin/leds?pulse=1&color=".$sColor));
				//echo $sColor;
				sleep(5);
				$json = json_decode(file_get_contents("http://".config("app.IP_KAROTZ")."/cgi-bin/ears_random"));
				
				if (trim($statuslabel) != ""){
					file_put_contents(storage_path()."/alarme.key",$statuslabel);
				}	
			}
		}


		//SI pas en mode alarme, alors 
		//Lit la temperature
		if ($sColor != "FF0000"){
			//Envoie la pluie dans les oreilles
			$url = "https://api.meteo-concept.com/api/forecast/nextHours?token=".config("app.TOKEN_METEO")."&insee=".config("app.ID_VILLE_METEO");
			$json = json_decode(file_get_contents($url),true);
			$url = "http://".config("app.IP_KAROTZ")."/cgi-bin/ears?noreset=1&right=16&left=16";
			if (isset($json["forecast"][0]["weather"])){
				if ($json["forecast"][0]["weather"]<3){
					//Soleil
					$url = "http://".config("app.IP_KAROTZ")."/cgi-bin/ears?noreset=1&right=16&left=16";
					$json = json_decode(file_get_contents("http://".config("app.IP_KAROTZ")."/cgi-bin/leds?pulse=0&color=0015ff"));
				}else{
					if ($json["forecast"][0]["weather"]<10){
						//Nuage
						$url = "http://".config("app.IP_KAROTZ")."/cgi-bin/ears?noreset=1&right=7&left=7";
						$json = json_decode(file_get_contents("http://".config("app.IP_KAROTZ")."/cgi-bin/leds?pulse=0&color=47de95"));
					}else{
						//Pluie
						$url = "http://".config("app.IP_KAROTZ")."/cgi-bin/ears?noreset=1&right=0&left=0";
						$json = json_decode(file_get_contents("http://".config("app.IP_KAROTZ")."/cgi-bin/leds?pulse=0&color=dff01f"));
					}
				}
				sleep(5);
				file_get_contents($url);
			}
		}
	}
		
	//Donne le temps avant le bus
	public function bus($id, Request $request){
	
		if ($request->input("password") == config("app.PASSWORD")){
			//On supprime certains morceaux de phrase
			$id = trim($id);
			$id = str_ireplace("le","",$id);
			$id = str_ireplace("chronobus","",$id);
			$id = str_ireplace("bus","",$id);
			$id = str_ireplace("tramway","",$id);
			$id = str_ireplace("tram","",$id);
			$id = str_ireplace("c","",$id);
			$id = trim($id);
			
			//Trouver coord de mon arret verlaine 
			//https://data.nantesmetropole.fr/api/records/1.0/search/?dataset=244400404_tan-arrets&q=verlaine
			//penser a remplacer . par virgule pour obtenir les codes lieux
			//http://open.tan.fr/ewp/arrets.json/47,23333811/-1,59457807
			//Horaires standards: http://open.tan.fr/ewp/horairesarret.json/VERL1/54/1
			foreach (config("app.BUS") as $number=>$codeLieu){
				if ($number == $id){
					$tan = file_get_contents("http://open.tan.fr/ewp/tempsattente.json/".$codeLieu);

					$infos = json_decode($tan,true);		
					$temps1 = "";
					$temps2 = "";
					$i1=0;
					$i2=0;
					foreach ($infos as $info){
						if ($info["ligne"]["numLigne"] == $id){
							if (stripos($info["temps"],"proche") === false){
								if ($info["sens"] == 1){
									$i1++;
									if ($i1<=2){
										if ($temps1 == ""){
											$temps1 = "Vers ". $info["terminus"]." dans ". str_replace("mn","minutes",$info["temps"]);
										}else{
											$temps1 .= " ou le suivant dans ".str_replace("mn","minutes",$info["temps"]);
										}
									}
								}else{
									$i2++;
									if ($i2<=2){
										if ($temps2 == ""){
											$temps2 = "Vers ". $info["terminus"]." dans ". str_replace("mn","minutes",$info["temps"]);
										}else{
											$temps2 .= " ou le suivant dans ".str_replace("mn","minutes",$info["temps"]);
										}
									}
								}
							}
						}
					}
					if ($temps1 != "" and $temps2 != "" ){
						$temps = $temps1. " et " .$temps2;
					}else{
						$temps = $temps1. " " .$temps2;
					}
					
					
					file_put_contents(storage_path()."/logs/bus.log",$id);
					if ($temps == ""){
						$phrase = "Désolé, il n'y a pas d'autre bus.";
					}else{
						$phrase = "Le bus ".$id." doit passer ".$temps.".";
					}

					$this->tts_to_mp3($phrase);
					$ip = "";
					foreach (config("app.ROOMS") as $xip=>$salle){
						if ($ip == ""){
							$ip = $xip;
						}
					}
					
					$this->tts_to_sonos($ip);
				}
			}
		}
	}
	
	//Text to speech
	public function tts(Request $request){
		$txt = urldecode($request->input("txt"));
		HelperServiceProvider::log();
		$this->tts_to_mp3(utf8_encode($txt));
		$ip = $request->input("ip");
		if ($ip == "" or $ip == "-"){
			foreach (config("app.ROOMS") as $xip=>$salle){
				if ($ip == "" or $ip == "-"){
					$ip = $xip;
				}
			}
		}
		
		$sonos = new SonosPHPController($ip);
		$this->tts_to_sonos($ip);
	}
	
	//Envoie la phrase texte vers un MP3
	private function tts_to_mp3($txt){
		if ($txt != ""){
			putenv("GOOGLE_APPLICATION_CREDENTIALS=".storage_path()."/../google_key.json");			
			$client = new \Google_Client();
			$client->useApplicationDefaultCredentials();
			$client->setSubject(config("app.name"));
			$client->setApplicationName(config("app.name"));
			
			$textToSpeechClient = new TextToSpeechClient();

			//On garde un cache de chaque phrase
			$triFolder = "0.A-TRIER";
			$folder = config("app.MUSIC_FOLDER")."/".$triFolder;
			$file = $folder."/tts-".md5($txt).".mp3";
			if (file_exists(storage_path()."/tts.mp3")){
				unlink(storage_path()."/tts.mp3");
			}
			if (!file_exists($file)){
				$input = new SynthesisInput();
				$input->setText($txt);
				$voice = new VoiceSelectionParams();
				$voice->setLanguageCode(config("app.faker_locale"));
				$audioConfig = new AudioConfig();
				$audioConfig->setAudioEncoding(AudioEncoding::MP3);
				$resp = $textToSpeechClient->synthesizeSpeech($input, $voice, $audioConfig);
				file_put_contents($file, $resp->getAudioContent());
			}
			copy($file,storage_path()."/tts.mp3");
		}
	}
	
	//Envoie le MP3 généré vers le sonos
	private function tts_to_sonos($ip){			
		if ($ip != "" and $ip != "-"){
			//Vers SONOS
			$triFolder = "0.A-TRIER";
			$folder = config("app.MUSIC_FOLDER")."/".$triFolder;
			if (file_exists($folder."/tmp.mp3")){
				unlink($folder."/tmp.mp3");
			}
			if (file_exists(storage_path()."/tts.mp3")){
				copy(storage_path()."/tts.mp3",$folder."/tmp.mp3");
				
				//Lecture sur le sonos
				$sonos = new SonosPHPController($ip);
				$sonos->PlayMessage("x-file-cifs:".config("app.NAS_MUSIC_FOLDER")."/".$triFolder."/tmp.mp3");
			}
		}
	}
	
	//Lit la pluie dans l heure
	public function meteo($id, Request $request){
		if ($request->input("password") == config("app.PASSWORD")){
			//$id = config("ID_VILLE_METEO");
			//$id = 44162;
			$url = "https://api.meteo-concept.com/api/forecast/nextHours?token=".config("app.TOKEN_METEO")."&insee=".$id;
			if ($id != ""){
				$phrase = "";
				$json = json_decode(file_get_contents($url),true);

				if (isset($json["forecast"][0]["weather"])){
					if ($json["forecast"][0]["weather"]<3){
						$phrase = "soleil";
					}else{
						if ($json["forecast"][0]["weather"]<10){
							$phrase = "nuage";
						}else{
							$phrase = "pluie";
						}
					}
					
					$this->tts_to_mp3($phrase);
					
					$ip = "";
					foreach (config("app.ROOMS") as $xip=>$salle){
						if ($ip == "" or $ip == "-"){
							$ip = $xip;
						}
					}
					$this->tts_to_sonos($ip);
				}
			}
		}
	}

	//Set le volume du sonos de 0 à 10
	public function sonosvolume($volume = 5, Request $request){
		$ip = "";
		foreach (config("app.ROOMS") as $xip=>$salle){
			if ($ip == "" or $ip == "-"){
				$ip = $xip;
			}
		}
		if ($ip != "" and $ip != "-"){				
			$sonos = new SonosPHPController($ip);
			$sonos->SetVolume($volume);
		}
	}
}
