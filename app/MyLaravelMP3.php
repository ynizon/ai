<?php

namespace App;
use LaravelMP3;
//New class from Acekyd\LaravelMP3 (remove a lot of pb with different encoding files format)
class MyLaravelMP3 
{

    private $info;
	private $path;

    public function load($path = null)
    {
		$this->info = null;
		try{			
			include_once(base_path().'/vendor/james-heinrich/getid3/getid3/getid3.php');
			$getID3 = new \getID3;
			$this->info = $getID3->analyze( $path );
		}catch(\Exception $e){
			//Nothing todo			
		}	
		return $this->info;		
    }

	private function getInfoByKey($key){
		$info = "";
        $lib = $this->info;

		if (isset($lib['tags'])){
			foreach ($lib['tags'] as $tag=>$value){								
				if (isset($value[$key])){
					$info = $value[$key];
				}
			}
		}else{
			if (isset($lib['tag'])){
				foreach ($lib['tag'] as $tag=>$value){				
					if (isset($value[$key])){
						$info = $value[$key];
					}
				}
			}
		}

		if (is_array($info)){
			$info = $info[0];
		}
		return $info ;
	}
	
    public function getAlbum()
    {
        return $this->getInfoByKey("album") ;
    }

    public function getArtist()
    {		
		return $this->getInfoByKey("artist") ;
    }

    public function getBitrate()
    {
        $lib = $this->info;
        return $lib['audio']['bitrate'];
    }

    public function getDuration()
    {
        $lib = $this->info;
        $play_time = $lib['playtime_string'];
        $hours = 0;
        list($mins , $secs) = explode(':' , $play_time);

        if($mins > 60)
        {
            $hours = intval($mins / 60);
            $mins = $mins - $hours*60;
        }
        if($hours)
        {
            $play_time = sprintf("%02d:%02d:%02d" , $hours , $mins , $secs);
        }
        else $play_time = sprintf("%02d:%02d" , $mins , $secs);

        return $play_time;
    }

    public function getFormat()
    {
        $lib = $this->info;
        return $lib['audio']['dataformat'];
    }

    public function getGenre($path)
    {
        return $this->getInfoByKey($path,"genre") ;
    }

    public function getMime($path)
    {
        $lib = $this->info;
        return $lib['mime_type'];
    }

    public function getTitle()
    {		
        return $this->getInfoByKey("title") ;
    }

    public function getTrackNo()
    {
        return $this->getInfoByKey("track_number") ;		
    }

    public function getYear()
    {
        return $this->getInfoByKey("year") ;
    }

    public function isLossless()
    {
        $lib = $this->info;
        return $lib['audio']['lossless'];
    }


}


?>