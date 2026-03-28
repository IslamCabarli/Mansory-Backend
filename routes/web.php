<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-link', function () {
    Artisan::call('storage:link --force');
    return "Storage link created!";
});
