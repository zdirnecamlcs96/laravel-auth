<?php

use Illuminate\Support\Facades\Route;
use Zdirnecamlcs96\Auth\Controllers\LoginController;

Route::group(['middleware' => "guest"], function() {
    Route::post('login', [LoginController::class, "login"]);
    // Route::post('register', [RegisterController::class, "register"]);
});