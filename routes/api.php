<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\StructureController;

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
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/get_sponsor', [AuthController::class, 'get_sponsor']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/profile', [AuthController::class, 'userProfile']);    
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'structure'
], function ($router) {
    Route::post('/get', [StructureController::class, 'index']);  
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'products'
], function ($router) {
    Route::get('/get', [ProductController::class, 'index']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'cart'
], function ($router) {
    Route::post('/get_orders', [OrderController::class, 'get_orders']);
    Route::post('/add_product', [OrderController::class, 'plus_product_basket']);
    Route::post('/plus_product', [OrderController::class, 'plus_product_basket']);
    Route::post('/minus_product', [OrderController::class, 'minus_product_basket']);
    Route::post('/change_product_count', [OrderController::class, 'change_product_count_in_basket']);
    Route::post('/delete_product', [OrderController::class, 'delete_product_basket']);
    Route::get('/get_payment_and_delivery_types', [OrderController::class, 'get_payment_and_delivery_types']);
    Route::get('/get_countries_for_delivery_and_pickup', [OrderController::class, 'get_countries_for_delivery_and_pickup']);
    Route::post('/get_regions_for_delivery', [OrderController::class, 'get_regions_for_delivery']);
    Route::post('/get_cities_for_delivery', [OrderController::class, 'get_cities_for_delivery']);
    Route::post('/get_cities_for_pickup', [OrderController::class, 'get_cities_for_pickup']);
    Route::post('/place_an_order', [OrderController::class, 'place_an_order']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'marketing'
], function ($router) {
    Route::group([
        'prefix' => 'get'
    ], function($router) {
        Route::get('/status_types', [MarketingController::class, 'get_status_types']);
        Route::get('/recruiting_data', [MarketingController::class, 'recruiting_data']);
    });
    Route::group([
        'prefix' => 'pay'
    ], function($router) {
        Route::post('/recruiting', [MarketingController::class, 'recruiting_pay']);
    });

    Route::group([
        'prefix' => 'act'
    ], function($router) {
        Route::post('/structure_building', [MarketingController::class, 'structure_building']);
        Route::post('/retail', [MarketingController::class, 'retail']);
        Route::post('/mentor', [MarketingController::class, 'mentor']);
        Route::post('/leader_ship', [MarketingController::class, 'leader_ship']);
        Route::post('/independent', [MarketingController::class, 'independent']);
    });
});