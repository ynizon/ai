<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Controller@home');
Route::get('/scan', 'Controller@scan');
Route::get('/cron', 'Controller@scan');
Route::get("/mp3","Controller@mp3");
Route::get("/youtube","Controller@youtube");
Route::get("/download_youtube","Controller@download_youtube");
Route::get("/alarme/{mode}","Controller@alarme");
Route::get("/karotz","Controller@karotz");
Route::get("/tts","Controller@tts");
Route::post("/ia","IaController@ia");
Route::get("/ia","IaController@ia");
Route::get("/bus/{id}","Controller@bus");
Route::get("/radio","Controller@radio");
Route::get("/meteo/{id}","Controller@meteo");
Route::get("/sonosvolume/{volume}","Controller@sonosVolume");
Route::get("/calendar","Controller@duplicateCalendar");
Route::get("/export_dialogflow","IaController@export_dialogflow");