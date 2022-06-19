<?php

use Illuminate\Support\Facades\Route;
use Zdirnecamlcs96\Auth\Contracts\ShouldAuthenticate;
use Zdirnecamlcs96\Auth\Http\Controllers\{
    LoginController,
    AccountController,
    RegisterController
};

foreach (config('auth.guards') as $guard => $settings) {
    if (in_array($settings['driver'], [
        ShouldAuthenticate::PASSPORT,
        ShouldAuthenticate::SANCTUM
    ])) {

        Route::match(['get', 'post'], 'third-party-login/{provider}/callback', [LoginController::class, 'thirdPartyLoginCallback'])->name('third-party-login.callback');

        Route::group(['middleware' => ["guest:{$guard}"]], function() {
            Route::controller(LoginController::class)->group(function() {
                Route::post('login', 'login')->name('login');
                Route::post('third-party-login', 'thirdPartyLogin')->name('third-party-login');
            });
            Route::post('register', [RegisterController::class, 'register']);
            // Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            //             ->name('password.email');
            // Route::post('reset-password', [NewPasswordController::class, 'store'])
            //             ->name('password.update');
        });

        Route::group(['middleware' => ["auth:{$guard}"]], function() {
            Route::post('logout', [LoginController::class, "logout"])->name('logout');

            Route::group(["prefix" => "account", "as" => "account", "controller" => AccountController::class], function() {
                Route::post("", 'show');
                Route::post("update", 'update');
                Route::post("change-password", 'changePassword');
                Route::post("delete", 'destroy');
            });

        });
    }
}
