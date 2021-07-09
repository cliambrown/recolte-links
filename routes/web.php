<?php

use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    
    Route::get('/', [LinkController::class, 'index'])->name('home');
    Route::resource('links', LinkController::class)->except(['index']);
    
    // Route::get('/get_url_metadata', [LinkController::class, 'get_url_metadata']);
    
});

require __DIR__.'/auth.php';
