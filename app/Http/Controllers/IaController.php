<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use wapmorgan\Mp3Info\Mp3Info;
use Getid3\Getid3;
use App\Song;
use App\Movie;
use App\Kodi;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\SonosController;
use DialogFlow\Client;
use App\Providers\HelperServiceProvider;

use Dialogflow\Action\Responses\MediaObject;
use Dialogflow\Action\Responses\MediaResponse;
use Dialogflow\Action\Responses\Suggestions;
use Dialogflow\WebhookClient;

class IaController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	// Renvoie les infos provenant de l'ia
	public function ia(Request $request){		
		$bModeEchoIA = true;
		try {
			HelperServiceProvider::log();
			
			/*
			$json = json_decode(utf8_encode(file_get_contents('php://input')),true);
			//$json = json_decode((file_get_contents('php://input')),true);
			//$agent = new WebhookClient($json);
			$agent = WebhookClient::fromData($json);
			
			$conv = $agent->getActionConversation();
			$conv->ask('Here you go');
			$conv->ask(
				new MediaResponse(
					MediaObject::create('http://storage.googleapis.com/automotive-media/Jazz_In_Paris.mp3')
					->name('Jazz in Paris')
					->description('A funky Jazz tune')
					->icon('http://storage.googleapis.com/automotive-media/album_art.jpg')
					->image('http://storage.googleapis.com/automotive-media/album_art.jpg')
				)
			);
			//$conv->ask(new Suggestions(['Pause', 'Stop', 'Start over']));
			// $agent->reply($conv);
			exit();
			*/
			
			
			$question = $request->input("question");
			$input_salle = $request->input("salle");
			$update_response = file_get_contents("php://input");			
			
			HelperServiceProvider::log("");
			$update = json_decode(($update_response), true);
			if (empty($update)){
				$update = json_decode(utf8_encode($update_response), true);//Test preprod				
			}
			
			if (isset($update["queryResult"])) {
				$bModeEchoIA = false;
				$question = $update["queryResult"]["queryText"];
				foreach (config("app.ROOMS") as $xip=>$salle){
					$question = str_replace("dans la ".$salle,"",$question);
					$question = str_replace("dans le ".$salle,"",$question);
				}
			}
			
			//echo $question;exit();
			
			if ($request->input("alarme") != ""){
				if ($request->input("alarme") == "on"){
					echo HelperServiceProvider::setAlarm("armed");
				}else{
					echo HelperServiceProvider::setAlarm("disarmed");
				}
				exit();
			}
			
			if (!isset($_SESSION["id"])){
				$_SESSION["id"] = uniqid();
			}
			$sReponse = "";
			$sLog = "";
			$sBody = "";
			$sTodo = "";
			$ip = $request->input("ip");
			$sAlbumOK = "";
			$sFolderOK = "";
			$sFolderAlbum = "";
								
			//$ip = "192.168.1.12";	
			//$ip=SonosPHPController->get_room_coordinator("salle");
			
			
			//////////////////////////////////////////////////////////////////
			//Provenance d'IFTT (Google Home)
			//////////////////////////////////////////////////////////////////
			//Si on vient d IFTT, alors on ecrase la question			
			if ($request->input("iftt") != "" and $request->input("password") == config("app.PASSWORD")) {
				$x = array();
				switch ($request->input("action")) {
					case "tv.pause":
						$x["result"]["action"] = "tv.pause";	
						break;
					case "music.start":
						$x["result"]["action"] = "music.start";
						$x["result"]["parameters"]["music-artist"] = trim(($request->input("iftt")));//utf8_decode ?
						$x["result"]["parameters"]["album"] = "";
						$x["result"]["contexts"] = [];
						$x["result"]["contexts"][0]["parameters"]["music-artist"] = $x["result"]["parameters"]["music-artist"];						
						break;
						
					case "movie.start":
						$x["result"]["action"] = "movie.start";
						$x["result"]["contexts"] = [];
						$x["result"]["parameters"]["tv_show"] = trim(($request->input("iftt")));//utf8_decode ?
						break;
						
					case "story.start":
						$x["result"]["action"] = "story.start";
						$x["result"]["contexts"] = [];
						break;
				}
				
				$sBody = json_encode(HelperServiceProvider::utf8ize($x));
			}
			
			//On recup la salle 
			if (isset($update["queryResult"])) {
				if (isset($update["queryResult"]["parameters"])) {
					if (isset($update["queryResult"]["parameters"]["room"])) {
						if (!empty($update["queryResult"]["parameters"]["room"])) {
							$input_salle = $update["queryResult"]["parameters"]["room"];
							
						}
					}
				}
			}
			
			//On force le salon
			foreach (config("app.ROOMS") as $xip=>$salle){
				if ($ip == ""){
					$ip = $xip;
				}
			}
			
			//Si il y a le parametre, alors on prend la bonne salle
			if ($input_salle != ""){
				foreach (config("app.ROOMS") as $xip=>$salle){
					if (strtolower($salle) == strtolower($input_salle)){
						$ip = $xip;
					}				
				}
			}
			
			$sonos = new SonosPHPController($ip);

			//////////////////////////////////////////////////////////////////
			//Provenance de l'interface WEB
			//////////////////////////////////////////////////////////////////
			//$question = "je voudrais écouter les supers pouvoirs pourris d ' Aldebert";
			if ($question != ""){				
				//file_put_contents(storage_path()."/ia.log",$question);
				$client = new Client(config("app.ACCESS_TOKEN"));

				//Source avec retour vocal https://www.sitepoint.com/how-to-build-your-own-ai-assistant-using-api-ai/
				/*//Exemple
				$query = "regarder mister robot saison 3 épisode 1";
				$query = "je voudrais voir money monster";
				$query = "je voudrais écouter alouette";
				$query = "combien fait 6 plus 4";
				$query = "comment va chuck norris";
				$query = "combien fait 2 + 4";
				*/
				$query = $question;
				$query = urldecode($query);
				$query = str_replace("œ","oe",$query);
				
				//Certains parametres sont mal captés par DialogFlow, alors quand c est le titre d une chanson ou dun film, 
				//on passe cette etape, et on va a lessentiel
				if (stripos($query,"je voudrais")!==false or stripos($query,"dis moi une histoire")!==false){										
					$x = array();
					if (stripos($query,"je voudrais écouter")!==false){
						$x["result"]["action"] = "music.start";
						$x["result"]["parameters"]["music-artist"] = (trim(str_ireplace("je voudrais écouter","",$query)));
						$x["result"]["parameters"]["album"] = "";
						$x["result"]["contexts"] = [];
						$x["result"]["contexts"][0]["parameters"]["music-artist"] = $x["result"]["parameters"]["music-artist"];
					}
					if (stripos($query,"je voudrais voir")!==false){
						$x["result"]["action"] = "movie.start";
						$x["result"]["contexts"] = [];
						$x["result"]["parameters"]["tv_show"] = trim(str_ireplace("je voudrais voir","",$query));
					}
					if (stripos($query,"dis moi une histoire")!==false){
						$x["result"]["action"] = "story.start";
						$x["result"]["contexts"] = [];
						//$x["result"]["parameters"]["story"] = "";
					}
					$sBody = json_encode($x);					
				}else{
					//echo var_dump($query);exit();
					$query = $client->get('query', [
						'query' => urlencode($query),
						'sessionId'=>$_SESSION["id"],
						'lang' => 'fr',
					]);
					$sBody = $query->getBody();
				}
			}
			
			if ($request->input("body") != ""){
				$sBody = $request->input("body");
			}
			
			$tasks = array();
			if (file_exists(storage_path()."/tasks.lst")){
				$tasks = unserialize(file_get_contents(storage_path()."/tasks.lst"));
			}

			if ($sBody != ""){
				$response = json_decode((string) $sBody, true);				
				if (isset($response["result"])){
					if (isset($response["result"]["action"])){
						switch ($response["result"]["action"]){
							case "info":					
								//$docXML->loadXML(file_get_contents("http://www.lemonde.fr/rss/une.xml"));
								$sReponse = "";
								$docXML = new DomDocument();
								
								$flux = array("http://www.lemonde.fr/societe/","http://www.lemonde.fr/international/","http://www.lemonde.fr/planete/");
								foreach ($flux as $f){
									$docXML->loadHTML(file_get_contents($f));
									$xpath = new DomXPath($docXML);
									$list = $xpath->query("//h2");
									
									$sPhrase = "";
									$k=0;
									foreach ($list as $node) {
										if ($k==0){
											$sPhrase = trim($node->nodeValue) . ". ";	
										}
										$k++;
									}
									
									$list = $xpath->query("//h2/span[@class='nb_reactions mgl5']");
									
									$k=0;
									foreach ($list as $node) {
										if ($k==0){
											$sPhrase = str_replace($node->nodeValue,"",$sPhrase);
										}
										$k++;
									}
									
									$sReponse .= $sPhrase;
								}
								
								/*
								$flux = array("http://www.lemonde.fr/planete/rss_full.xml","http://www.lemonde.fr/international/rss_full.xml","http://www.lemonde.fr/societe/rss_full.xml");
								foreach ($flux as $f){
									$docXML->loadXML(file_get_contents($f));
									$xpath = new DomXPath($docXML);
									$list = $xpath->query("//item/title");
									
									$k = 0;						
									foreach ($list as $node) {
										if ($k==0){
											$sReponse .= $node->nodeValue . ". ";	
										}
										
										$k++;
									}
								}
								*/						
								$sReponse = str_replace("\n","",$sReponse);
								$sReponse = str_replace("\r","",$sReponse);
								$sReponse = str_replace('’',"",$sReponse);
								$sReponse = str_replace('»',"",$sReponse);
								$sReponse = str_replace('«',"",$sReponse);
								$sReponse = str_replace('"',"",$sReponse);
								$sReponse = str_replace("'","",$sReponse);
								break;
							case "trash":
								if (date("w") == 6 or date("w") == 0){
									$sReponse = "la déchetterie est fermée aujourd'hui";
								}else{
									$sReponse = "la déchetterie est ouverte de 10 heures à 17 heures 15";
								}
								break;
							case "rain":
								$url = "https://api.meteo-concept.com/api/forecast/nextHours?token=".config("app.TOKEN_METEO")."&insee=".config("app.ID_VILLE_METEO");
								$json = json_decode(file_get_contents($url),true);
								$tab = [
									0=>"Soleil",
									1=>"Peu nuageux",
									2=>"Ciel voilé",
									3=>"Nuageux",
									4=>"Très nuageux",
									5=>"Couvert",
									6=>"Brouillard",
									7=>"Brouillard givrant",
									10=>"Pluie faible",
									11=>"Pluie modérée",
									12=>"Pluie forte",
									13=>"Pluie faible verglaçante",
									14=>"Pluie modérée verglaçante",
									15=>"Pluie forte verglaçante",
									16=>"Bruine",
									20=>"Neige faible",
									21=>"Neige modérée",
									22=>"Neige forte",
									30=>"Pluie et neige mêlées faibles",
									31=>"Pluie et neige mêlées modérées",
									32=>"Pluie et neige mêlées fortes",
									40=>"Averses de pluie locales et faibles",
									41=>"Averses de pluie locales",
									42=>"Averses locales et fortes",
									43=>"Averses de pluie faibles",
									44=>"Averses de pluie",
									45=>"Averses de pluie fortes",
									46=>"Averses de pluie faibles et fréquentes",
									47=>"Averses de pluie fréquentes",
									48=>"Averses de pluie fortes et fréquentes",
									60=>"Averses de neige localisées et faibles",
									61=>"Averses de neige localisées",
									62=>"Averses de neige localisées et fortes",
									63=>"Averses de neige faibles",
									64=>"Averses de neige",
									65=>"Averses de neige fortes",
									66=>"Averses de neige faibles et fréquentes",
									67=>"Averses de neige fréquentes",
									68=>"Averses de neige fortes et fréquentes",
									70=>"Averses de pluie et neige mêlées localisées et faibles",
									71=>"Averses de pluie et neige mêlées localisées",
									72=>"Averses de pluie et neige mêlées localisées et fortes",
									73=>"Averses de pluie et neige mêlées faibles",
									74=>"Averses de pluie et neige mêlées",
									75=>"Averses de pluie et neige mêlées fortes",
									76=>"Averses de pluie et neige mêlées faibles et nombreuses",
									77=>"Averses de pluie et neige mêlées fréquentes",
									78=>"Averses de pluie et neige mêlées fortes et fréquentes",
									100=>"Orages faibles et locaux",
									101=>"Orages locaux",
									102=>"Orages fort et locaux",
									103=>"Orages faibles",
									104=>"Orages",
									105=>"Orages forts",
									106=>"Orages faibles et fréquents",
									107=>"Orages fréquents",
									108=>"Orages forts et fréquents",
									120=>"Orages faibles et locaux de neige ou grésil",
									121=>"Orages locaux de neige ou grésil",
									122=>"Orages locaux de neige ou grésil",
									123=>"Orages faibles de neige ou grésil",
									124=>"Orages de neige ou grésil",
									125=>"Orages de neige ou grésil",
									126=>"Orages faibles et fréquents de neige ou grésil",
									127=>"Orages fréquents de neige ou grésil",
									128=>"Orages fréquents de neige ou grésil",
									130=>"Orages faibles et locaux de pluie et neige mêlées ou grésil",
									131=>"Orages locaux de pluie et neige mêlées ou grésil",
									132=>"Orages fort et locaux de pluie et neige mêlées ou grésil",
									133=>"Orages faibles de pluie et neige mêlées ou grésil",
									134=>"Orages de pluie et neige mêlées ou grésil",
									135=>"Orages forts de pluie et neige mêlées ou grésil",
									136=>"Orages faibles et fréquents de pluie et neige mêlées ou grésil",
									137=>"Orages fréquents de pluie et neige mêlées ou grésil",
									138=>"Orages forts et fréquents de pluie et neige mêlées ou grésil",
									140=>"Pluies orageuses",
									141=>"Pluie et neige mêlées à caractère orageux",
									142=>"Neige à caractère orageux",
									210=>"Pluie faible intermittente",
									211=>"Pluie modérée intermittente",
									212=>"Pluie forte intermittente",
									220=>"Neige faible intermittente",
									221=>"Neige modérée intermittente",
									222=>"Neige forte intermittente",
									230=>"Pluie et neige mêlées",
									231=>"Pluie et neige mêlées",
									232=>"Pluie et neige mêlées",
									235=>"Averses de grêle" ];
								$sReponse = $tab[$json["forecast"][0]["weather"]];
								
								break;
							case "alarm.holiday":
								$iMois = date("m");
								$iAnnee = date("Y");
								$iJour = date("d");
								$sTime = "00:00:01";
								if ($response["result"]["parameters"]["number"] != ""){							
									$iJour = sprintf("%02d",$response["result"]["parameters"]["number"]);
									if (date("d")>$iJour){
										//c est le mois prochain
										$iMois++;
										if ($iMois>12){
											$iMois = "01";
											$iAnnee++;
										}								
									}
									file_put_contents("../alarm_to.txt",$iAnnee."-".$iMois."-".$iJour ." ".$sTime);
								}
								if ($response["result"]["parameters"]["time"] != ""){
									$sTime = $response["result"]["parameters"]["time"];
									file_put_contents("../alarm_to.txt",$iAnnee."-".$iMois."-".$iJour ." ".$sTime);
								}
								sleep(120);
								$sReponse = setAlarm("armed"). " jusqu'au ".$iAnnee."-".$iMois."-".$iJour ." ".$sTime;
								break;
							case "alarm.on":
								$sReponse = setAlarm("armed");
								break;
							case "alarm.partial":
								$sReponse = setAlarm("partial");
								break;
							case "alarm.off":
								$sReponse = setAlarm("disarmed");
								if (file_exists(storage_path()."/alarm_to.txt")){
									unlink(storage_path()."/alarm_to.txt");
								}
								break;
								
							case "smalltalk.dialog":
								$sReponse = "Je n'ai pas compris";	
								break;
								
							case "task.add":
								$sTask = $response["result"]["parameters"]["any"];
								$tasks[$sTask] = $sTask;
								file_put_contents("tasks.lst",serialize($tasks));
								$sReponse = $response["result"]["fulfillment"]["speech"];
								break;
							
							case "task.list":
								$sReponse = $response["result"]["fulfillment"]["speech"]. " " ;
								foreach ($tasks as $sTask){
									$sReponse .= $sTask.", ";
								}
								break;
								
							case "task.delete":
								$sTask = $response["result"]["parameters"]["any"];
								unset($tasks[$sTask]);
								file_put_contents("tasks.lst",serialize($tasks));
								$sReponse = $response["result"]["fulfillment"]["speech"];
								break;
								
							case "input.unknown":
								$sReponse = $response["result"]["fulfillment"]["speech"];
								break;
								
							case "chuck_norris":
								$sContenu = file_get_contents("http://chucknorrisfacts.fr/facts/alea");
								$docXML = new DomDocument();
								$s = "";
								if (@$docXML->loadHTML($sContenu)){
									$xpath = new DOMXPath($docXML);
									$sXpath = "//div[@class='fact']/div[@class='factbody']";
									$lNodes = $xpath->query($sXpath);
									
									foreach ($lNodes as $oNode) {
										if ($s == ""){
											$s = $oNode->firstChild->nodeValue;
										}
									}
								}
								$sReponse = trim($s);		
								break;
								
							case "operation.calculate":
								$iResult = "";
								//echo var_dump($request->input("question"));exit();
								if (isset($response["result"]["parameters"])){
									$iChiffre1 = $response["result"]["parameters"]["number"][0];
									$iChiffre2 = $response["result"]["parameters"]["number1"];
									switch ($response["result"]["parameters"]["Operations-arithmetiques"]){
										case "Addition":
											$iResult = $iChiffre1 + $iChiffre2;
											break;
										case "Soustraction":
											$iResult = $iChiffre1 - $iChiffre2;
											break;
										case "Division":
											if ($iChiffre2 != 0){
												$iResult = "impossible de diviser par zéro";
											}else{
												$iResult = $iChiffre1 / $iChiffre2;	
											}								
											break;
										case "Multiplication":
											$iResult = $iChiffre1 * $iChiffre2;
											break;
									}
								}
								$sReponse = $response["result"]["fulfillment"]["speech"]." " .$iResult;
								break;
							
							//Pause ou lecture
							case "tv.pause":							
								$_Kodi = new Kodi(config("app.KODI_IP_PORT"));
								
								if (!isset($_Kodi->_error)){
									$_Kodi->togglePlayPause();
									$sReponse =  "Je viens d'envoyer l'ordre concernant la pause.";
								}else{
									$sReponse =  $_Kodi->_error;
								}
								
								break;
								
							case "tv.start":
								$iSaison = sprintf("%02d",$response["result"]["parameters"]["saison"][0]);
								$iEpisode = sprintf("%02d",$response["result"]["parameters"]["episode"]);
								$tv_show = $response["result"]["parameters"]["tv_show"];
								$sFolder = $tv_show;
								$sFolder = str_replace("/","",$sFolder);
								$b = true;
								if (file_exists(config("app.SERIE_FOLDER")."/".$sFolder)){
									$tabReps = scandir(config("app.SERIE_FOLDER")."/".$sFolder);
									foreach ($tabReps as $sFolderSaison){
										if ($b and $sFolderSaison == "S".$iSaison){
											if (is_dir(config("app.SERIE_FOLDER")."/".$sFolder."/".$sFolderSaison)){
												$tabFiles = scandir(config("app.SERIE_FOLDER")."/".$sFolder."/".$sFolderSaison);
												foreach ($tabFiles as $file){
													if ($b and strpos(strtolower($file),$iEpisode) !== false){
														//Envoi l'ordre à Kodi
														$b = false;
														$url = config("app.NFS_NAS")."/".$sFolder."/".$sFolderSaison."/".$file;
														
														//$url = 'x:/video/Films/Merci%20Patron.2016.FRENCH.DVDRip.XVid.AC3-Afrique31.avi';
														//file_get_contents(config("app.KODI_URL").'/jsonrpc?request={"jsonrpc":"2.0","id":"1","method":"Player.Open","params":{"item":{"file":"'.$url.'"}}}');
														
														$_Kodi = new Kodi(config("app.KODI_IP_PORT"));
								
														if (!isset($_Kodi->_error)){
															$_Kodi->openFile($url);
															$sReponse = "Lecture de ".$tv_show." saison ".$iSaison." épisode ".$iEpisode;

														}else{
															$sReponse =  $_Kodi->_error;
														}
													}
												}
											}
										}
									}
								}else{
									$sReponse =  "Je n'ai pas trouvé le dossier ".$sFolder .".";
								}
								break;
							
							case "movie.start":								
								$sMovie = $response["result"]["parameters"]["tv_show"];
								$tabMovies = scandir(config("app.MOVIE_FOLDER"));
								$b = true;
								$sReponse =  "Je n'ai pas trouvé le film ".$sMovie .".";
								
								$movies = Movie::where("name","like","%".$sMovie."%")->orderBy("name")->get();
								
								foreach ($movies as $movie){
									if ($b){
										$sFolder = substr(str_ireplace(config("app.MOVIE_FOLDER"),"",$movie->directory),1);
										$filename = $movie->filename;
										
										//Envoi l'ordre à Kodi
										$b = false;
										$url = config("app.NFS_NAS")."/".$movie->directory."/".$filename;
										
										
										//$url = 'x:/video/Films/Merci%20Patron.2016.FRENCH.DVDRip.XVid.AC3-Afrique31.avi';
										//$url = '//192.168.1.5/nas/video/Films/Jackie.2016.FRENCH.720p.BluRay.x264-PKPTRS.mkv';
										//$url = "nfs://192.168.1.5/volume1/video/Films/Jackie.2016.FRENCH.720p.BluRay.x264-PKPTRS.mkv";
												  //nfs://volume1/video/Films/Jackie.2016.FRENCH.720p.BluRay.x264-PKPTRS.mkv
										//echo $url;
										//echo "xx";
										//Playlist.Clear
										//file_get_contents(config("app.KODI_URL").'/jsonrpc?request={"jsonrpc":"2.0","id":"1","method":"Player.Open","params":{"item":{"file":"'.$url.'"}}}');
										$_Kodi = new Kodi(config("app.KODI_IP_PORT"));
								
										if (!isset($_Kodi->_error) or $_Kodi->_error == "No active player."){
											$_Kodi->openFile($url);
											$_Kodi->play();
											
											$sReponse = "Lecture de ".$sMovie;

										}else{
											$sReponse =  $_Kodi->_error;
										}
										$b = false;
									}
								}
								
								//Recherche sans scan prealable
								if ($b){
									for ($z = 1; $z<=2; $z++){
										switch ($z){
											case 1:
												$dir = config("app.MOVIE_FOLDER");
												break;
											case 2:
												$dir = config("app.CARTOON_FOLDER");
												break;
										}
										
										$tabMovies = scandir($dir);
										foreach ($tabMovies as $movie){
											if ($movie != ".." and $movie != "."){
												if ($b and strpos(str_replace("."," ",strtolower($movie)), strtolower($sMovie) ) !== false){	
													//Envoi l'ordre à Kodi
													$b = false;
													$url = config("app.NFS_NAS")."/".$dir."/".$movie;
													$_Kodi = new Kodi(config("app.KODI_IP_PORT"));
										
													if (!isset($_Kodi->_error) or $_Kodi->_error == "No active player."){
														$_Kodi->openFile($url);
														$_Kodi->play();
														$sReponse = "Lecture de ".$sMovie;

													}else{
														$sReponse =  $_Kodi->_error;
													}
													$b = false;
												}
											}
										}
									}
								}
								break;
								
							case "cartoon.start":
								$sMovie = $response["result"]["parameters"]["tv_show"];
								$tabMovies = scandir(config("app.CARTOON_FOLDER"));
								$b = true;
								$sReponse =  "Je n'ai pas trouvé le dessin animé ".$sMovie .".";
								foreach ($tabMovies as $movie){							
									if ($b and strpos(str_replace("."," ",strtolower($movie)), strtolower($sMovie) ) !== false){	
										//Envoi l'ordre à Kodi
										$b = false;
										$url = config("app.NFS_NAS")."/".$dir."/".$movie;
										
										
										//$url = 'x:/video/Films/Merci%20Patron.2016.FRENCH.DVDRip.XVid.AC3-Afrique31.avi';
										//$url = '//192.168.1.5/nas/video/Films/Jackie.2016.FRENCH.720p.BluRay.x264-PKPTRS.mkv';
										//$url = "nfs://192.168.1.5/volume1/video/Films/Jackie.2016.FRENCH.720p.BluRay.x264-PKPTRS.mkv";
												  //nfs://volume1/video/Films/Jackie.2016.FRENCH.720p.BluRay.x264-PKPTRS.mkv
										
										$_Kodi = new Kodi(config("app.KODI_IP_PORT"));
								
										if (!isset($_Kodi->_error) or $_Kodi->_error == "No active player."){
											$_Kodi->openFile($url);
											$_Kodi->play();
											$sReponse = "Lecture de ".$movie;
										}else{
											$sReponse =  $_Kodi->_error;
										}
									}
								}
								
								break;
							
							case "story.start":						
								//Histoire 
								//file_put_contents("coco.txt",$sFolder);
								$sFolder = "0.Histoires";									
								if (file_exists(config("app.MUSIC_FOLDER")."/".$sFolder)){
									$sonos->RemoveAllTracksFromQueue();									
									$sReponse = "";
									
									//Ya til une histoire ou on prend au hasard ?				
									$stories = scandir(config("app.MUSIC_FOLDER")."/".$sFolder);					
									foreach ($stories as $story){
										if ($story != "." and $story != ".."){
											$tabHistoires[] = $story;
										}
									}
									
									$sFileOK = "";
									if (isset($response["result"]["parameters"]["story"])){
										$sFileOK = $response["result"]["parameters"]["story"];
									}else{
										$sFileOK = $tabHistoires[array_rand($tabHistoires, 1)];  
									}
									
									$sTodo .= "oPlaylist = [";

									$url = "";
									$iFichier = 0;
									foreach ($tabHistoires as $file){
										if ($file != "." and $file != ".." ){
											if (substr(strtolower($file),-4) == ".mp3"){												
												if ($sFileOK == "" or $file == $sFileOK){
													$url .= '"'.config("app.MUSIC_FOLDER")."/".$sFolder."/".$file.'" ';

													if ($ip=="-"){
														if ($iFichier > 0){
															$sTodo .= ",";	
														}
														$iFichier++;
														$sTodo .= "{
															title:'".str_replace("'","\'",utf8_encode(basename(substr($file,0,-4))))."',
															mp3:'mp3.php?url=".str_replace("'","\'",config("app.MUSIC_FOLDER")."/".$sFolder."/".$file)."'
														}";
													}else{														
														$sonos->AddURIToQueue("x-file-cifs:".HelperServiceProvider::charSonos(config("app.NAS_MUSIC_FOLDER")."/".$sFolder."/".$file));
													}

													$sFileOK = "------------------";//On ne prend qu'un fichier max
													$sReponse = "ok, je vais jouer l'histoire.";
												}
											}
										}
									}
									$sTodo .= "];mPlayer.setPlaylist(oPlaylist);mPlayer.play();";
									if ($url != ""){
										//$url = '"C:/Program Files (x86)/Winamp/winamp" '.$url;
										//$url = '"d:/SVN_REPOSITORY/wamp/www/ai/winamp.bat" '.$url;
									}
									//echo $url;
									//system  ($url);							
									
									if ($ip!="-"){
										$sonos->Stop();
										$sonos->Play();
									}
									
								}else{
									$sReponse =  "Je n'ai pas trouvé le dossier des histoires.";
								}
								break;
								
							case "music.less":
								$sonos->SetVolume($sonos->getVolume()-10);
								break;
							case "music.more":
								$sonos->SetVolume($sonos->getVolume()+10);
								break;
							case "music.next":
								$sonos->Next();
								break;
							case "music.previous":
								$sonos->Previous();
								break;
							case "album.start":
								$sAlbumOK = $response["result"]["parameters"]["album"];
								$sFolderOK = $response["result"]["contexts"][0]["parameters"]["music-artist"];								
							case "music.start":
								$song = "favorites.m3u";
								if ($sAlbumOK != ""){
									$sAlbum = $sAlbumOK;
								}else{
									$sAlbum = $response["result"]["parameters"]["album"];							
								}
								$sAlbum = utf8_decode($sAlbum);
								if ($sFolderOK != ""){
									$sFolder = $sFolderOK;
								}else{
									$sFolder = $response["result"]["parameters"]["music-artist"];
								}
								
								$sFileOK = "";
								if (isset($response["result"]["contexts"][0]["parameters"]["song"])){
									$sFileOK = $response["result"]["contexts"][0]["parameters"]["song"];
								}
								
								//Artiste introuvable, on cherche + ou -								
								if ($sFolder!=""){
									//$sFolder = utf8_decode($sFolder);	
									if (!file_exists(config("app.MUSIC_FOLDER")."/".$sFolder)){
										$tabDir = scandir(config("app.MUSIC_FOLDER"));
										foreach ($tabDir as $sDir){
											if (strtolower(HelperServiceProvider::sRep($sFolder)) == strtolower(HelperServiceProvider::sRep($sDir))){
												$sFolder = $sDir;
												//$sFolder = utf8_decode($sFolder);
											}
										}
									}

									//Pas trouve, alors on cherche dans la base de donnees
									if (!file_exists(config("app.MUSIC_FOLDER")."/".$sFolder)){
										$sRecherche = str_replace(" d ' ","@",$sFolder);
										$sRecherche = str_replace(" d' ","@",$sRecherche);
										$sRecherche = str_replace(" de ","@",$sRecherche);
										$sRecherche = str_replace(" ' ","'",$sRecherche);
										$tabRecherche = explode("@",$sRecherche);
										
										if (isset($tabRecherche[1])){
											$songs = Song::where("artist","like",$tabRecherche[1])->where("name","like","%".$tabRecherche[0]."%")->orderBy("filename")->get();
											foreach ($songs as $mysong){
												$sFolder = substr(str_ireplace(config("app.MUSIC_FOLDER"),"",$mysong->directory),1);
												$song = $mysong->filename;
											}
										}else{
											$songs = Song::where("name","like","%".$tabRecherche[0]."%")->orderBy("filename")->get();
											foreach ($songs as $mysong){
												$sFolder = substr(str_ireplace(config("app.MUSIC_FOLDER"),"",$mysong->directory),1);
												$song = $mysong->filename;
											}
										}
									}
									
									//Pas trouve, alors on cherche dans divers et compils							
									if (!file_exists(config("app.MUSIC_FOLDER")."/".$sFolder)){
										foreach ($tabDir as $sDir){
											if (substr($sDir,0,1) == "0"){
												$tabDir2 = scandir(config("app.MUSIC_FOLDER")."/".$sDir);										
												foreach ($tabDir2 as $sDir2){
													if (strtolower(HelperServiceProvider::sRep($sFolder)) == strtolower(HelperServiceProvider::sRep($sDir2))){
														$sFolder = $sDir;
														$sAlbum = $sDir2;
														$sAlbum = utf8_decode($sDir2);
														$sFolder = utf8_decode($sFolder);
													}
												}
											}
										}
									}
									
									$sonos->SetPlayMode("NORMAL");
									if (file_exists(config("app.MUSIC_FOLDER")."/".$sFolder)){
										$sonos->RemoveAllTracksFromQueue();
										
										$sReponse = "";
										
										//Ya til des favoris				
										if (file_exists(config("app.MUSIC_FOLDER")."/".$sFolder."/".$song)){											
											if ($ip!="-"){
												$sonos->SetPlayMode("SHUFFLE_NOREPEAT");//Pour les favoris, je preferes aleatoire
												$sonos->AddURIToQueue("x-file-cifs:".HelperServiceProvider::charSonos(config("app.NAS_MUSIC_FOLDER")."/".$sFolder."/".$song));
												$sReponse =  "OK, je vais jouer les favoris.";
											}else{
												$iFichier = 0;
												$sTodo .= "oPlaylist = [";
												$tmp = file_get_contents(config("app.MUSIC_FOLDER")."/".$sFolder."/".$song);
												$files = explode("\n",$tmp);
												
												foreach ($files as $file){
													if (strpos($file,".mp3") !== false){
														$file = str_replace("\\","/",$file);
														if ($iFichier > 0){
															$sTodo .= ",";	
														}
														$iFichier++;
														
														$sTodo .= "{
															title:'".str_replace("'","\'",dirname($file)." > ".utf8_encode(basename(substr($file,0,-4))))."',
															mp3:'/mp3?url=".str_replace("'","\'",config("app.MUSIC_FOLDER")."/".$sFolder."/".trim($file))."'
														}";
													}
												}
												$sTodo .= "];mPlayer.setPlaylist(oPlaylist);mPlayer.play();";	
												
												if ($bModeEchoIA){
													$sReponse =  "OK, je vais jouer les favoris.";
												}
											}	
										
										}else{
											$tabReps = scandir(config("app.MUSIC_FOLDER")."/".$sFolder);					
											$sTodo .= "oPlaylist = [";

											$url = "";
											$iFichier = 0;
											foreach ($tabReps as $sFolderAlbum){
												if ($sFolderAlbum != "." and $sFolderAlbum != ".." ){
													if (stripos($sFolderAlbum,$sAlbum)!==false or $sAlbum == ""){
														if (is_dir(config("app.MUSIC_FOLDER")."/".$sFolder."/".$sFolderAlbum)){
															$tabFiles = scandir(config("app.MUSIC_FOLDER")."/".$sFolder."/".$sFolderAlbum);
															foreach ($tabFiles as $file){
																if (substr(strtolower($file),-4) == ".mp3"){
																	if ($sFileOK == "" or stripos(str_replace("-"," ",$file),$sFileOK)!==false){
																		$url .= '"'.config("app.MUSIC_FOLDER")."/".$sFolder."/".$sFolderAlbum."/".$file.'" ';
																																	
																		if ($ip=="-"){
																			if ($iFichier > 0){
																				$sTodo .= ",";	
																			}
																			$iFichier++;
																			$sTodo .= "{
																				title:'".str_replace("'","\'",$sFolderAlbum." > ".utf8_encode(basename(substr($file,0,-4))))."',
																				mp3:'mp3.php?url=".str_replace("'","\'",config("app.MUSIC_FOLDER")."/".$sFolder."/".$sFolderAlbum."/".$file)."'
																			}";
																		}else{																																						
																			$sonos->AddURIToQueue("x-file-cifs:".HelperServiceProvider::charSonos(config("app.NAS_MUSIC_FOLDER")."/".$sFolder."/".$sFolderAlbum."/".$file));
																		}
																		
																		if ($sFileOK != ""){
																			$sFileOK = "------------------";//On ne prend qu'un fichier max																			
																		}
																		$sReponse = "ok, je vais jouer la musique";
																	}
																}
															}
														}
													}
												}
											}
											
											$sTodo .= "];mPlayer.setPlaylist(oPlaylist);mPlayer.play();";
											if ($url != ""){
												//$url = '"C:/Program Files (x86)/Winamp/winamp" '.$url;
												//$url = '"d:/SVN_REPOSITORY/wamp/www/ai/winamp.bat" '.$url;
											}
											//echo $url;
											//system  ($url);
										}
																				
										if ($ip!="-"){
											$sonos->Stop();
											$sonos->Play();
										}
										
									}else{										
										//Pas trouvé, alors on le telecharge depuis Youtube
										if ($request->input("iftt") != ""){
											$sFolder = $request->input("iftt");
										}
										if ($sFolder != ""){
											$triFolder = "0.A-TRIER";
											$folder = config("app.MUSIC_FOLDER")."/".$triFolder;
											$videoId = "";
											$goodFile = "";
											$title = "";
											
											
											$url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=".urlencode($sFolder)."&maxResults=5&key=".config("app.YOUTUBE_API");
											$json_yt = [];
											//On met un @ pour ne pas diffuser la cle si ca deconne
											if ($content = @file_get_contents($url)){
												$json_yt = json_decode($content,true);
											}
											
											if (isset($json_yt["items"])){
												if (isset($json_yt["items"][0]["id"]["videoId"])){
													$videoId = $json_yt["items"][0]["id"]["videoId"];
													$title = $videoId."-".html_entity_decode ($json_yt["items"][0]["snippet"]["title"]);
													$title = preg_replace("/&#?[a-z0-9]{2,8};/i","",$title); 
												}
											}
											
											
											//Est-il deja telechargé ?
											$files = scandir($folder);
											foreach ($files as $file){
												if (stripos($file,"mp3") !== false and stripos($file,$videoId) !== false){
													$goodFile = $file;
												}
											}
											
											//Il faut le telechargé
											if ($goodFile == "" and $videoId != ""){
												$goodFile=$title.".mp3";
												$url = config("app.URL_YOUTUBEDL_PAGE")."/?youtube_id=".$videoId;
												file_put_contents($folder."/".$goodFile,file_get_contents($url));
												chmod($folder."/".$goodFile,777);										
											}

											if ($goodFile != ""){
												//Seul tmp.mp3 est indexé, donc on l ecrase
												if (file_exists($folder."/tmp.mp3")){
													unlink($folder."/tmp.mp3");
												}
												copy($folder."/".$goodFile, $folder."/tmp.mp3");
												chmod($folder."/".$goodFile, 777);
												chmod($folder."/tmp.mp3", 777);
												$sonos->RemoveAllTracksFromQueue();
												$sonos->AddURIToQueue("x-file-cifs:".HelperServiceProvider::charSonos(config("app.NAS_MUSIC_FOLDER")."/".$triFolder."/tmp.mp3"));
												
												if ($ip!="-"){
													$sonos->Stop();
													$sonos->Play();
												}
											
												$sReponse =  "Je vais jouer ".$sFolder." trouvé sur Youtube.";
											}
										}else{
											$sReponse =  "Je n'ai pas trouvé le dossier ".$sFolder .".";
										}
									}
								}
								break;
								
							case "radio.start":
								if (isset($response["result"]["parameters"])){
									if (isset($response["result"]["parameters"]["radio_name"])){
										$radio_name = $response["result"]["parameters"]["radio_name"];
										foreach (config("app.RADIOS") as $radio=>$url_radio){
											if (strtolower($radio_name) == strtolower($radio)){
												if ($ip!="-"){	
													$sTodo = "Lancement de la radio ".$radio;
													$sonos->RemoveAllTracksFromQueue();
													$sonos->AddURIToQueue("x-rincon-mp3radio://".str_replace("http://","",$url_radio));
												}else{
													$sTodo .= "oPlaylist = [";
													$sTodo .= "{
																title:'".str_replace("'","\'",$radio)."',
																mp3:'".str_replace("'","\'",$url_radio)."'
															}";
													$sTodo .= "];mPlayer.setPlaylist(oPlaylist);";
												}
											}
										}
										
										if ($ip!="-"){
											$sonos->Stop();
											$sonos->Play();
										}
									}
								}
								if ($sTodo == ""){
									$sReponse = "Je n'ai pas trouvé la radio ".$radio_name;
								}
								break;
						}			
					}
				}
			}
		} catch (\Exception $error) {
			$sLog =  $error->getMessage();
		}

		//On affiche rien pour Google
		if ($bModeEchoIA){
			return view ("ia",compact("sReponse","sLog","sTodo"));
		}
	}

	public function export_dialogflow(){
		echo "Ajouter votre export dans le répertoire stockage/export, nous allons écraser les fichiers avec le contenu du répertoire musique ".config("app.MUSIC_FOLDER").". A vous de faire le réimport dans Dialogflow.";
		$tabArtist = [];
		$tabSongs = [];
		$fArtist = storage_path()."/export/entities/artist_entries_fr.json";
		$fSong = storage_path()."/export/entities/song_entries_fr.json";
		
		$artists = scandir(config("app.MUSIC_FOLDER"));
		foreach ($artists as $artist){
			if ($artist != "." and $artist != ".." ){
				$tabArtist[] = ["value"=> $artist,"synonyms"=> [$artist]];
				
				if (is_dir(config("app.MUSIC_FOLDER")."/".$artist)){
					$albums = scandir(config("app.MUSIC_FOLDER")."/".$artist);
					foreach ($albums as $album){
						if ($album != "." and $album != ".." ){
							if (is_dir(config("app.MUSIC_FOLDER")."/".$artist."/".$album)){
								$songs = scandir(config("app.MUSIC_FOLDER")."/".$artist."/".$album);
								foreach ($songs as $song){
									if ($song != "." and $song != ".." ){
										$tabSongs[] = ["value"=> $song,"synonyms"=> [$song]];
									}
								}
							}else{
								$tabSongs[] = ["value"=> $album,"synonyms"=> [$album]];
							}
						}					
					}
				}
			}
		}
		
		$fpa = fopen($fArtist, "w+");
		fputs ($fpa , json_encode($tabArtist));		
		fclose($fpa);
		
		$fps = fopen($fSong, "w+");
		fputs ($fps , json_encode($tabSongs));		
		fclose($fps);
	}
}
