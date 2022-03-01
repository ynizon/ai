<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo config("app.name");?></title>
	    <!-- CSRF Token -->
		<meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

		<link href="/fontawesome/css/all.css" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                /*height: 100vh;*/
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>

		<link href="/css/jplayer.blue.monday.min.css" rel="stylesheet" type="text/css" />

		<style type="text/css">
			body { width: 500px; margin: 0 auto; text-align: center; margin-top: 20px; }
			input { width: 400px; }
			button { width: 50px; }
			textarea { width: 100%; }

			@media (prefers-color-scheme: dark) {
				html, body {
					background-color: #111;
					background: #212121 !important;
					color: #ededed;
					color-alpha: #50a8d8;
				}
				.jp-interface {
					background: #eee !important;
				}
				.jp-details, .jp-playlist {
					background: #ccc !important;
				}

				.jp-playlist a{
					color : #000;
				}
				div.jp-type-playlist div.jp-playlist a.jp-playlist-current{
					color : #000 !important;
				}

				a.jp-playlist-item{
					color : #000 !important;
				}
			}
		</style>
		<script src="/js/jquery.min.js"></script>
		<script type="text/javascript" src="/js/jquery.jplayer.min.js"></script>
		<script type="text/javascript" src="/js/jplayer.playlist.min.js"></script>

		<script type="text/javascript">
			$(document).ready(function() {
				$.ajaxSetup({
					headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
				});
                loadDirectories("");
			});

            function loadDirectories(dir){
                $.get( "/explorer",
                    { dir: dir })
                    .done(function( data ) {
                        $("#directories").html(data);
                    }
                );
            }


			var accessToken = "<?php echo config('app.ACCESS_TOKEN');?>";
			var baseUrl = "https://api.dialogflow.com/v1/";

			$(document).ready(function() {
				$("#input").keypress(function(event) {
					if (event.which == 13) {
						event.preventDefault();
						sendText();
						//send();
					}
				});
				$("#rec").click(function(event) {
					switchRecognition();
				});
				<?php
				if($radio!="" or $artist!=""){
					?>
					send();
					<?php
				}else{
					?>
					startRecognition();
					<?php
				}
				?>
			});
			var recognition;
			function startRecognition() {

				recognition = new webkitSpeechRecognition();
				recognition.onstart = function(event) {
					updateRec();
				};
				recognition.onresult = function(event) {
					var text = "";
					for (var i = event.resultIndex; i < event.results.length; ++i) {
						text += event.results[i][0].transcript;
					}
					setInput(text);
					stopRecognition();
				};
				recognition.onend = function() {
					stopRecognition();
				};
				recognition.lang = "fr-FR";
				recognition.start();
			}

			function stopRecognition() {
				if (recognition) {
					recognition.stop();
					recognition = null;
				}
				updateRec();
			}
			function switchRecognition() {
				if (recognition) {
					stopRecognition();
				} else {
					startRecognition();
				}
			}
			function setInput(text) {
				$("#input").val(text);
				send();
			}
			function updateRec() {
				$("#rec").toggleClass("fa-microphone");
				$("#rec").toggleClass("fa-microphone-alt");
			}
			function send() {
				setLog("Loading...");
				var text = $("#input").val();
				if (text == ""){
					setLog("");
				}else{
					if (text.toLowerCase().indexOf("je voudrais écouter")>-1){
						var ip = $("#ip").val();
						$.post("/ia", { ip:ip, question: text}, function(data) {
							eval(data);
							prepareResponse();
						 });
					}else{
						$.ajax({
							type: "POST",
							url: baseUrl + "query?v=20150910",
							contentType: "application/json; charset=utf-8",
							dataType: "json",
							headers: {
								"Authorization": "Bearer " + accessToken
							},
							data: JSON.stringify({ q: text, lang: "fr",sessionId:"1234567890"}),
							success: function(data) {
								sendText();
							},
							error: function() {
								sendText();
								setLog("Internal Server Error");
							}
						});
					}
				}
			}

			function sendText() {
				var text = encodeURIComponent($("#input").val());
				setLog("Loading...");
				var ip = $("#ip").val();
				$.post("/ia", { ip:ip, question: text}, function(data) {
					eval(data);
					prepareResponse();
				 });
			}

			function setLog(val) {
				$("#log").val(val);
			}

			function prepareResponse() {
			  var val = $("#response").val();
			  if (val !== "") {
				var msg = new SpeechSynthesisUtterance();
				var voices = window.speechSynthesis.getVoices();
				for (var i = 0; i < voices.length; i++) {
					if (voices[i].lang == "fr-FR"){
						msg.voice = voices[i];
					}
				}

				msg.text = val;
				window.speechSynthesis.speak(msg);
			  }
			}

			//On demarre la reconnaissance vocale
			$(document).ready(function(){
				//switchRecognition();
			});

			var oPlaylist = [];
			var mPlayer = null;
			$(document).ready(function(){
				mPlayer = new jPlayerPlaylist({
					jPlayer: "#jquery_jplayer_1",
					cssSelectorAncestor: "#jp_container_1"
				},
					oPlaylist
				, {
					playlistOptions: {
						autoPlay: true,
						enableRemoveControls: true
					},
					swfPath: "js",
					supplied: "mp3",
					wmode: "window",
					useStateClassSkin: true,
					autoBlur: false,
					smoothPlayBar: true,
					keyEnabled: true
				});

			});
			</script>
    </head>
    <body>
        <div class=" position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="content">
				<h1 style="display:inline;padding-right:100px;"><?php echo config("app.name");?></h1><span><?php echo $count;?> Fichiers</span>
                <?php
				$data = '{"jsonrpc": "2.0", "method": "VideoLibrary.GetTVShows", "params": { "filter": {"field": "playcount", "operator": "is", "value": "0"}, "limits": { "start" : 0, "end": 75 }, "properties": ["art", "genre", "plot", "title", "originaltitle", "year", "rating", "thumbnail", "playcount", "file", "fanart"], "sort": { "order": "ascending", "method": "label" } }, "id": "libTvShows"}';
				//$data = '{ "jsonrpc": "2.0", "method": "JSONRPC.Introspect", "params": { "filter": { "id": "AudioLibrary.GetAlbums", "type": "method" } }, "id": 1 }';
				$data = '{"jsonrpc":"2.0","id":"1","method":"Player.Open","params":{"item":{"file":"file:///X:/video/Films/destroy-predestination.avi"}}}';
				//{%22method%22:%22Player.Open%22,%22id%22:44,%22jsonrpc%22:%222.0%22,%22params%22:{%22item%22:{%22file%22:%22x:/video/Films/Merci%20Patron.2016.FRENCH.DVDRip.XVid.AC3-Afrique31.avi%22}}}%27
				$data = '{"method":"Player.Open","id":44,"jsonrpc":"2.0","params":{"item":{"file":"x:/video/Films/Merci%20Patron.2016.FRENCH.DVDRip.XVid.AC3-Afrique31.avi"}}}';
				//$data = '{ "jsonrpc": "2.0", "method": "Player.Open", "params": { "item": { "file": "smb://dan-svr/movies/Kids/My Little Pony Equestria Girls - Rainbow Rocks [2014 480p BDRip].mkv" } }, "id": 1 }';
				//$data = '{"jsonrpc": "2.0", "method": "Player.PlayPause", "params": { "playerid": 1 }, "id": 1}';
				//{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 1}
				$data = '{"method":"Player.Open","id":1,"jsonrpc":"2.0","params":{"item":{"file":"x:/video/Films/Merci%20Patron.2016.FRENCH.DVDRip.XVid.AC3-Afrique31.avi"}}}';
				//$s = file_get_contents("http://192.168.1.10:8080/jsonrpc?request=".$data);
				//echo $s;
				//exit();
				?>



				<div>
					<form action="/tts">
						<input id="input" type="text" name="txt" value="<?php if ($radio!= ""){echo "écouter la radio ".$radio;}?><?php if ($artist!= ""){echo "écouter ".$artist;}?>">
						<i class="fas fa-microphone" id="rec"></i><br/><br/>


						<select name="ip" id="ip">
							<option <?php if($salle=="-"){echo "selected";} ?> value="-">Local</option>
							<?php
							foreach (config("app.ROOMS") as $ip_salle=>$name_salle){
								?>
								<option <?php if($salle==$name_salle){echo "selected";} ?> value="<?php echo $ip_salle;?>"><?php echo $name_salle;?></option>
								<?php
							}
							?>
						</select>
						&nbsp;<input type="submit" style="width:50px" value="TTS"/>
					</form>
					<br/><button is='google-cast-button' id='google-cast-button' style='width:40px'></button>
					&nbsp;&nbsp;<a href="/restartNasHdmi" style="color:#fff">HDMI Restart</a>

					<div style="padding:25px">
						<div id="jquery_jplayer_1" class="jp-jplayer"></div>
							<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">
								<div class="jp-type-playlist">
									<div class="jp-gui jp-interface">
										<div class="jp-controls">
											<button class="jp-previous" role="button" tabindex="0">previous</button>
											<button class="jp-play" role="button" tabindex="0">play</button>
											<button class="jp-next" role="button" tabindex="0">next</button>
											<button class="jp-stop" role="button" tabindex="0">stop</button>
										</div>
										<div class="jp-progress">
											<div class="jp-seek-bar">
												<div class="jp-play-bar"></div>
											</div>
										</div>
										<div class="jp-volume-controls">
											<button class="jp-mute" role="button" tabindex="0">mute</button>
											<button class="jp-volume-max" role="button" tabindex="0">max volume</button>
											<div class="jp-volume-bar">
												<div class="jp-volume-bar-value"></div>
											</div>
										</div>
										<div class="jp-time-holder">
											<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
											<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
										</div>
										<div class="jp-toggles">
											<button class="jp-repeat" role="button" tabindex="0">repeat</button>
											<button class="jp-shuffle" role="button" tabindex="0">shuffle</button>
										</div>
									</div>
									<div class="jp-playlist">
										<ul>
											<li>&nbsp;</li>
										</ul>
									</div>
									<div class="jp-no-solution">
										<span>Update Required</span>
										To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
									</div>
								</div>
							</div>
						</div>

						<br>Response<br> <textarea id="response" cols="4" rows="2"></textarea>
						<br>LOG<br> <textarea id="log" cols="40" rows="2"></textarea>

						<br/>
						<p>Prends ton micro casque et demande moi :
							<ul>
								<li>une opération mathématique</li>
								<li>une anecdote sur chuck norris</li>
								<li>d'écouter une radio</li>
								<li>d'ajouter/supprimer/lister une tâche</li>
								<li>de regarder la série W saison X épisode Y</li>
							</ul>
						</p>

                        <div id="directories" style="text-align: left;border:1px solid #cccccc;padding-top:10px;">
                        </div>
					</div>
				</div>
            </div>
        </div>

		<script src='https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1'></script>
		<script>
            function lanceExplorer(heart){
                $("#response").val("");
                $("#log").val("");
                eval(document.getElementById('explorer_mp3'+heart).value);
                mPlayer.setPlaylist(oPlaylist);
                mPlayer.play();
            }

			//Chromecast
			var castSession;
			var media;
			var request;
			window.__onGCastApiAvailable = function(isAvailable){
				if(! isAvailable){
					return false;
				}

				var castContext = cast.framework.CastContext.getInstance();

				castContext.setOptions({
					autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED,
					receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID
				});


				var stateChanged = cast.framework.CastContextEventType.CAST_STATE_CHANGED;
				castContext.addEventListener(stateChanged, function(event){
					console.log(event);
					castSession = castContext.getCurrentSession();
					if (event.castState == 'CONNECTED'){
						$("#jquery_jplayer_1").jPlayer("mute", true);
						mPlayer.play();
						goCast();

						//Test de cette ligne
						window.setTimeout(function(){$(".jp-playlist-current").click();}, 2000);

						$('#google-cast-button').css('backgroundColor','#ccc');
					}
					if (event.castState == 'DISCONNECTED'){
						$('#google-cast-button').css('backgroundColor','#f00');
					}
				});
			};

			//A chaque chanson, on la cast

			$('#jquery_jplayer_1').bind($.jPlayer.event.play, function(event) {
				if (castSession){
					goCast();
					/*
					if (pause){
						let playrequest = new chrome.cast.media.PlayRequest();
						request = new chrome.cast.media.LoadRequest(playrequest);
						pause = false;
						castSession.loadMedia(request, onLoadSuccess, onLoadError);
					}else{
						goCast();
					}
					*/
				}
			});

			//On pause aussi le cast
			/*
			var pause = false;
			$('#jquery_jplayer_1').bind($.jPlayer.event.pause, function(event) {
				if (castSession){
					let pauserequest = new chrome.cast.media.PauseRequest();
					request = new chrome.cast.media.LoadRequest(pauserequest);
					pause = true;
					castSession.loadMedia(request, onLoadSuccess, onLoadError);
				}
			});
			*/



			//Envoi le son sur le chromecast
			function goCast(){

				/*
				//Playlist
				var items = [];
				var k = 0;
				while (k < mPlayer.playlist.length){
					media = new chrome.cast.media.MediaInfo(mPlayer.playlist[k]['mp3'], 'audio/mp3');
					media.metadata = new chrome.cast.media.MusicTrackMediaMetadata();
					var titles = mPlayer.playlist[k]['title'].split('>');
					media.metadata.title = titles[0];
					media.metadata.artist = titles[1];
					//im = chrome.cast.Image('http://m1.behance.net/rendition/modules/575407/disp/822271229466847.png');
					//media.metadata.images = new Array(im);
					var item = new chrome.cast.media.QueueItem(media);

					items.push(item);
					k++;
				}

				request = new chrome.cast.media.QueueLoadRequest(items);
				castSession && castSession.getSessionObj().queueLoad(request);
				*/

				//1 par 1
				if (mPlayer.playlist.length > 0){
                    media = new chrome.cast.media.MediaInfo(mPlayer.playlist[mPlayer.current]['mp3'], 'audio/mp3');
                    //media = new chrome.cast.media.MediaInfo('https://file-examples-com.github.io/uploads/2017/11/file_example_MP3_700KB.mp3', 'audio/mp3');
                    //media = new chrome.cast.media.MediaInfo('http://home.gameandme.fr/mp3test', 'audio/mp3');
                    //media = new chrome.cast.media.MediaInfo('http://chiens.gameandme.fr/coco.mp3', 'audio/mp3');
                    /*

                    if (window.confirm("ccc")){
                        media = new chrome.cast.media.MediaInfo('https://home.gameandme.fr/mp3test', 'audio/mp3');
                    }*/

                    media.metadata = new chrome.cast.media.MusicTrackMediaMetadata();
                    var titles = mPlayer.playlist[mPlayer.current]['title'].split('>');
                    media.metadata.title = titles[0];
                    media.metadata.artist = titles[1];
                    //im = chrome.cast.Image('http://m1.behance.net/rendition/modules/575407/disp/822271229466847.png');
                    //media.metadata.images = new Array(im);
                    request = new chrome.cast.media.LoadRequest(media);

                    castSession.loadMedia(request, onLoadSuccess, onLoadError);

                    /*
                    //A tester ca: si ca marche mieux
                    const sessionObj = await castSession.getSessionObj();
                    await (new Promise((res) => {
                        sessionObj.loadMedia(request, res);
                    }));
                    */


                }
			}

			function castPicture(){
				var mediaInfo = new chrome.cast.media.MediaInfo('http://i.imgur.com/IFD14.jpg', 'image/jpg');

				var request = new chrome.cast.media.LoadRequest(mediaInfo);
				request.autoplay = true;

				castSession.loadMedia(request, onLoadSuccess, onLoadError);

			}

			function castVideo(){
				//var mediaInfo = new chrome.cast.media.MediaInfo('http://i.imgur.com/IFD14.jpg', 'image/jpg');
				var mediaInfo = new chrome.cast.media.MediaInfo('https://www.learningcontainer.com/wp-content/uploads/2020/05/sample-mp4-file.mp4', 'video/mp4');

				var request = new chrome.cast.media.LoadRequest(mediaInfo);
				request.autoplay = true;

				castSession.loadMedia(request, onLoadSuccess, onLoadError);

			}

			function onLoadSuccess() {
				console.log('Success.');
			}

			function onLoadError() {
				console.log('Failed.');
			}
		</script>
    </body>
</html>
