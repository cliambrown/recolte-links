<?php

use App\Http\Controllers\LinkController;
use App\Http\Controllers\SocialController;
use App\Models\User;
use Illuminate\Support\Facades\Http;
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

Route::middleware(['auth'])->group(function () {
    
    Route::get('/', [LinkController::class, 'index'])->name('home');
    Route::resource('links', LinkController::class)->except(['index','show']);
    Route::get('/links/{link}/delete', [LinkController::class, 'delete'])->name('links.delete');
    
    Route::get('/liked', [LinkController::class, 'index'])->name('liked');
    
    // DEBUG
    // Route::get('/get_url_metadata', [LinkController::class, 'get_url_metadata']);
    
});

Route::get('/auth/redirect', function () {
    $socialite = Socialite::driver('slack')->redirect();
    $url = $socialite->getTargetUrl();
    $urlParts = parse_url($url);
    parse_str($urlParts['query'], $params);
    $params['user_scope'] = 'chat:write,reactions:write';
    $url = $urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'?'.http_build_query($params);
    $socialite->setTargetUrl($url);
    return $socialite;
    
    
    
    // dd($url);
    
    
    
    // // dd($socialite->getTargetUrl());
    // dd($socialite->setTargetUrl('test'));
    
    
    // dd(Socialite::driver('slack')->redirect());
    // // return Socialite::driver('slack')->redirect();
    // $userScopes = [
    //     'chat:write',
    //     'reactions=:write',
    // ];
    // $params = [
    //     'user_scope' => urlencode(implode(',',$userScopes)),
    //     'client_id' => urlencode(env('SLACK_CLIENT_ID')),
    //     // 'redirect_uri' => urlencode(env('SLACK_REDIRECT_CALLBACK_URL')),
    // ];
    // $url = 'https://slack.com/oauth/v2/authorize?'.http_build_query($params);
    // dd($url);
    // return redirect()->away($url);
});

Route::get('/auth/callback', function () {
    if (isset($_GET['error'])) {
        return redirect()->route('home');
    }
    $slackUser = Socialite::driver('slack')->user();
    if ($slackUser->organization_id !== env('SLACK_TEAM_ID')) {
        return redirect()
            ->route('login')
            ->withErrors(['msg' => 'Your Slack account does not seem to be a part of our team. Org id: '.$slackUser->organization_id.' Required ID: '.env('SLACK_TEAM_ID')]);
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

Route::post('/slack-event-endpoint', [LinkController::class, 'slack_event'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/slack-event-endpoint', [LinkController::class, 'slack_event'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__.'/auth.php';
