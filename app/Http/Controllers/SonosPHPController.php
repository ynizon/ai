<?php
namespace App\Http\Controllers;

//DOC https://musicpartners.sonos.com/?q=node/520
// http://www.planete-domotique.com/blog/2013/06/10/une-classe-php-pour-piloter-ses-sonos-avec-leedomus/
class SonosPHPController
{
	protected $Sonos_IP;
	protected $_raw = [];

	/**
	* Constructeur
	* @param string Sonos IP adress
	* @param string Sonos port (optional)
	*/
	public function __construct($Sonos_IP,$Sonos_Port = '1400')
	{
		// On assigne les param�tres aux variables d'instance.
		$this->IP = $Sonos_IP;
		$this->PORT = $Sonos_Port;
	}

	protected function Upnp($url,$SOAP_service,$SOAP_action,$SOAP_arguments = '',$XML_filter = '')
	{
		$POST_xml = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
		$POST_xml .= '<s:Body>';
		$POST_xml .= '<u:'.$SOAP_action.' xmlns:u="'.$SOAP_service.'">';
		$POST_xml .= $SOAP_arguments;
		$POST_xml .= '</u:'.$SOAP_action.'>';
		$POST_xml .= '</s:Body>';
		$POST_xml .= '</s:Envelope>';

		$POST_url = $this->IP.":".$this->PORT.$url;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $POST_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "SOAPAction: ".$SOAP_service."#".$SOAP_action));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST_xml);
		$r = curl_exec($ch);
		curl_close($ch);

		if ($XML_filter != '')
			return $this->Filter($r,$XML_filter);
		else
			return $r;
	}

	protected function Filter($subject,$pattern)
	{
		preg_match('/\<'.$pattern.'\>(.+)\<\/'.$pattern.'\>/',$subject,$matches); ///'/\<'.$pattern.'\>(.+)\<\/'.$pattern.'\>/'
		return $matches[1];
	}

	/**
	* Play
	*/
	public function Play()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'Play';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID><Speed>1</Speed>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Pause
	*/
	public function Pause()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'Pause';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Stop
	*/
	public function Stop()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'Stop';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Next
	*/
	public function Next()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'Next';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Previous
	*/
	public function Previous()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'Previous';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Seek to position xx:xx:xx or track number x
	* @param string 'REL_TIME' for time position (xx:xx:xx) or 'TRACK_NR' for track in actual queue
	* @param string
	*/
	public function Seek($type,$position)
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'Seek';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID><Unit>'.$type.'</Unit><Target>'.$position.'</Target>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Seek to time xx:xx:xx
	*/
	public function SeekTime($time)
	{
		return $this->Seek("REL_TIME",$time);
	}

	/**
	* Change to track number
	*/
	public function ChangeTrack($number)
	{
		return $this->Seek("TRACK_NR",$number);
	}

	/**
	* Restart actual track
	*/
	public function RestartTrack()
	{
		return $this->Seek("REL_TIME","00:00:00");
	}

	/**
	* Restart actual queue
	*/
	public function RestartQueue()
	{
		return $this->Seek("TRACK_NR","1");
	}

	/**
	* Get volume value (0-10)
	*/
	public function GetVolume()
	{
		$url = '/MediaRenderer/RenderingControl/Control';
		$action = 'GetVolume';
		$service = 'urn:schemas-upnp-org:service:RenderingControl:1';
		$args = '<InstanceID>0</InstanceID><Channel>Master</Channel>';
		$filter = 'CurrentVolume';
		return $this->Upnp($url,$service,$action,$args,$filter);
	}

	/**
	* Set volume value (0-10)
	*/
	public function SetVolume($volume)
	{
		$url = '/MediaRenderer/RenderingControl/Control';
		$action = 'SetVolume';
		$service = 'urn:schemas-upnp-org:service:RenderingControl:1';
		$args = '<InstanceID>0</InstanceID><Channel>Master</Channel><DesiredVolume>'.$volume.'</DesiredVolume>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Get mute status
	*/
	public function GetMute()
	{
		$url = '/MediaRenderer/RenderingControl/Control';
		$action = 'GetMute';
		$service = 'urn:schemas-upnp-org:service:RenderingControl:1';
		$args = '<InstanceID>0</InstanceID><Channel>Master</Channel>';
		$filter = 'CurrentMute';
		return $this->Upnp($url,$service,$action,$args,$filter);
	}

	/**
	* Set mute
	* @param integer mute active=1
	*/
	public function SetMute($mute = 0)
	{
		$url = '/MediaRenderer/RenderingControl/Control';
		$action = 'SetMute';
		$service = 'urn:schemas-upnp-org:service:RenderingControl:1';
		$args = '<InstanceID>0</InstanceID><Channel>Master</Channel><DesiredMute>'.$mute.'</DesiredMute>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Get Transport Info : get status about player
	*/
	public function GetTransportInfo()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'GetTransportInfo';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		$filter = 'CurrentTransportState';
		return $this->Upnp($url,$service,$action,$args,$filter);
	}

	/**
	* Get Media Info : get informations about media
	*/
	public function GetMediaInfo()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'GetMediaInfo';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		$filter = 'CurrentURI';
		return $this->Upnp($url,$service,$action,$args,$filter);
	}

	/**
	* Get Position Info : get some informations about track
	*/
	public function GetPositionInfo()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'GetPositionInfo';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		$xml = $this->Upnp($url,$service,$action,$args);

		$data["TrackNumberInQueue"] = $this->Filter($xml,"Track");
		$data["TrackURI"] = $this->Filter($xml,"TrackURI");
		$data["TrackDuration"] = $this->Filter($xml,"TrackDuration");
		$data["RelTime"] = $this->Filter($xml,"RelTime");
		$TrackMetaData = $this->Filter($xml,"TrackMetaData");

		$xml = substr($xml, stripos($TrackMetaData, '&lt;'));
		$xml = substr($xml, 0, strrpos($xml, '&gt;') + 4);
		$xml = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $xml);

		$data["Title"] = $this->Filter($xml,"dc:title");	// Track Title
		$data["AlbumArtist"] = $this->Filter($xml,"r:albumArtist");		// Album Artist
		$data["Album"] = $this->Filter($xml,"upnp:album");		// Album Title
		$data["TitleArtist"] = $this->Filter($xml,"dc:creator");	// Track Artist

		return $data;
	}

	/**
	* Add URI to Queue
	* @param string track/radio URI
	* @param bool added next (=1) or end queue (=0)
	*/
	public function AddURIToQueue($URI,$next=0)
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'AddURIToQueue';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$next = (int)$next;
		$args = '<InstanceID>0</InstanceID><EnqueuedURI>'.$URI.'</EnqueuedURI><EnqueuedURIMetaData></EnqueuedURIMetaData><DesiredFirstTrackNumberEnqueued>0</DesiredFirstTrackNumberEnqueued><EnqueueAsNext>'.$next.'</EnqueueAsNext>';
		$filter = 'FirstTrackNumberEnqueued';
		return $this->Upnp($url,$service,$action,$args,$filter);
	}

	/**
	* Remove a track from Queue
	*
	*/
	public function RemoveTrackFromQueue($tracknumber)
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'RemoveTrackFromQueue';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID><ObjectID>Q:0/'.$tracknumber.'</ObjectID>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Clear Queue
	*
	*/
	public function RemoveAllTracksFromQueue()
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'RemoveAllTracksFromQueue';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Set Queue
	* @param string URI of new track
	*/
	public function SetQueue($URI)
	{
		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'SetAVTransportURI';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID><CurrentURI>'.$URI.'</CurrentURI><CurrentURIMetaData></CurrentURIMetaData>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Refresh music library
	*
	*/
	public function RefreshShareIndex()
	{
		$url = '/MediaServer/ContentDirectory/Control';
		$action = 'RefreshShareIndex';
		$service = 'urn:schemas-upnp-org:service:ContentDirectory:1';
		return $this->Upnp($url,$service,$action,$args);
	}

	/******************************************************************************
	* Get Transport Settings : get PlayMode about player
	******************************************************************************/
	public function GetPlayMode()	{

		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'GetTransportSettings';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID>';
		$filter = 'PlayMode';
		return $this->Upnp($url,$service,$action,$args,$filter);
	}

	/******************************************************************************
	* Set Transport Settings : set PlayMode about player (NORMAL , REPEAT_ALL, SHUFFLE , SHUFFLE_NOREPEAT)
	******************************************************************************/
	public function SetPlayMode($playmode="NORMAL")	{

		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'SetPlayMode';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$args = '<InstanceID>0</InstanceID><NewPlayMode>'.$playmode.'</NewPlayMode>';
		return $this->Upnp($url,$service,$action,$args);
	}

	/**
	* Split string in several strings
	*
	*/
	protected function CutString($string,$intmax)
	{
		$i = 0;
		while (strlen($string) > $intmax)
		{
			$string_cut = substr($string, 0, $intmax);
			$last_space = strrpos($string_cut, "+");
			$strings[$i] = substr($string, 0, $last_space);
			$string = substr($string, $last_space, strlen($string));
			$i++;
		}
		$strings[$i] = $string;
		return $strings;
	}

	/**
	* Say song name via TTS message
	* @param string message
	* @param string radio name display on sonos controller
	* @param int volume
	* @param string language
	*/
	public function SongNameTTS($directory,$volume=0,$unmute=0,$lang='fr')
	{
		$ThisSong = "Cette chanson s'appelle ";
		$By = " de ";

		$actual['track'] = $this->GetPositionInfo();

		$SongName = $actual['track']['Title'];
		$Artist = $actual['track']['TitleArtist'];

		$message = $ThisSong . $SongName . $By . $Artist ;

		$this->PlayTTS($message,$directory,$volume,$unmute,$lang);

		return true;
	}

	/**
	* Play a message and restart songs...
	* @param string mp3 file for message
	* @param int volume
	*/
	public function PlayMessage($file="", $unmute = 0, $volume = 0)
	{
		try{
			//Si on envoi un MP3 et que ya rien dans la file, alors cette partie ne sert a rien
			$actual['track'] = $this->GetPositionInfo();
			$actual['volume'] = $this->GetVolume();
			$actual['mute'] = $this->GetMute();
			$actual['status'] = $this->GetTransportInfo();
			$this->Pause();

			if ($unmute == 1)
				$this->SetMute(0);
			if ($volume != 0)
				$this->SetVolume($volume);
		}catch(\Exception $e){
			//Nothing to do
		}

		//$file = 'x-file-cifs://'.$directory.'/file.mp3';

		if (((!isset($actual["track"]) or stripos($actual['track']["TrackURI"],"x-file-cifs://")) !== false) or ((stripos($actual['track']["TrackURI"],".mp3")) !== false))
		{
			// It's a MP3 file
			$TrackNumber = $this->AddURIToQueue($file);
			$this->ChangeTrack($TrackNumber);
			$this->Play();
			while (true) {
				@$ttsFile=$this->GetPositionInfo();
				if($ttsFile["TrackNumberInQueue"]!=$TrackNumber)
					break;
				usleep(10000);
			}
			$this->Pause();
			$this->SetVolume($actual['volume']);
			$this->SetMute($actual['mute']);
			$this->ChangeTrack($actual['track']["TrackNumberInQueue"]);
			$this->SeekTime($actual['track']["RelTime"]);
			$this->RemoveTrackFromQueue($TrackNumber);
		}
		else
		{
			//It's a radio / or TV (playbar) / or nothing
			$this->SetQueue($file);
			$this->Play();
			sleep(2);
			while ($this->GetTransportInfo() == "PLAYING") {}
			$this->Pause();
			$this->SetVolume($actual['volume']);
			$this->SetMute($actual['mute']);
			$this->SetQueue($actual['track']["TrackURI"]);
		}

		if (strcmp($actual['status'],"PLAYING") == 0)
			$this->Play();
		return true;
	}

	/**
	* Play a TTS message
	* @param string message
	* @param string radio name display on sonos controller
	* @param int volume
	* @param string language
	*/
	public function PlayTTS($message,$directory,$volume=0,$unmute=0,$lang='fr', $file="")
	{
		$actual['track'] = $this->GetPositionInfo();
		$actual['volume'] = $this->GetVolume();
		$actual['mute'] = $this->GetMute();
		$actual['status'] = $this->GetTransportInfo();
		$this->Pause();

		if ($unmute == 1)
			$this->SetMute(0);
		if ($volume != 0)
			$this->SetVolume($volume);

		if ($file == ""){
			$file = 'x-file-cifs://'.$directory.'/'.$this->TTSToMp3($message,$lang);
		}

		if (((stripos($actual['track']["TrackURI"],"x-file-cifs://")) !== false) or ((stripos($actual['track']["TrackURI"],".mp3")) !== false))
		{
			// It's a MP3 file
			$TrackNumber = $this->AddURIToQueue($file);
			$this->ChangeTrack($TrackNumber);
			$this->Play();
			while (true) {
				@$ttsFile=$this->GetPositionInfo();
				if($ttsFile["TrackNumberInQueue"]!=$TrackNumber)
					break;
				usleep(10000);
			}
			$this->Pause();
			$this->SetVolume($actual['volume']);
			$this->SetMute($actual['mute']);
			$this->ChangeTrack($actual['track']["TrackNumberInQueue"]);
			$this->SeekTime($actual['track']["RelTime"]);
			$this->RemoveTrackFromQueue($TrackNumber);
		}
		else
		{
			//It's a radio / or TV (playbar) / or nothing
			$this->SetQueue($file);
			$this->Play();
			sleep(2);
			while ($this->GetTransportInfo() == "PLAYING") {}
			$this->Pause();
			$this->SetVolume($actual['volume']);
			$this->SetMute($actual['mute']);
			$this->SetQueue($actual['track']["TrackURI"]);
		}

		if (strcmp($actual['status'],"PLAYING") == 0)
			$this->Play();
		return true;
	}

	public function AddSpotifyToQueue($spotify_id, $next = false) {
		$rand = mt_rand(10000000, 99999999);

		$meta = '<DIDL-Lite xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/" xmlns:r="urn:schemas-rinconnetworks-com:metadata-1-0/" xmlns="urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/">
				<item id="'.$rand.'spotify%3atrack%3a'.$spotify_id.'" restricted="true">
					<dc:title></dc:title>
					<upnp:class>object.item.audioItem.musicTrack</upnp:class>
					<desc id="cdudn" nameSpace="urn:schemas-rinconnetworks-com:metadata-1-0/">SA_RINCON2311_X_#Svc2311-0-Token</desc>
				</item>
			</DIDL-Lite>';

		$meta = htmlentities($meta);

		$url = '/MediaRenderer/AVTransport/Control';
		$action = 'AddURIToQueue';
		$service = 'urn:schemas-upnp-org:service:AVTransport:1';
		$next = (int)$next;

		$args = "
			<InstanceID>0</InstanceID>
			<EnqueuedURI>x-sonos-spotify:spotify%3atrack%3a{$spotify_id}</EnqueuedURI>
			<EnqueuedURIMetaData>{$meta}</EnqueuedURIMetaData>
			<DesiredFirstTrackNumberEnqueued>0</DesiredFirstTrackNumberEnqueued>
			<EnqueueAsNext>{$next}</EnqueueAsNext>
		";

		$filter = 'FirstTrackNumberEnqueued';

		return $this->Upnp($url, $service, $action, $args, $filter);
	}

	public function device_info() {
		$xml = $this->_device_info_raw('/xml/device_description.xml');

		$out = [
			'friendlyName' => (string)$xml->device->friendlyName,
			'modelNumber' => (string)$xml->device->modelNumber,
			'modelName' => (string)$xml->device->modelName,
			'softwareVersion' => (string)$xml->device->softwareVersion,
			'hardwareVersion' => (string)$xml->device->hardwareVersion,
			'roomName' => (string)$xml->device->roomName,
		];

		return $out;
	}

	public function get_coordinator() {
		$topology = $this->_device_info_raw('/status/topology');

		$myself = null;
		$coordinators = [];

		// Loop players, build map of coordinators and find myself
		foreach ($topology->ZonePlayers->ZonePlayer as $player) {
			$player_data = $player->attributes();

			$ip = parse_url((string)$player_data->location)['host'];

			if ($ip == $this->IP) {
				$myself = $player_data;
			}

			if ((string)$player_data->coordinator == 'true') {
				$coordinators[(string)$player_data->group] = $ip;
			}
		}

		$coordinator = $coordinators[(string)$myself->group];

		return new static($coordinator);
	}

	protected function _device_info_raw($url) {
		$url = "http://{$this->IP}:{$this->PORT}{$url}";

		if (!isset($this->_raw[$url])) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			curl_close($ch);

			$this->_raw[$url] = simplexml_load_string($data);
		}

		return $this->_raw[$url];
	}

	public static function detect($ip = '239.255.255.250', $port = 1900) {
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($sock, getprotobyname('ip'), IP_MULTICAST_TTL, 2);

		$data = <<<DATA
M-SEARCH * HTTP/1.1
HOST: {$ip}:reservedSSDPport
MAN: ssdp:discover
MX: 1
ST: urn:schemas-upnp-org:device:ZonePlayer:1
DATA;

		socket_sendto($sock, $data, strlen($data), null, $ip, $port);

		// All passed by ref
		$read = [$sock];
		$write = $except = [];
		$name = $port = null;
		$tmp = '';

		// Read buffer
		$buff = '';

		// Loop until there's nothing more to read
		while (socket_select($read, $write, $except, 1) && $read) {
			socket_recvfrom($sock, $tmp, 2048, null, $name, $port);

			$buff .= $tmp;
		}

		// Parse buffer into devices
		$data = static::_parse_detection_replies($buff);

		// Make an array of myselfs
		$devices = [];
		$unique = [];

		foreach ($data as $datum) {
			if(in_array($datum['usn'],$unique)) {
				continue;
			}
			$url = parse_url($datum['location']);

			$devices[] = new static($url['host'], $url['port']);
			$unique[] = $datum['usn'];
		}

		return $devices;
	}

	protected static function _parse_detection_replies($replies) {
		$out = [];

		// Loop each reply
		foreach (explode("\r\n\r\n", $replies) as $reply) {
			if ( ! $reply) {
				continue;
			}

			// New array entry
			$arr =& $out[];

			// Loop each line
			foreach (explode("\r\n", $reply) as $line) {
				// End of header name
				if (($colon = strpos($line, ':')) !== false) {
					$name = strtolower(substr($line, 0, $colon));
					$val = trim(substr($line, $colon + 1));

					$arr[$name] = $val;
				}
			}
		}

		return $out;
	}

	public static function get_room_coordinator($room_name) {
		// Detect devices. Sometimes takes a few goes.
		do {
			$devices = static::detect();

			if (!$devices) {
				sleep(1);
			}
		} while (!$devices);

		foreach ($devices as $device) {
			if ($device->device_info()['roomName'] == $room_name) {
				return $device->get_coordinator();
			}
		}

		return false;
	}
}
