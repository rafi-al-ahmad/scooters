<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group([
    'middleware' => ['auth:sanctum']
], function () {

    Route::group([
        'middleware' => ['can:admin']
    ], function () {
        Route::get('users', [UserController::class, 'users'])->name('users');
        Route::get('admins', [UserController::class, 'admins'])->name('admins');
        Route::post('admin', [UserController::class, 'store'])->name('admin.create');
        Route::delete('user/{id}', [UserController::class, 'destroy'])->name('user.delete');
        Route::put('user/{id}', [UserController::class, 'update'])->name('user.update');
        
        //services routes
        Route::get('services/all', [ServiceController::class, 'index'])->name('services');
        Route::post('service', [ServiceController::class, 'store'])->name('service.create');
        Route::get('service/{id}', [ServiceController::class, 'show'])->name('service');
        Route::put('service/{id}', [ServiceController::class, 'update'])->name('service.update');
        Route::delete('service/{id}/translation/{language}', [ServiceController::class, 'deleteTranslation'])->name('service.translation.delete');
        Route::delete('service/{id}', [ServiceController::class, 'destroy'])->name('service.delete');
        
    });
    
    
    Route::put('user', [UserController::class, 'update'])->name('user.profile.update');
    Route::get('user/{id}', [UserController::class, 'show'])->name('user.show');
    Route::get('logout', [UserController::class, 'logout'])->name('logout');
    Route::get('logout/all', [UserController::class, 'logoutAll'])->name('logout.all');
});

Route::get('services', [ServiceController::class, 'allActive'])->name('services.active');

Route::post('register', [UserController::class, 'store'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::get('social/{provider}', [UserController::class, 'socialAuth'])->name('social.auth');
Route::get('login/{provider}', [UserController::class, 'redirectToProvider'])->name('login.provider.redirect');
Route::get('login/{provider}/callback', [UserController::class, 'handleProviderCallback'])->name('login.provider.callback');
