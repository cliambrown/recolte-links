<?php

use App\Http\Controllers\LinkController;
use App\Http\Controllers\SlackController;
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

Route::middleware(['auth'])->group(function () {
    
    Route::get('/', [LinkController::class, 'index'])->name('home');
    Route::resource('links', LinkController::class)->except(['index','show']);
    Route::get('/links/{link}/delete', [LinkController::class, 'delete'])->name('links.delete');
    
    Route::get('/liked', [LinkController::class, 'index'])->name('liked');
    
    // DEBUG
    // Route::get('/get_url_metadata', [LinkController::class, 'get_url_metadata']);
    
});

Route::name('slack.')->group(function () {
    Route::get('/auth/redirect', [SlackController::class, 'auth_redirect'])->name('auth_redirect');
    Route::get('/auth/callback', [SlackController::class, 'auth_callback'])->name('auth_callback');
    Route::get('/slack/scope-redirect', [SlackController::class, 'scope_redirect'])->name('scope_redirect');
    Route::get('/slack/scope-callback', [SlackController::class, 'scope_callback'])->name('scope_callback');
    Route::post('/slack-event-endpoint', [SlackController::class, 'slack_event'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('event_endpoint');
});

require __DIR__.'/auth.php';
