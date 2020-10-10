<?php

namespace App\Providers;

use View;
use Illuminate\Support\ServiceProvider;
use Request;
abstract class HelperServiceProvider extends ServiceProvider
{
	
	/* Convertit une date de 2016-01-31 a 01/2016 */
	public static function showMonth($sDate, $bJustDate = false)
	{
		if ($sDate == "" or $sDate == "1970-01-01"){
			return "";
		}else{
			if ($bJustDate){
				return substr($sDate,5,2)."/".substr($sDate,0,4);
			}else{
				return substr($sDate,5,2)."/".substr($sDate,0,4).substr($sDate,10);
			}
		}
	}
	
	/* Convertit une date de 2016-01-31 a 31/01/2016 */
	public static function formatDateFR($sDate, $bJustDate = false)
	{
		if ($sDate == "" or $sDate == "1970-01-01"){
			return "";
		}else{
			if ($bJustDate){
				return substr($sDate,8,2)."/".substr($sDate,5,2)."/".substr($sDate,0,4);
			}else{
				return substr($sDate,8,2)."/".substr($sDate,5,2)."/".substr($sDate,0,4).substr($sDate,10);
			}
		}
	}
	
	/* Convertit une date de 31/01/2016 a 2016-01-31*/
	public static function formatDateSQL($sDate)
	{
		return substr($sDate,6,4)."-".substr($sDate,3,2)."-".substr($sDate,0,2);
	}
	
	/* Renvoie une date pour les calendriers JS avec new Date(2018,12,31) a partir de 31-12-2018 */
	public static function formatDateCalendarJS($sDate){
		$iJour = substr($sDate,0,2);
		$iMois = substr($sDate,3,2);
		$iAnnee = substr($sDate,6,4);
		return "new Date(".$iAnnee.",".$iMois.",".$iJour.")" ;
	}
	
	public static function formatDureeHeureMin($iSecondes)
	{
		$iHeure = 0;
		$iMin = 0;
		$iSec = 0;
		while ($iSecondes>3600){
			$iHeure++;
			$iSecondes  = $iSecondes - 3600;
		}
		while ($iSecondes>60){
			$iMin++;
			$iSecondes  = $iSecondes - 60;
		}
		$iSec = $iSecondes;
		
		$sHeure = $iHeure;
		if (strlen($iHeure)<2){
			$sHeure = "0".$iHeure;
		}
		$sMin = $iMin;
		if (strlen($iMin)<2){
			$sMin = "0".$iMin;
		}
		
		return $sHeure.":".$sMin;
	}
	
	public static function formatDureeHeureMinSec($iSecondes)
	{
		$iHeure = 0;
		$iMin = 0;
		$iSec = 0;
		while ($iSecondes>3600){
			$iHeure++;
			$iSecondes  = $iSecondes - 3600;
		}
		while ($iSecondes>60){
			$iMin++;
			$iSecondes  = $iSecondes - 60;
		}
		$iSec = $iSecondes;
		
		$sHeure = $iHeure;
		if (strlen($iHeure)<2){
			$sHeure = "0".$iHeure;
		}
		$sMin = $iMin;
		if (strlen($iMin)<2){
			$sMin = "0".$iMin;
		}
		$sSec = $iSec;
		if (strlen($iSec)<2){
			$sSec = "0".$iSec;
		}
		return $sHeure.":".$sMin.":".$sSec;
	}
	
	
	/* Renvoie le chiffre avec les bons separateurs */
	public static function showNumber($sNumber, $sCurrency, $iVirgule = 0){	
		$r = number_format($sNumber, $iVirgule, ',', ' ');
		if ($sCurrency != ""){
			$r .= " " .$sCurrency;
		}
		return $r;
	}
	
	 /**
     * Affiche un nombre avec les bons séparateurs (>FR) 10 000.00
     * @param unknown_type $s
     */
    public static function num($number, $bEuro = true, $iDecimale = 2){
    	if ($number == ""){ 
			$number = 0;
		}
		if (round($number,$iDecimale) == 0){
			$number = 0;
		}
		$s = number_format($number, $iDecimale, ',', ' ');
		if ($bEuro){
			$s .= " &euro;";
		}
		return $s;
    }
	
	/**
	 * Renvoie le nom du mois
	 * @param unknown_type $iMois
	 */
	public static function getMois($iMois, $bPrefixe = false) {
		$iMois = (int) $iMois;
		$sMois = "";
		$sPrefix = "de ";
		switch ($iMois){
			case 0:
				$sMois = "Décembre";
				break;
			case 1:
				$sMois = "Janvier";
				break;
			case 2:
				$sMois = "Février";
				break;
			case 3:
				$sMois = "Mars";
				break;
			case 4:
				$sPrefix = "d'";
				$sMois = "Avril";
				break;
			case 5:
				$sMois = "Mai";
				break;
			case 6:
				$sMois = "Juin";
				break;
			case 7:
				$sMois = "Juillet";
				break;
			case 8:
				$sPrefix = "d'";
				$sMois = "Août";
				break;
			case 9:
				$sMois = "Septembre";
				break;
			case 10:
				$sPrefix = "d'";
				$sMois = "Octobre";
				break;
			case 11:
				$sMois = "Novembre";
				break;
			case 12:
				$sMois = "Décembre";
				break;
			case 13:
				$sMois = "Janvier";
				break;
		}	

		if (!$bPrefixe){
			return $sMois;
		}else{
			return $sPrefix . $sMois;
		}
	}
	
	/* Effectue le total d'un champ d'un tableau */
	public static function sum($tab, $field){
		$r = 0;
		foreach ($tab as $t){
			$r = $r + $t[$field];
		}
		return $r;
	}
	
	/* Effectue la moyenne d'un champ d'un tableau */
	public static function average($tab, $field){
		$k=0;
		$r = 0;
		foreach ($tab as $t){
			$r = $r + $t[$field];
			$k++;
		}
		if ($k>0){
			return ($r/$k);
		}else{
			return $r;	
		}
		
	}
	
	/* Remplace les accents */
	public static function remove_accents($str, $charset='utf-8'){
		$str = htmlentities($str, ENT_NOQUOTES, $charset);
		
		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
		$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
		
		return $str;
	}
	
	/**
	Obtenir le domaine a partir d'une url
	*/
	public static function getdomain($url) {
		
	    preg_match (
	        "/^(http:\/\/|https:\/\/)?([^\/]+)/i",
	        $url, $matches
	    );
		
		$host = "";
		if (isset($matches[2])){
			$host = $matches[2]; 
		}
	    preg_match (
	        "/[^\/]+\.[^\.\/]+$/", 
	        $host, $matches
	    );
	    
		if (isset($matches[0])){
			return strtolower("{$matches[0]}");
		}else{
			return "";
		}
	} 
	
	// Renomme lurl .. en HP
	public static function renameurl($url){
		$shorturl = $url;
		if ($shorturl == "." or $shorturl == ".."){
			$shorturl = "-HP-";
		}
		return $shorturl;
	}
	
	// Ecrit une duree au format Heure:minute:seconde
	public static function formatTime($iDuree){
		$iDuree = round($iDuree,0);
		$iHeure = 0;
		$iMin = 0;
		$iSec = 0;
		$iHeure = round(floor($iDuree/3600),0);
		$iDuree = $iDuree - $iHeure*3600;
		$iMin = round(floor($iDuree/60),0);
		$iDuree = $iDuree - $iMin*60;
		$iSec = $iDuree;
		
		$result = sprintf("%02d",$iHeure).":".sprintf("%02d",$iMin).":".sprintf("%02d",$iSec);
		return $result;
	}
	
	//Malgré l'encodage, certain titres ont mal etes importés dans last fm, on les corrige comme ca
	public static function strangeChar($s){
		$s = str_replace("ã©","é",$s);
		$s = str_replace("Ã©","é",$s);
		$s = str_replace("Ă©","é",$s);
		$s = str_replace("Ã¨","è",$s);
		$s = str_replace("Ã«","ë",$s);
		return $s;
	}
	
	public static function replaceUpperChar($s){
		$s = str_replace("à","A",$s);
		$s = str_replace("â","A",$s);
		$s = str_replace("é","E",$s);
		$s = str_replace("è","E",$s);
		$s = str_replace("ê","E",$s);		
		$s = str_replace("ï","I",$s);
		$s = str_replace("î","I",$s);		
		$s = str_replace("ö","O",$s);
		$s = str_replace("ô","O",$s);
		$s = str_replace("œ","OE",$s);		
		$s = str_replace("ù","U",$s);
		$s = str_replace("û","U",$s);
		$s = str_replace("ü","U",$s);
		$s = strtoupper($s);

		return $s;
	}
	
	public static function extrait($string, $start = 150, $end = 0, $sep = ' ...'){
		$extrait = substr($string,0,$start);
		$extrait = substr($string,0,strrpos($extrait,' ')).$sep;
		$extrait2 = strstr(substr($string, -$end,$end),' ');
		return str_replace("\n","",$extrait.' '.$extrait2);
	}
	
	public static function sRep($s){
		$s = str_replace(" ","",$s);
		$s = str_replace("-","",$s);
		$s = str_replace("_","",$s);
		$s = str_replace(".","",$s);
		$s = str_replace("'","",$s);
		$s = str_replace("é","e",$s);
		$s = str_replace("è","e",$s);
		$s = str_replace("à","a",$s);	
		
		return $s;
	}

	//Initialisation des tokens pour l'alarme
	public static function initAlarm(&$siteid = 0,&$token = ""){
		// Authentification
		/*
		$auth = null;
		if (!file_exists(storage_path()."/token.txt")){
			//1er init
			$curl = curl_init( 'https://api.myfox.me/oauth2/token' );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
					'grant_type' => 'password',
					'client_id' => config("app.ALARM_CLIENT_ID"),
					'client_secret' => config("app.ALARM_CLIENT_SECRET"),
					'username' => config("app.ALARM_USERNAME"),
					'password' => config("app.ALARM_PASSWORD")
			) );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
			$auth = curl_exec( $curl );
			file_put_contents(storage_path()."/token.txt",$auth);
		}else{
			$tokenFile = json_decode(file_get_contents(storage_path()."/token.txt"),true);
			if (isset($tokenFile["access_token"])){
				$curl = curl_init( 'https://api.myfox.me/oauth2/token' );
				curl_setopt( $curl, CURLOPT_POST, true );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
						'grant_type' => 'refresh_token',
						'refresh_token' => $tokenFile["access_token"]
				) );
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
				$auth = curl_exec( $curl );
				file_put_contents(storage_path()."/token.txt",$auth);
			}else{
				//Erreur
				echo var_dump($tokenFile);
			}
		}
		if ($auth != null){
			$secret = json_decode($auth);	
			$token = $secret->access_token;

			// Obtention du site ID
			if ($siteid==0){
				if (file_exists(storage_path()."/site_id.txt")){
					$siteid = file_get_contents(storage_path()."/site_id.txt");
				}
				$api_url = "https://api.myfox.me:443/v2/client/site/items?access_token=" . $token;
				$requete = @file_get_contents($api_url);
				$json_result = json_decode($requete,true);
				$siteid = $json_result["payload"]["items"][0]["siteId"];    // On prend en compte le premier site de la liste, si plusieur Sites remplacer la valeur 0 par 1 2 3.....(non testé), le site ID peut etre trouvé dans votre compte, sa valeur ne change pas
				file_put_contents(storage_path()."/site_id.txt",$siteid);
			}
		}
		*/
	}
	
	//Renvoi letat de l'alarme
	public static function getAlarm(){		
		$statuslabel = "INCONNU";
		$siteid = 0;
		$token = "";
		
		// Activation par timing (sans appel API)
		$time_end = "06:45";
		switch (date("w")){
			default:
				$time_start = "00:01";				
				break;
			case 6:
			case 0:
				$time_start = "02:00";
				break;
		}
		
		if (date("H:i")>=$time_start and date("H:i")<$time_end){
			$statuslabel = "armed";
		}else{
			$statuslabel = "disarmed";
		}
		
		
		/*
		self::initAlarm($siteid,$token);
		if ($token != "" and $siteid != ""){
			// Obtenir Etat de l'alarme
			$api_url2 = "https://api.myfox.me:443/v2/site/" .$siteid. "/security?access_token=" . $token;
			$requete2 = @file_get_contents($api_url2);
			$json_result2 = json_decode($requete2,true);
			$statusvalue = $json_result2["payload"]["status"];
			$statuslabel = $json_result2["payload"]["statusLabel"];

			//file_get_contents("http://home.gameandme.fr/karotz.php?alarme=".$gAlarme);
		}
		*/
		return $statuslabel;
	}
	
	//Met letat de l'alarme a (armed/partial/disarmed)
	public static function setAlarm($gAlarme){		
		$siteid = 0;
		$token = "";
		/*
		self::initAlarm($siteid,$token);
		if ($token != "" and $siteid != ""){
			//On envoie letat a mettre en place
			$api_url3 = "https://api.myfox.me:443/v2/site/" .$siteid. "/security/set/".$gAlarme."?access_token=" . $token;
			//echo "ALARME ".$gAlarme." <br/>";
			$curl2 = curl_init( $api_url3 );
			curl_setopt( $curl2, CURLOPT_POST, true );
			curl_setopt( $curl2, CURLOPT_RETURNTRANSFER, 1);
			$return = curl_exec( $curl2 );
		}
		*/
	}
	
	//Encode certains caracteres pour les URL SONOS
	public static function charSonos($s){
		$s = str_replace("&","%26",$s);
		return $s;
	}
	
	//Conversion UTF8 Ansi
	public static function utf8_ansi($valor='') {
		$utf8_ansi2 = array(
		"\u00c0" =>"À",
		"\u00c1" =>"Á",
		"\u00c2" =>"Â",
		"\u00c3" =>"Ã",
		"\u00c4" =>"Ä",
		"\u00c5" =>"Å",
		"\u00c6" =>"Æ",
		"\u00c7" =>"Ç",
		"\u00c8" =>"È",
		"\u00c9" =>"É",
		"\u00ca" =>"Ê",
		"\u00cb" =>"Ë",
		"\u00cc" =>"Ì",
		"\u00cd" =>"Í",
		"\u00ce" =>"Î",
		"\u00cf" =>"Ï",
		"\u00d1" =>"Ñ",
		"\u00d2" =>"Ò",
		"\u00d3" =>"Ó",
		"\u00d4" =>"Ô",
		"\u00d5" =>"Õ",
		"\u00d6" =>"Ö",
		"\u00d8" =>"Ø",
		"\u00d9" =>"Ù",
		"\u00da" =>"Ú",
		"\u00db" =>"Û",
		"\u00dc" =>"Ü",
		"\u00dd" =>"Ý",
		"\u00df" =>"ß",
		"\u00e0" =>"à",
		"\u00e1" =>"á",
		"\u00e2" =>"â",
		"\u00e3" =>"ã",
		"\u00e4" =>"ä",
		"\u00e5" =>"å",
		"\u00e6" =>"æ",
		"\u00e7" =>"ç",
		"\u00e8" =>"è",
		"\u00e9" =>"é",
		"\u00ea" =>"ê",
		"\u00eb" =>"ë",
		"\u00ec" =>"ì",
		"\u00ed" =>"í",
		"\u00ee" =>"î",
		"\u00ef" =>"ï",
		"\u00f0" =>"ð",
		"\u00f1" =>"ñ",
		"\u00f2" =>"ò",
		"\u00f3" =>"ó",
		"\u00f4" =>"ô",
		"\u00f5" =>"õ",
		"\u00f6" =>"ö",
		"\u00f8" =>"ø",
		"\u00f9" =>"ù",
		"\u00fa" =>"ú",
		"\u00fb" =>"û",
		"\u00fc" =>"ü",
		"\u00fd" =>"ý",
		"\u00ff" =>"ÿ");

		return strtr($valor, $utf8_ansi2);      

	}
	
	//On enregistre tous les appels (pour le debug)
	public static function log($s = ""){		
		if (file_exists(storage_path()."/logs/ia.log")){
			$info = file_get_contents(storage_path()."/logs/ia.log");
			if (count(explode("\n",$info))>10){
				unlink(storage_path()."/logs/ia.log");
			}
		}
		
		$fp = fopen(storage_path()."/logs/ia.log","a+");
		fputs($fp,$_SERVER["REQUEST_URI"]. " ".$s."\r\n");
		fclose($fp);
	}
	
	//Convertit tout en UTF 8 pour json_encode
	public static function utf8ize($d) {
		if (is_array($d)) {
			foreach ($d as $k => $v) {
				$d[$k] = self::utf8ize($v);
			}
		} else if (is_string ($d)) {
			return utf8_encode($d);
		}
		return $d;
	}
}
