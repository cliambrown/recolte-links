<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\LinkReadStatus;
use App\Models\User;
use App\Models\UserLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SlackController extends Controller
{
    
    public function auth_redirect() {
        return Socialite::driver('slack')->redirect();
    }
    
    public function auth_callback() {
        if (isset($_GET['error'])) {
            return redirect()->route('home');
        }
        try {
            $slackUser = Socialite::driver('slack')->user();
        } catch (Exception $e) {
            if (isset($_GET['error'])) {
                return redirect()->route('home');
            }
        }
        if ($slackUser->organization_id !== env('SLACK_TEAM_ID')) {
            return redirect()
                ->route('login')
                ->withErrors(['msg' => 'Your Slack account does not seem to be a part of our team. Org id: '.$slackUser->organization_id.' Required ID: '.env('SLACK_TEAM_ID')]);
        }
        $user = User::updateOrCreate(
            ['slack_id' => $slackUser->getId()],
            [
                'name' => $slackUser->getName(),
                'email' => $slackUser->getEmail(),
                'slack_token' => $slackUser->token,
            ]
        );
        Auth::login($user, true);
        return redirect()->route('home');
    }
    
    public function scope_redirect() {
        // https://slack.com/oauth/v2/authorize?client_id=372244571046.2283825560753&scope=chat:write,reactions:write&user_scope=chat:write,reactions:read,reactions:write
        $state = Str::random(40);
        $params = [
            'scope' => 'chat:write,reactions:write',
            'user_scope' => 'chat:write,reactions:write',
            'client_id' => env('SLACK_CLIENT_ID'),
            'redirect_uri' => route('slack.scope_callback'),
            'state' => $state,
        ];
        $url = 'https://slack.com/oauth/v2/authorize?'.http_build_query($params);
        session()->put('state', $state);
        return redirect()->away($url);
    }
    
    public function scope_callback() {
        // TODO handle errors better
        $code = data_get($_GET, 'code');
        if (!$code) return 'Authentication failed: No code.';
        $state = data_get($_GET, 'state');
        if ($state !== session('state')) return 'Authentication failed: Invalid state.';
        $response = Http::asForm()
            ->accept('application/json')
            ->post('https://slack.com/api/oauth.v2.access', [
                'client_secret' => env('SLACK_CLIENT_SECRET'),
                'client_id' => env('SLACK_CLIENT_ID'),
                'code' => $code,
                'redirect_uri' => route('slack.scope_callback'),
            ]);
        if (!$response) return 'Authentication failed: No response.';
        if (!$response->ok()) return 'Authentication error: Not ok.';
        $json = $response->json();
        $token = data_get($json, 'access_token');
        if (!$token) return 'Authentication failed: no token.';
        $user = auth()->user();
        $user->update(['slack_token' => $token]);
        return 'Authentication successful. Please close this tab and retry your post.';
    }
    
    /**
     * Endpoint for a Slack API event, which the Slack Bot is subscribed to.
     * https://api.slack.com/apps
     *  > [app]
     *  > Event Subscriptions
     *  > "Subscribe to events on behalf of users"
     *  > "reaction_added" and "reaction_removed"
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function slack_event(Request $request) {
        
        // This was needed for initial url validation when setting up the app
        // return response()->json(['challenge' => $request->challenge]);
        
        Log::info('Incoming Slack event: '.json_encode($request->all()));
        
        $error = null;
        
        $event = data_get($request, 'event');
        if (!$event) $error = 'Could not retrieve event.';
        if (!$error) {
            $type = data_get($event, 'type');
            if ($type !== 'reaction_added' && $type !== 'reaction_removed') $error = 'Invalid event type: '.$type;
        }
        if (!$error) {
            $reaction = data_get($event, 'reaction');
            if ($reaction !== 'thumbsup' && $reaction !== '+1' && $reaction !== 'heavy_check_mark') {
                $error = 'Invalid reaction: '.$reaction;
            }
        }
        if (!$error) {
            $channel = data_get($event, 'item.channel');
            if ($channel !== env('SLACK_CHANNEL')) $error = 'Invalid channel: '.$channel;
        }
        if (!$error) {
            $slackID = data_get($event, 'user');
            if (!$slackID) $error = 'Could not retrieve Slack ID from payload.';
        }
        if (!$error) {
            $user = User::where('slack_id', $slackID)->first();
            if (!$user) $error = 'User not found. Slack ID: '.$slackID;
        }
        if (!$error) {
            $slackTS = data_get($event, 'item.ts');
            if (!$slackTS) $error = 'Could not retrieve Slack timestamp from payload.';
        }
        if (!$error) {
            $link = Link::where('slack_ts', $slackTS)->first();
            if (!$link) $error = 'Link not found. Slack TS: '.$slackTS;
        }
        
        if ($error) {
            Log::info('Slack event error: '.$error);
        } else {
            if ($reaction === 'thumbsup' || $reaction === '+1') {
                if ($type === 'reaction_added') {
                    UserLike::firstOrCreate([
                        'link_id' => $link->id,
                        'user_id' => $user->id,
                    ]);
                } else {
                    UserLike::where('link_id', $link->id)
                        ->where('user_id', $user->id)
                        ->delete();
                }
            } elseif ($reaction === 'heavy_check_mark') {
                if ($type === 'reaction_added') {
                    LinkReadStatus::firstOrCreate([
                        'link_id' => $link->id,
                        'user_id' => $user->id,
                    ]);
                } else {
                    LinkReadStatus::where('link_id', $link->id)
                        ->where('user_id', $user->id)
                        ->delete();
                }
            }
        }
        
        return response('OK', 200)
            ->header('Content-Type', 'text/plain')
            ->header('X-Slack-No-Retry', 1);
    }
}
