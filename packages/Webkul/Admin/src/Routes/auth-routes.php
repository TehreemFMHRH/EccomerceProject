<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Controllers\User\ForgetPasswordController;
use Webkul\Admin\Http\Controllers\User\ResetPasswordController;
use Webkul\Admin\Http\Controllers\User\SessionController;


Route::group(['prefix' => config('app.admin_url')], function () {
    
    Route::get('/', [Controller::class, 'redirectToLogin']);

    Route::controller(SessionController::class)->prefix('login')->group(function () {
        
        Route::get('', 'create')->name('admin.session.create');

        
        Route::post('', 'store')->name('admin.session.store');
    });

    
    Route::controller(ForgetPasswordController::class)->prefix('forget-password')->group(function () {
        Route::get('', 'create')->name('admin.forget_password.create');

        Route::post('', 'store')->name('admin.forget_password.store');
    });

    
    Route::controller(ResetPasswordController::class)->prefix('reset-password')->group(function () {
        Route::get('{token}', 'create')->name('admin.reset_password.create');

        Route::post('', 'store')->name('admin.reset_password.store');
    });
});
