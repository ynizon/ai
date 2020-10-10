<?php

// src/DemoServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\MyLaravelMP3;

class MyLaravelMP3ServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('app-mylaravelmp3', function() {
            return new MyLaravelMP3;
        });
    }
}


?>