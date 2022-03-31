<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\PartTypeController;
use App\Http\Controllers\ProductController;
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

Route::group([
    'middleware' => ['auth:sanctum', 'verified']
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
        
        //collections routes
        Route::get('collections/all', [CollectionController::class, 'index'])->name('collections');
        Route::post('collection', [CollectionController::class, 'store'])->name('collection.create');
        Route::get('collection/{id}', [CollectionController::class, 'show'])->name('collection');
        Route::put('collection/{id}', [CollectionController::class, 'update'])->name('collection.update');
        Route::delete('collection/{id}/translation/{language}', [CollectionController::class, 'deleteTranslation'])->name('collection.translation.delete');
        Route::delete('collection/{id}', [CollectionController::class, 'destroy'])->name('collection.delete');
        
        //part types routes
        Route::get('part/types/all', [PartTypeController::class, 'index'])->name('part.types');
        Route::post('part/type', [PartTypeController::class, 'store'])->name('part.type.create');
        Route::get('part/type/{id}', [PartTypeController::class, 'show'])->name('part.type');
        Route::put('part/type/{id}', [PartTypeController::class, 'update'])->name('part.type.update');
        Route::delete('part/type/{id}/translation/{language}', [PartTypeController::class, 'deleteTranslation'])->name('part.type.translation.delete');
        Route::delete('part/type/{id}', [PartTypeController::class, 'destroy'])->name('part.type.delete');
        
        
        //brands routes
        Route::get('brands/all', [BrandController::class, 'index'])->name('brands');
        Route::post('brand', [BrandController::class, 'store'])->name('brand.create');
        Route::get('brand/{id}', [BrandController::class, 'show'])->name('brand');
        Route::put('brand/{id}', [BrandController::class, 'update'])->name('brand.update');
        Route::delete('brand/{id}', [BrandController::class, 'destroy'])->name('brand.delete');
        
        //product
        Route::post('product', [ProductController::class, 'store'])->name('product.create');
        Route::put('product', [ProductController::class, 'update'])->name('product.update');
        Route::delete('product/{id}', [ProductController::class, 'destroy'])->name('product.delete');
        Route::get('products/all', [ProductController::class, 'index'])->name('products.all');
    
    
        Route::put('appointment', [AppointmentController::class, 'update'])->name('appointment.update');
        Route::get('appointment/{id}', [AppointmentController::class, 'show'])->name('appointment.show');
        Route::delete('appointment/{id}', [AppointmentController::class, 'destroy'])->name('appointment.delete');
        Route::get('appointments', [AppointmentController::class, 'index'])->name('appointment.all');
    });

    Route::post('user/address', [UserController::class, 'setAddress'])->name('user.address.set');
    Route::put('user', [UserController::class, 'update'])->name('user.profile.update');
    Route::put('update/user/password/{id?}', [UserController::class, 'updatePassword'])->name('user.password.update');
    Route::get('user/{id?}', [UserController::class, 'show'])->name('user.show');
    
    Route::post('appointment', [AppointmentController::class, 'store'])->middleware('account.completed')->name('appointment.create');
    Route::get('appointments/user', [AppointmentController::class, 'userAppointments'])->name('appointment.user');
    
    Route::get('account/check', function() {
        return response(["status" => "completed"]);
    })->middleware('account.completed');
    
});
Route::get('logout', [UserController::class, 'logout'])->name('logout');
Route::get('logout/all', [UserController::class, 'logoutAll'])->name('logout.all');

//public routs
Route::get('brands', [BrandController::class, 'allActive'])->name('brands.active');
Route::get('services', [ServiceController::class, 'allActive'])->name('services.active');
Route::get('collections', [CollectionController::class, 'allActive'])->name('collections.active');
Route::get('part/types', [PartTypeController::class, 'allActive'])->name('part.types.active');

Route::get('product/{id}', [ProductController::class, 'show'])->name('product');
Route::get('products', [ProductController::class, 'activeWithFilters'])->name('products.active');


Route::post('register', [UserController::class, 'store'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::get('social/{provider}', [UserController::class, 'socialAuth'])->name('social.auth');

Route::get('login/{provider}', [UserController::class, 'redirectToProvider'])->name('login.provider.redirect');
Route::get('login/{provider}/callback', [UserController::class, 'handleProviderCallback'])->name('login.provider.callback');

Route::get('verify', [UserController::class, 'verify'])->middleware(['throttle:6,1', 'signed'])->name('verification.verify');
Route::get('verify/resend', [UserController::class, 'resendVerification'])->middleware('auth:sanctum', 'throttle:6,1')->name('verification.resend');


Route::get('cart/add', [CartController::class, 'addToCart'])->name('cart.add');
Route::get('cart/show', [CartController::class, 'show'])->name('cart.show');
Route::delete('cart/remove', [CartController::class, 'removeFromCart'])->name('cart.remove');
