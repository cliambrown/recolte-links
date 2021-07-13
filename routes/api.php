<?php

use App\Http\Controllers\LinkController;
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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::group(['middleware' => 'auth:sanctum'], function(){

Route::group(['middleware' => 'auth:sanctum'], function() {
    
    Route::post('/get_url_metadata', [LinkController::class, 'get_url_metadata']);
    Route::post('/links/{link}/update', [LinkController::class, 'api_update']);
    
});