<?php

// src/DemoFacade.php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class MyLaravelMP3Facade extends Facade
{
    protected static function getFacadeAccessor() { 
        return 'app-mylaravelmp3';
    }
}

?>