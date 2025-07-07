<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/xml', 'App\Http\Controllers\XmlController@index')->name('xml.index');
