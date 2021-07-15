<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\LinkReadStatus;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPHtmlParser\Dom;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Link::with([
                'tags',
                'user',
                'read_statuses' => function ($query) {
                    $query->where('user_id', auth()->user()->id);
                },
                'likes',
            ])
            ->orderBy('created_at', 'desc');
        
        $origQ = null;
        if (isset($_GET['q'])) {
            $q = $_GET['q'];
            // Check for tags
            $result = preg_match_all('/#([a-zA-Z0-9\-]+)/u', $q, $matches);
            if ($result) {
                $origQ = $_GET['q'];
                foreach ($matches[1] as $tagName) {
                    $q = str_replace('#'.$tagName, '', $q);
                    $tagName = trim($tagName);
                    $tagName = Str::slug($tagName, '-');
                    if (!$tagName) continue;
                    $query->whereHas('tags', function ($query) use ($tagName) {
                        $query->where('name', $tagName);
                    });
                }
            }
            // Check for unread
            $result = preg_match('/((^|\s)is\:unread(\s|$))/', $q, $matches);
            if ($result === 1 && count($matches) >= 2) {
                $origQ = $_GET['q'];
                $q = str_replace($matches[1], '', $q);
                $query->whereDoesntHave('read_statuses', function ($query) {
                    $query->where('user_id', auth()->user()->id);
                });
            }
            // Check for from:user
            $result = preg_match('/((^|\s)from\:([a-zA-Z]+)(\s|$))/', $q, $matches);
            if ($result === 1 && count($matches) >= 2) {
                $origQ = $_GET['q'];
                $q = str_replace($matches[1], '', $q);
                $fromName = $matches[3];
                $query->whereHas('user', function ($query) use ($fromName) {
                    $query->where('name', 'like', '%'.$fromName.'%');
                });
            }
            // Search using remainder of query
            $q = trim(preg_replace('/\s+/', ' ',$q));
            if ($q) {
                $origQ = $_GET['q'];
                $query->where(function ($query) use ($q) {
                    $query->where('title', 'like', '%'.$q.'%')
                            ->orWhere('description', 'like', '%'.$q.'%')
                            ->orWhere('url', 'like', '%'.$q.'%');
                });
            }
        }

        $get = $_GET;
        if (isset($get['q'])) {
            $q = $get['q'];
            $result = preg_match('/((^|\s)is\:unread(\s|$))/', $q, $matches);
            if ($result !== 1) {
                $get['q'] = trim($q.' is:unread');
            }
        } else {
            $get['q'] = 'is:unread';
        }
        $unreadUrl = \URL::current().'?'.http_build_query($get);
        
        $liked = request()->routeIs('liked');
        if ($liked) {
            $query->whereHas('likes', function ($query) {
                $query->where('user_id', auth()->user()->id);
            });
        }
        
        $links = $query->paginate(10);
        
        $data = [
            'links' => $links,
            'origQ' => $origQ,
            'unreadUrl' => $unreadUrl,
            'liked' => $liked,
        ];
        
        return view('links.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $allTags = Tag::withCount('links')
            ->orderBy('links_count', 'desc')
            ->orderBy('name', 'asc')
            ->get();
        return view('links.create', ['allTags' => $allTags]);
    }
    
    public function get_url_metadata(Request $request) {
        
        $title = '';
        $description = '';
        
        $url = $request->url;
        
        $isPdf = false;
        $headers = get_headers($url, 1);
        if (isset($headers['Content-Type']) && is_string($headers['Content-Type'])) {
            $contentType = trim($headers['Content-Type']);
            $pdfTypes = ['application/pdf','application/x-pdf','application/acrobat','applications/vnd.pdf','text/pdf','text/x-pdf'];
            if (in_array($contentType, $pdfTypes, true)) {
                $isPdf = true;
            }
        }
        
        if ($isPdf) {
            // TODO handle PDF files without downloading the whole file. Example near end of file:
            // <dc:title>
            //     <rdf:Alt>
            //        <rdf:li xml:lang="x-default">Plan de dÃ©veloppement d'une communautÃ© nourriciÃ¨re</rdf:li>
            //     </rdf:Alt>
            //  </dc:title>
            // tried to use fopen and fseek from end of file, but got this error:
            // fseek(): stream does not support seeking
            // Could use https://www.pdfparser.org/documentation but it would have to download the entire PDF
            // Probably just need to fopen and fread piece by piece, checking for title tags
        } else {
            $dom = new Dom;
            $dom->loadFromFile($url);
            $titleEls = $dom->find('title');
            if ($titleEls && isset($titleEls[0])) {
                $titleEl = $titleEls[0];
                $title = mb_substr(html_entity_decode($titleEl->text), 0, 100);
            }
            $descEls = $dom->find('meta[name="description"]');
            if ($descEls && isset($descEls[0])) {
                $descEl = $descEls[0];
                $description = mb_substr(html_entity_decode($descEl->getAttribute('content')), 0, 1000);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $request->validate([
            'url' => 'required|url',
            'title' => 'required|string',
            'description' => 'required|string',
            'is_short' => 'nullable|boolean',
            'tags' => 'required|string',
        ]);
        
        $tagsArr = explode(',', $request->tags);
        $tagNames = collect([]);
        foreach ($tagsArr as $tagName) {
            $tagName = trim($tagName);
            $tagName = Str::slug($tagName, '-');
            if (!$tagName) continue;
            $tagNames->push($tagName);
        }
        $tagNames = $tagNames->uniqueStrict();
        
        $link = new Link;
        $link->user_id = auth()->user()->id;
        $link->url = $request->url;
        $link->title = $request->title;
        $link->description = $request->description;
        if ($request->is_short) {
            $link->is_short = true;
        }
        
        $slackText = '*'.$link->title.'*'."\n";
        $slackText .= $link->url."\n";
        if ($link->is_short) $slackText .= '[short] ';
        $slackText .= $link->description;
        $slackText .= 'tags: '.$tagNames->implode(', ');
        
        $response1 = Http::withToken(auth()->user()->slack_token)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => env('SLACK_CHANNEL'),
                'text' => $slackText,
                'as_user' => true,
                'unfurl_links' => false,
                'unfurl_media' => false,
            ]);
        
        $messagePosted = isset($response1->object()->ok)
            && ($response1->object()->ok === true || $response1->object()->ok === 'true')
            && isset($response1->object()->ts);
        $response2 = null;
        
        if ($messagePosted) {
            $link->slack_ts = $response1->object()->ts;
            $response2 = Http::withToken(env('SLACK_BOT_TOKEN'))
                ->asForm()
                ->post('https://slack.com/api/chat.getPermalink', [
                    'channel' => env('SLACK_CHANNEL'),
                    'message_ts' => $link->slack_ts,
                ]);
        } else {
            $error = 'Error posting Slack message: '.data_get($response1->object(), 'error', '[unknown error]');
            return redirect()
                ->back()
                ->withInput($request->input())
                ->withErrors(['msg' => $error]);
        }
        
        $gotPermalink = isset($response2->object()->ok)
            && ($response2->object()->ok === true || $response2->object()->ok === 'true')
            && isset($response2->object()->permalink);
        
        if ($gotPermalink) {
            $link->slack_url = $response2->object()->permalink;
        } else {
            $error = 'Error getting Slack permalink: '.data_get($response2->object(), 'error', '[unknown error]');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['msg' => $error]);
        }
        
        $link->save();
        
        $tagIDs = [];
        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIDs[] = $tag->id;
        }
        
        $link->tags()->sync($tagIDs);
        
        return redirect()
            ->route('home')
            ->with('status', __('New link saved.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function show(Link $link)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function edit(Link $link)
    {
        if ($link->user_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }
        $allTags = Tag::withCount('links')
            ->orderBy('links_count', 'desc')
            ->orderBy('name', 'asc')
            ->get();
        return view('links.edit', ['link' => $link, 'allTags' => $allTags]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Link $link)
    {
        if ($link->user_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'url' => 'required|url',
            'title' => 'required|string',
            'description' => 'required|string',
            'is_short' => 'nullable|boolean',
            'tags' => 'required|string',
        ]);
        
        $tagsArr = explode(',', $request->tags);
        $tagNames = collect([]);
        foreach ($tagsArr as $tagName) {
            $tagName = trim($tagName);
            $tagName = Str::slug($tagName, '-');
            if (!$tagName) continue;
            $tagNames->push($tagName);
        }
        $tagNames = $tagNames->uniqueStrict();
        
        $link->user_id = auth()->user()->id;
        $link->url = $request->url;
        $link->title = $request->title;
        $link->description = $request->description;
        if ($request->is_short) {
            $link->is_short = true;
        } else {
            $link->is_short = null;
        }
        $link->save();
        
        $tagIDs = [];
        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIDs[] = $tag->id;
        }
        
        $link->tags()->sync($tagIDs);
        
        return redirect()
            ->back()
            ->with('status', __('Link updated.'));
    }
    
    public function api_update(Request $request, Link $link) {
        $request->validate([
            'update_unread' => 'nullable|boolean',
            'unread' => 'nullable|boolean',
            'update_liked' => 'nullable|boolean',
            'liked' => 'nullable|boolean',
        ]);
        
        $updateUnread = $request->update_unread;
        $unread = $request->unread;
        if ($updateUnread) {
            if ($unread) {
                $link->read_statuses()->where('user_id', auth()->user()->id)->delete();
                $response = Http::withToken(auth()->user()->slack_token)
                    ->post('https://slack.com/api/reactions.remove', [
                        'channel' => env('SLACK_CHANNEL'),
                        'name' => 'heavy_check_mark',
                        'timestamp' => $link->slack_ts,
                    ]);
            } else {
                $link->read_statuses()->firstOrCreate(['user_id' => auth()->user()->id]);
                $response = Http::withToken(auth()->user()->slack_token)
                    ->post('https://slack.com/api/reactions.add', [
                        'channel' => env('SLACK_CHANNEL'),
                        'name' => 'heavy_check_mark',
                        'timestamp' => $link->slack_ts,
                    ]);
            }
        }
        
        $updateLiked = $request->update_liked;
        $liked = $request->liked;
        if ($updateLiked) {
            if ($liked) {
                $link->likes()->firstOrCreate(['user_id' => auth()->user()->id]);
                $response = Http::withToken(auth()->user()->slack_token)
                    ->post('https://slack.com/api/reactions.add', [
                        'channel' => env('SLACK_CHANNEL'),
                        'name' => 'thumbsup',
                        'timestamp' => $link->slack_ts,
                    ]);
            } else {
                $link->likes()->where('user_id', auth()->user()->id)->delete();
                $response = Http::withToken(auth()->user()->slack_token)
                    ->post('https://slack.com/api/reactions.remove', [
                        'channel' => env('SLACK_CHANNEL'),
                        'name' => 'thumbsup',
                        'timestamp' => $link->slack_ts,
                    ]);
            }
            $link->loadCount('likes');
        }
        
        return response()->json([
            'status' => 'success',
            'unread' => $unread,
            'liked' => $liked,
            'likes_count' => $link->likes_count,
        ]);
    }
    
    public function slack_event(Request $request) {
        // This was needed for initial url validation
        // return response()->json(['challenge' => $request->challenge]);
        
        Log::info('Incoming Slack event');
        
        $error = null;
        
        $type = data_get($request, 'type');
        if ($type !== 'reaction_added' && $type !== 'reaction_removed') $error = 'Invalid event type: '.$type;
        if (!$error) {
            $reaction = data_get($request, 'reaction');
            if ($reaction !== 'thumbsup' && $reaction !== 'heavy_check_mark') $error = 'Invalid reaction: '.$reaction;
        }
        if (!$error) {
            $channel = data_get($request, 'item.channel');
            if ($channel !== env('SLACK_CHANNEL')) $error = 'Invalid channel: '.$channel;
        }
        if (!$error) {
            $slackID = data_get($request, 'user');
            if (!$slackID) $error = 'Could not retrieve Slack ID from payload.';
        }
        if (!$error) {
            $user = User::where('slack_id', $slackID)->first();
            if (!$user) $error = 'User not found. Slack ID: '.$slackID;
        }
        if (!$error) {
            $slackTS = data_get($request, 'item.ts');
            if (!$slackTS) $error = 'Could not retrieve Slack timestamp from payload.';
        }
        if (!$error) {
            $link = Link::where('slack_ts', $slackTS)->first();
            if (!$link) $error = 'Link not found. Slack TS: '.$slackTS;
        }
        
        if ($error) {
            Log::info('Slack event error: '.$error);
        } else {
            if ($reaction === 'thumbsup') {
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
    
    public function delete(Link $link)
    {
        if ($link->user_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }
        return view('links.delete', ['link' => $link]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function destroy(Link $link)
    {
        if ($link->user_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }
        $link->tags()->sync([]);
        $link->read_statuses()->delete();
        $link->likes()->delete();
        $link->delete();
        return redirect()
            ->route('home')
            ->with('status', __('Link deleted.'));
    }
}
