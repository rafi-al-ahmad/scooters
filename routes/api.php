<?php

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
    });
    
    Route::put('user', [UserController::class, 'update'])->name('user.profile.update');
    Route::get('user/{id}', [UserController::class, 'show'])->name('user.show');
    Route::get('logout', [UserController::class, 'logout'])->name('logout');
    Route::get('logout/all', [UserController::class, 'logoutAll'])->name('logout.all');
});

Route::post('register', [UserController::class, 'store'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::get('social/{provider}', [UserController::class, 'socialAuth'])->name('social.auth');
Route::get('login/{provider}', [UserController::class, 'redirectToProvider'])->name('login.provider.redirect');
Route::get('login/{provider}/callback', [UserController::class, 'handleProviderCallback'])->name('login.provider.callback');
