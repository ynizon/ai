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

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Response;
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


	public function mp3test(Request $request){
        $filename = "../public/coco.mp3";

        header("Content-type: audio/mp3");
        header('Content-Disposition: inline;filename="'.basename($filename).'"');
        header('Content-length: '.filesize($filename));
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        header('Accept-Ranges: bytes');
        header("Content-Transfer-Encoding: binary");
        header('Content-Description: File Transfer');
        header('Expires: 0');
        header('Connection: close');
        header('X-Pad: avoid browser bug');
        header("X-XSS-Protection: 0");
        readfile($filename);


        /*
        $headers = array();
        $headers['Content-Type'] = 'audio/mpeg, audio/x-mpeg, audio/x-mpeg-3, audio/mpeg3';
        $headers['Content-Length'] = filesize($filename);
        $headers['Content-Transfer-Encoding'] = 'binary';
        $headers['Accept-Range'] = 'bytes';
        $headers['Cache-Control'] = 'must-revalidate, post-check=0, pre-check=0';
        $headers['Connection'] = 'Keep-Alive';
        $headers['Content-Disposition'] = 'attachment; filename="'.$filename.'"';
        return Response::download($filename, "coco.mp3", $headers);
        */

		/*
        $response = new BinaryFileResponse($filename);
        BinaryFileResponse::trustXSendfileTypeHeader();


        return $response;
		*/
	}

	//Recupere un fichier MP3 pour le streamer
	public function mp3(Request $request){
		$filename = '';
		if ($request->input("url") != ""){
			$url = str_replace('url=','',$request->getQueryString());
            $url = urldecode($url);
			//Fix accent
			$url = str_replace("%5%27","’",$url);
			$url = str_replace("%92","'",$url);
			$url = preg_replace("/%u([0-9a-f]{2,3,4})/i","&#x\\1;",urldecode($url));
			$url = html_entity_decode($url,null,'UTF-8');

			$url2 = ($url);
			$url = utf8_encode($url);


			$filename = rawurldecode($url);//Prends les caracteres speciaux (+)
			$filename2 = rawurldecode($url2);//Prends les caracteres speciaux (+)

			//Fix apostrophe
			$filename = str_replace("\'","'",$filename);
			$filename2 = str_replace("\'","'",$filename2);

		}

		$filename = (config("app.MUSIC_FOLDER").'/'.$filename);//utf8_encode
		$filename2 = (config("app.MUSIC_FOLDER").'/'.$filename2);//utf8_encode

		$dir = dirname($filename);
		/*
		$files = scandir($dir);
		foreach ($files as $file){
			echo $file.'<br/>';
		}
		*/


		/*
		exit();

		$filename2 = '/share/CACHEDEV1_DATA/nas/music/Queen/Queen - Greatest Hits/CD1/[Queen]Good Old-fashioned Lover Boy.mp3';
		if(file_exists($filename)) {
            echo"ok";
        }
		echo $filename;
		echo '<br/>';
		echo $filename2;
        exit();
		*/


		if (strtolower(substr($filename,-4)) == ".mp3"){
			if(file_exists($filename2)) {
				$filename = $filename2;
			}
			if(file_exists($filename)) {
				header('Content-Disposition: inline;filename="'.basename($filename).'"');
				header('Content-Type: audio/mp3');
				header('Content-length: '.filesize($filename));
				header('Cache-Control: no-cache');
				header("Content-Transfer-Encoding: chunked");
				header('X-Pad: avoid browser bug');

				header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        header('Accept-Ranges: bytes');
        header("Content-Transfer-Encoding: binary");
        header('Content-Description: File Transfer');
        header('Expires: 0');
        header('Connection: close');
        header('X-Pad: avoid browser bug');
        header("X-XSS-Protection: 0");

                //header("Content-Description: File Transfer");
                //header("Content-Type: application/octet-stream");
                //header('Content-Disposition: attachment;filename="'.basename($filename).'"');

				readfile($filename);
			} else {
				header("HTTP/1.0 404 Not Found");
				echo "Fichier $filename non trouvé";
			}
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
		try{
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
		}catch(\Exception $e){
			//Do nothing
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

	/* Dupliuqe un calendrier */
	public function duplicateCalendar(){
		// Get the API client and construct the service object.
		//Retrieve first calendar
		$calendarId = 'primary';
		$client = $this->getClient(base_path() . "/config/token_ynizon.json");
		$service = new Google_Service_Calendar($client);
		$optParams = array(
		  'maxResults' => 20,
		  'orderBy' => 'startTime',
		  'singleEvents' => true,
		  'timeMin' => date('c'),
		);
		$results = $service->events->listEvents($calendarId, $optParams);
		$events = $results->getItems();

		//On supprime tous les events enregistrés
		$client_dest = $this->getClient(base_path() . "/config/token_music.json");
		$service_dest = new Google_Service_Calendar($client_dest);
		$results_dest = $service_dest->events->listEvents($calendarId, $optParams);
		$events_dest = $results_dest->getItems();
		foreach ($events_dest as $event) {
			$service_dest->events->delete($calendarId,$event->id);
		}
		//On rajoute ceux d'origine
		foreach ($events as $event) {
			$attributes = ['summary','location','start','end'];
			$new_event = new Google_Service_Calendar_Event(array(
			  'summary' => $event->getSummary(),
			  'location' => $event->getLocation(),
			  'description' => $event->getDescription(),
			  'start' => $event->getStart(),
			  'end' => $event->getEnd()
			  )
			);

			$service_dest->events->insert($calendarId, $new_event);
			$date = $event->getStart()->getDate();
			if ($date == ""){
				$date = $event->getStart()->getDateTime();
			}
			echo  $date. " - ".$new_event->getSummary()."<br/>";
		}
	}

	private  function getClient($tokenPath = 'token.json')
	{
		$client = new Google_Client();
		$client->setApplicationName('Google Calendar API PHP Quickstart');
		$client->setScopes(Google_Service_Calendar::CALENDAR);
		$client->setAuthConfig(base_path() . "/config/credentials.json");
		$client->setAccessType('offline');
		$client->setPrompt('select_account consent');

		// Load previously authorized token from a file, if it exists.
		// The file token.json stores the user's access and refresh tokens, and is
		// created automatically when the authorization flow completes for the first
		// time.
		//$tokenPath = "";
		if (file_exists($tokenPath)) {
			$accessToken = json_decode(file_get_contents($tokenPath), true);
			$client->setAccessToken($accessToken);
		}

		// If there is no previous token or it's expired.
		if ($client->isAccessTokenExpired()) {
			// Refresh the token if possible, else fetch a new one.
			if ($client->getRefreshToken()) {
				$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			} else {
				// Request authorization from the user.
				$authUrl = $client->createAuthUrl();
				printf("Open the following link in your browser:\n%s\n", $authUrl);
				print 'Enter verification code: ';
				$authCode = trim(fgets(STDIN));

				// Exchange authorization code for an access token.
				$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
				$client->setAccessToken($accessToken);

				// Check to see if there was an error.
				if (array_key_exists('error', $accessToken)) {
					throw new Exception(join(', ', $accessToken));
				}
			}
			// Save the token to a file.
			if (!file_exists(dirname($tokenPath))) {
				mkdir(dirname($tokenPath), 0700, true);
			}
			file_put_contents($tokenPath, json_encode($client->getAccessToken()));
		}
		return $client;
	}


	public function radio($artist = '',Request $request){
		//set variables
		$settings = array(
			"name" => "Radio",
			"genre" => "Radio",
			"url" => config("app.url"),
			"bitrate" => 96,
			"buffer_size" => 16384,
			"max_listen_time" => 14400,
			"randomize_seed" => 31337
		);

//		set_time_limit(0);
		$getID3 = new \getID3();

		//load playlist
		//$files = json_decode($request->input("files"));
        $filenames = [];
        $sFolder = $artist;
        $fileOK = "";
        $cover = "";
        if ($sFolder!="") {
            $song = "favorites.m3u";
            //$sFolder = utf8_decode($sFolder);
            if (!file_exists(config("app.MUSIC_FOLDER") . "/" . $sFolder)) {
                //On prend la casse
                foreach (scandir(config("app.MUSIC_FOLDER")) as $dirx){
                    if (stripos($dirx,$artist) !== false){
                        $sFolder = $dirx;
                    }
                }
            }

            if (file_exists(config("app.MUSIC_FOLDER")."/".$sFolder)){
                $dir = config("app.MUSIC_FOLDER").'/'.$sFolder.'/';

                //Ya til des favoris
                if (file_exists(config("app.MUSIC_FOLDER")."/".$sFolder."/".$song)){
                    $tmp = file_get_contents(config("app.MUSIC_FOLDER")."/".$sFolder."/".$song);
                    $files = explode("\r\n",$tmp);

                    foreach ($files as $file){
                        if ($file != "" && stripos($file,"#EXT") === false && stripos($file,".mp3") !== false){
                            $file = str_replace("\\","/",$file);
                            $filenames[] = utf8_encode($file);
                        }
                    }
                }else{
                    $this->scanDir4mp3($dir, $dir, $filenames);//TODO A TESTER
                }
            }
        }

        /*$sFolder = "Cali/Cali - Menteur";
		$dir = config("app.MUSIC_FOLDER")."/".$sFolder."/";
		$filenames = array_slice(scandir($dir), 2);
        */

/*
$dir = config("app.MUSIC_FOLDER")."/0.WAV/";
$filenames = [];
$filenames[] = 'BABYTALK.mp3';
$filenames[] =  'BATH2.mp3';
$filenames[] =  'BANJO1.mp3';
*/

		foreach($filenames as $filename) {
			$id3 = $getID3->analyze($dir.$filename);

			if(substr($filename,-3) == "mp3") {
				try {
					$artist = isset($id3["tags"]["id3v2"]["artist"][0]) ? $id3["tags"]["id3v2"]["artist"][0] : '';
					$title = isset($id3["tags"]["id3v2"]["title"][0]) ? $id3["tags"]["id3v2"]["title"][0] : '';

                    $playfile = array(
                        "filename" => $filename,
                        "filesize" => $id3["filesize"],
                        "playtime" => $id3["playtime_seconds"],
                        "audiostart" => $id3["avdataoffset"],
                        "audioend" => $id3["avdataend"],
                        "audiolength" => $id3["avdataend"] - $id3["avdataoffset"],
                        "artist" => $artist,
                        "title" => $title
                    );

                    if (empty($playfile["artist"]) || empty($playfile["title"])){
                        $infos = explode(" - ", substr($playfile["filename"], 0, -4));
						if (isset($infos[0])){
							$playfile["artist"] = $infos[0];
						}
						if (isset($infos[1])){
							$playfile["title"] = $infos[1];
						}
					}

                    $playfiles[] = $playfile;
                }catch(\Exception $e){
			        echo var_dump($id3);exit();

					echo $filename.$e->getMessage();//.var_dump($id3);
			        //exit();
                }
			}
		}

		//user agents
		$icy_data = false;
		foreach(array("iTunes", "VLC", "Winamp") as $agent)
			if(substr($_SERVER["HTTP_USER_AGENT"], 0, strlen($agent)) == $agent)
				$icy_data = true;

		//set playlist
		$start_time = microtime(true);
		srand($settings["randomize_seed"]);
		shuffle($playfiles);

		//sum playtime
		$total_playtime = 0;
		foreach($playfiles as $playfile)
			$total_playtime += $playfile["playtime"];

		//calculate the current song
		$play_sum = 0;
		$play_pos = $start_time % $total_playtime;
		foreach($playfiles as $i=>$playfile) {
			$play_sum += $playfile["playtime"];
			if($play_sum > $play_pos)
				break;
		}
		$track_pos = ($playfiles[$i]["playtime"] - $play_sum + $play_pos) * $playfiles[$i]["audiolength"] / $playfiles[$i]["playtime"];

		//output headers
		header("Content-type: audio/mpeg");
		if($icy_data) {
			header("icy-name: ".$settings["name"]);
			header("icy-genre: ".$settings["genre"]);
			header("icy-url: ".$settings["url"]);
			header("icy-metaint: ".$settings["buffer_size"]);
			header("icy-br: ".$settings["bitrate"]);
			header("Content-Length: ".$settings["max_listen_time"] * $settings["bitrate"] * 128); //suppreses chuncked transfer-encoding
		}

		//play content
		$o = $i;
		$old_buffer = substr(file_get_contents($dir.$playfiles[$i]["filename"]), $playfiles[$i]["audiostart"] + $track_pos, $playfiles[$i]["audiolength"] - $track_pos);
		while(time() - $start_time < $settings["max_listen_time"]) {
			$i = ++$i % count($playfiles);
			$buffer = $old_buffer.substr(file_get_contents($dir.$playfiles[$i]["filename"]), $playfiles[$i]["audiostart"], $playfiles[$i]["audiolength"]);

			for($j = 0; $j < floor(strlen($buffer) / $settings["buffer_size"]); $j++) {
				$metadata = "";
				if($icy_data) {
					if($i == $o + 1 && ($j * $settings["buffer_size"]) <= strlen($old_buffer))
						$payload = "StreamTitle='{$playfiles[$o]["artist"]} - {$playfiles[$o]["title"]}';".chr(0);
					else
						$payload = "StreamTitle='{$playfiles[$i]["artist"]} - {$playfiles[$i]["title"]}';".chr(0);

					$metadata = chr(ceil(strlen($payload) / 16)).$payload.str_repeat(chr(0), 16 - (strlen($payload) % 16));
				}
				echo substr($buffer, $j * $settings["buffer_size"], $settings["buffer_size"]).$metadata;
			}
			$o = $i;
			$old_buffer = substr($buffer, $j * $settings["buffer_size"]);
		}
	}

    public function scanDir4mp3($dir, $target, &$filenames) {
	    if(is_dir($target)){
            /*$fp = fopen("coco.txt","a+");
            fputs($fp,$target."\n");
            fclose($fp);*/
            $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned
            foreach( $files as $file )
            {
                if (substr($file,-3) == "mp3") {
                    $filenames[] = utf8_encode(str_replace($dir,'','/'.$target."/".$file));
                }

                $this->scanDir4mp3( $dir, $file,$filenames );
            }
        }
    }

    /**
     * Copie et lance le fichier
     * @param Request $request
     */
    public function sendtosonos(Request $request){
        $id = $request->input("id");
        $name = $request->input("name");
        $name = str_replace(" ","-",$name);
        $name = preg_replace("/[^a-z0-9\_\-\.]/i", '', $name);

        if (empty($id) or empty($name)){
            echo "Arguments id et name sont obligatoires";
        }else {
            $file = config("app.MUSIC_FOLDER") . '/0.A-TRIER/' . $name . '.mp3';
            if (!file_exists($file)) {
                Artisan::call('youtube:download', ["id" => $id]);
                copy(storage_path().'/tmp.mp3', config("app.MUSIC_FOLDER") . '/0.A-TRIER/' . $name . '.mp3');
            }

            if (file_exists($file)) {
                copy($file, config("app.MUSIC_FOLDER") . '/0.A-TRIER/tmp.mp3');
            }
            $file = config("app.MUSIC_FOLDER") . '/0.A-TRIER/tmp.mp3';

            if (file_exists($file)) {
                $ip = "192.168.1.15";//Salon par defaut
                $sonos = new SonosPHPController($ip);
                $sonos->SetPlayMode("NORMAL");
                $sonos->RemoveAllTracksFromQueue();
                $sonos->AddURIToQueue("x-file-cifs:" . HelperServiceProvider::charSonos(config("app.NAS_MUSIC_FOLDER") . '/0.A-TRIER/tmp.mp3'));
                $sonos->Stop();
                $sonos->Play();
                return view("close");
            }
        }
    }

	public function restartNasHdmi(){
		echo shell_exec("/etc/init.d/HD_Station.sh restart");
	}

    public function explorer(Request $request){
        $root = "";
        $dir = $request->input("dir");
        $dir = str_replace("../","",$dir);
        
        if ($dir == ".." or $dir == "."){
            $dir = "";
        }
        if (!empty($dir)){
            $root .= urldecode($dir)."/";
        }
        
        $dirsTmpNotOrder = scandir(config("app.MUSIC_FOLDER")."/".$root);

        $nbmp3 = 0;
        $dirs = [];
		
		
		$dirsTmp = [];
		foreach ($dirsTmpNotOrder as $file){
			if (stripos($file,".txt") === false) {
                $fileTmp = str_replace("-"," ",$file);
                $fileTmp = str_replace("_"," ",$file);
                $keys = explode(" ",$fileTmp );                
                
                if (is_dir(config("app.MUSIC_FOLDER")."/".$root."/".$file)){
                    $dirsTmp[$file] = $file;
                }else{
                    $numKey = 0;
                    
                    foreach ($keys as $idx => $key){
                        if ((int) $key >0){
                            $numKey = $idx;
                        }
                    }
                    
                    if (isset($keys[$numKey])){
                        $dirsTmp[$keys[$numKey]] = $file;
                    }else{
                        $dirsTmp[$file] = $file;
                    }                    
                }
            }
		}		

		ksort($dirsTmp);		
		
        foreach ($dirsTmp as $dir){
            if ($dir == "..") {
                $path = explode("/", $root);
                array_pop($path);
                array_pop($path);
                $dir = implode("/", $path);
                if ($root != ""){
                    $dirs[$dir] = ["icon" => "folder", "dir" => ".."];
                }
            } else {
                if ($dir == ".") {
                    if ($root != "") {
                        $icon = "play";
                        $dirs[$root . $dir] = ["dir" => "", "icon" => $icon];
                    }
                } else {
                    if (is_dir(config("app.MUSIC_FOLDER") . "/" . $root . "/" . $dir)) {
                        $icon = "folder";
                        $dirs[$root . $dir] = ["dir" => $dir, "icon" => $icon];
                    }else{
                        if (stripos($dir,".mp3") !== false) {
                            $icon = "file";
                            $nbmp3++;
                            $dirs[$root . $dir] = ["dir" => $dir, "icon" => $icon];
                        }
                    }
                }
            }
        }

        return view("explorer", compact('root','dirs','nbmp3'));
    }
}
