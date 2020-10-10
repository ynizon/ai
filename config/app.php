<?php

return [
	"BUS"=>array("3"=>"BSEJ","59"=>"VERL2","54"=>"VERL2","89"=>"BDEL","20"=>"GCAR"), //Ex: [numero ligne=>code arret ex:VERL ou VERL1 ou VERL2 pour specifier le sens]
	"IP_KAROTZ"=>env("IP_KAROTZ","192.168.1.16"),
	"MUSIC_FILES"=>["mp3","ogg","flac","wma","wav"],	
	"MOVIES_FILES"=>["mpeg","avi","mkv","mp4"],	
	"ROOMS" => array("192.168.1.15"=>"salon","192.168.1.14"=>"cuisine"),//[IP=>Sonos Room,IP 2=>Sonos Room 2]
	"URL_YOUTUBEDL_PAGE"=>env("URL_YOUTUBEDL_PAGE"),
	"YOUTUBE_API"=>env("YOUTUBE_API"),
	"ACCESS_TOKEN"=>env("ACCESS_TOKEN"),
	
	"MUSIC_FOLDER"=>env("MUSIC_FOLDER","d:/Documents_Sauvegardes/MP3"),//"/volume1/nas/music",
	"NAS_MUSIC_FOLDER"=>env("NAS_MUSIC_FOLDER","//192.168.1.5/nas/music"),
	
	"NFS_NAS"=>env("NFS_NAS","nfs://192.168.1.5"),
	
	"MOVIE_FOLDER"=>env("MOVIE_FOLDER","x:\\video\\Films"),//Configurer L'ouverture d'apache avec un compte local dans les services
	"CARTOON_FOLDER"=>env("CARTOON_FOLDER","/volume1/nas/video/Dessins Animes"),//Configurer L'ouverture d'apache avec un compte local dans les services
	"SERIE_FOLDER"=>env("SERIE_FOLDER","/volume1/nas/video/Series"),//Configurer L'ouverture d'apache avec un compte local dans les services

	"KODI_IP_PORT"=>env("KODI_IP_PORT","192.168.1.18:8080"),//192.168.1.3:8080	
	"TOKEN_METEO"=>env("TOKEN_METEO",""),//https://api.meteo-concept.com/
	"ID_VILLE_METEO"=>env("ID_VILLE_METEO"),//https://api.meteo-concept.com/carte-des-stations
	
	//Liste radios
	//http://fluxradios.blogspot.com/p/flux-radios-francaise.html
	"RADIOS" => array("RTL"=>"http://streaming.radio.rtl.fr/rtl-1-48-192",
				"France Inter"=>"http://chai5she.cdn.dvmr.fr/franceinter-midfi.mp3",
				"Europe 1"=>"http://mp3lg4.tdf-cdn.com/9240/lag_180945.mp3",
				"Alouette"=>"http://alouette.ice.infomaniak.ch/alouette-high.mp3"),
				
				
	//Alarme MyFox / Somfy
	"ALARM_PASSWORD"=>env("ALARM_PASSWORD",""),    // Mot de passe du compte
	"ALARM_USERNAME"=>env("ALARM_USERNAME",""),      // Non d'utilisateur (mail)
	"ALARM_CLIENT_ID"=>env("ALARM_CLIENT_ID",""),         // Client ID, s'incrire a l'API
	"ALARM_CLIENT_SECRET"=> env("ALARM_CLIENT_SECRET",""),   // Client secret
	
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Yoz AI'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'fr_FR',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

		App\Providers\MyLaravelMP3ServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

		'MyLaravelMP3' => App\Facades\MyLaravelMP3Facade::class,
		'Helpers'	=> App\Providers\HelperServiceProvider::class,
    ],

];
