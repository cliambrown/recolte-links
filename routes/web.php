<?php

use App\Http\Controllers\LinkController;
use App\Http\Controllers\SocialController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

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
    Route::get('/links/{link}/delete', [LinkController::class, 'delete'])->name('links.delete');
    
    Route::get('/liked', [LinkController::class, 'index'])->name('liked');
    
    // DEBUG
    // Route::get('/get_url_metadata', [LinkController::class, 'get_url_metadata']);
    
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('slack')->redirect();
});

Route::get('/auth/callback', function () {
    $slackUser = Socialite::driver('slack')->user();
    if ($slackUser->organization_id !== env('SLACK_TEAM_ID')) {
        return redirect()
            ->route('login')
            ->withErrors(['msg' => 'Your Slack account does not seem to be a part of our team.']);
    }
    $user = User::firstOrCreate(
        ['slack_id' => $slackUser->getId()],
        [
            'name' => $slackUser->getName(),
            'email' => $slackUser->getEmail(),
            'slack_token' => $slackUser->token,
        ]
    );
    Auth::login($user, true);
    return redirect()->route('home');
});

Route::post('/slack-event-endpoint', [LinkController::class, 'slack_event']);

require __DIR__.'/auth.php';
