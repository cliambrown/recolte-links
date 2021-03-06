<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\LinkReadStatus;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
                    // Retrieve only the current user's read status
                    $query->where('user_id', auth()->user()->id);
                },
                'likes',
            ])
            ->orderBy('created_at', 'desc');
        
        // Store the original user search query for display in the search box
        $origQ = null;
        
        if (isset($_GET['q'])) {
            $q = $_GET['q'];
            // Check for tags in search query (hashtag format)
            $result = preg_match_all('/#([a-zA-Z0-9\-]+)/u', $q, $matches);
            if ($result) {
                $origQ = $_GET['q'];
                foreach ($matches[1] as $tagName) {
                    // Remove tags from search query
                    $q = str_replace('#'.$tagName, '', $q);
                    $tagName = trim($tagName);
                    $tagName = Str::slug($tagName, '-');
                    if (!$tagName) continue;
                    $query->whereHas('tags', function ($query) use ($tagName) {
                        $query->where('name', $tagName);
                    });
                }
            }
            // Check for unread ("is:unread")
            $result = preg_match('/((^|\s)is\:unread(\s|$))/', $q, $matches);
            if ($result === 1 && count($matches) >= 2) {
                $origQ = $_GET['q'];
                // Remove "is:unread" from search query
                $q = str_replace($matches[1], '', $q);
                $query->whereDoesntHave('read_statuses', function ($query) {
                    $query->where('user_id', auth()->user()->id);
                });
            }
            // Check for "from:name" (including partial matches)
            $result = preg_match('/((^|\s)from\:([a-zA-Z]+)(\s|$))/', $q, $matches);
            if ($result === 1 && count($matches) >= 2) {
                $origQ = $_GET['q'];
                // Remove "from:name" from search query
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
        
        // Generate a link to Unread by adding "is:unread" to current search query
        $get = $_GET;
        $get['q'] = trim(data_get($_GET, 'q', '') . ' is:unread');
        $unreadUrl = \URL::current().'?'.http_build_query($get);
        
        // Instead of using "is:liked" in a search query, this is handled as
        // an entirely different route, with a link in the menu.
        // Not sure this is a reasonable decision, but oh well
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
    
    /**
     * Retrieve title & description meta tags for a specified URL.
     *
     * @return \Illuminate\Http\Response
     */
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
            //        <rdf:li xml:lang="x-default">Plan de d????veloppement d'une communaut???? nourrici????re</rdf:li>
            //     </rdf:Alt>
            //  </dc:title>
            // tried to use fopen and fseek from end of file, but got this error:
            //     "fseek(): stream does not support seeking"
            // Could use https://www.pdfparser.org/documentation but it would have to download the entire PDF
            // Probably just need to fopen and fread piece by piece, checking for title tags
        } else {
            $dom = new Dom;
            try {
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
            } catch (Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'title' => $title,
                    'description' => $description,
                ]);
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
        if (get_request_boolean($request->is_short)) {
            $link->is_short = true;
        }
        
        $slackText = '*'.slack_special_chars($link->title).'*'."\n";
        $slackText .= slack_special_chars($link->url)."\n";
        if ($link->is_short) $slackText .= '[short] ';
        $slackText .= slack_special_chars($link->description)."\n";
        $slackText .= '_tags: '.$tagNames->implode(', ').'_';
        
        $response1 = Http::withToken(auth()->user()->slack_token)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => env('SLACK_CHANNEL'),
                'text' => $slackText,
                'as_user' => true,
                'unfurl_links' => false,
                'unfurl_media' => false,
            ]);
        
        $messagePosted = $response1 && $response1->object()
            && isset($response1->object()->ok)
            && ($response1->object()->ok === true || $response1->object()->ok === 'true')
            && isset($response1->object()->ts);
        $response2 = null;
        
        if (!$messagePosted) {
            // TODO differentiate between different Slack problems ??? e.g. token_removed vs missing_scope vs other errors
            // https://api.slack.com/methods/chat.postMessage
            $error = 'Error posting Slack message: '.data_get($response1->object(), 'error', '[unknown error]');
            $needed = data_get($response1->object(), 'needed');
            if ($needed) $error .= '. Needed: '.$needed;
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['msg' => $error])
                ->with('needSlackScope', true);
        } else {
            $link->slack_ts = $response1->object()->ts;
            // Retrieve Slack permalink
            $response2 = Http::withToken(env('SLACK_BOT_TOKEN'))
                ->asForm()
                ->post('https://slack.com/api/chat.getPermalink', [
                    'channel' => env('SLACK_CHANNEL'),
                    'message_ts' => $link->slack_ts,
                ]);
                
        }
        
        $gotPermalink = $response2 && $response2->object()
            && isset($response2->object()->ok)
            && ($response2->object()->ok === true || $response2->object()->ok === 'true')
            && isset($response2->object()->permalink);
        
        if (!$gotPermalink) {
            $error = 'Error getting Slack permalink: '.data_get($response2->object(), 'error', '[unknown error]');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['msg' => $error]);
        } else {
            $link->slack_url = $response2->object()->permalink;
        }
        
        $link->save();
        
        // Attach tags
        $tagIDs = [];
        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIDs[] = $tag->id;
        }
        
        $link->tags()->sync($tagIDs);
        
        LinkReadStatus::create([
            'link_id' => $link->id,
            'user_id' => auth()->user()->id,
        ]);
        
        return redirect()
            ->route('home')
            ->with('status', __('New link saved.'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function edit(Link $link)
    {
        // Only the user who created the link can edit it
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
        // Only the user who created the link can edit it
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
        if (get_request_boolean($request->is_short)) {
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
    
    /**
     * Update a Link in storage with user responses (read/unread, liked/unliked).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
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
    
    /**
     * Show the form for deleting a link.
     *
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function delete(Link $link)
    {
        // Only the user who created the link can delete it
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
        // Only the user who created the link can delete it
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
