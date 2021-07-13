<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\LinkReadStatus;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        if (isset($_GET['tag'])) {
            $query->whereHas('tags', function ($query) {
                $query->where('name', $_GET['tag']);
            });
        }
        $links = $query->cursorPaginate(20);
        
        return view('links.index', ['links' => $links]);
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
        
        $link = new Link;
        $link->user_id = auth()->user()->id;
        $link->url = $request->url;
        $link->title = $request->title;
        $link->description = $request->description;
        if ($request->is_short) {
            $link->is_short = true;
        }
        
        $slackText = '*'.$link->title.'*'."\n";
        $slackText .= $link->description."\n";
        $slackText .= $link->url;
        
        $response1 = Http::post('https://slack.com/api/chat.postMessage', [
            'token' => env('SLACK_BOT_TOKEN'),
            'channel' => env('SLACK_CHANNEL'),
            'text' => $slackText,
            'unfurl_links' => false,
            'unfurl_media' => false,
        ]);
        
        $messagePosted = isset($response1->object()->ok)
            && ($response1->object()->ok === true || $response1->object()->ok === 'true')
            && isset($response1->object()->ts);
        $response2 = null;
        $response3 = null;
        
        if ($messagePosted) {
            
            $link->ts = $response1->object()->ts;
            
            $response2 = Http::post('https://slack.com/api/chat.postMessage', [
                'token' => env('SLACK_BOT_TOKEN'),
                'channel' => env('SLACK_CHANNEL'),
                'text' => 'posted by '.auth()->user()->name,
                'thread_ts' => $link->ts,
            ]);
            
            $response3 = Http::post('https://slack.com/api/chat.getPermalink', [
                'token' => env('SLACK_BOT_TOKEN'),
                'channel' => env('SLACK_CHANNEL'),
                'message_ts' => $link->ts,
            ]);
            
        } else {
            // respond with $response3->object()->error
        }
        
        $gotPermalink = isset($response3->object()->ok)
            && ($response3->object()->ok === true || $response3->object()->ok === 'true')
            && isset($response3->object()->permalink);
        
        if ($gotPermalink) {
            $link->slack_url = $response3->object()->permalink;
        } else {
            // respond with $response3->object()->error
        }
        
        $link->save();
        
        $tagsArr = explode(',', $request->tags);
        $tagIDs = [];
        foreach ($tagsArr as $tagName) {
            $tagName = trim($tagName);
            if (!$tagName) continue;
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
        //
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
        //
    }
    
    public function api_update(Request $request, Link $link) {
        $request->validate([
            'unread' => 'nullable|boolean',
            'liked' => 'nullable|boolean',
        ]);
        
        $unread = $request->unread;
        if ($unread) {
            $link->read_statuses()->where('user_id', auth()->user()->id)->delete();
        } else {
            $link->read_statuses()->firstOrCreate(['user_id' => auth()->user()->id]);
        }
        
        $liked = $request->liked;
        if ($liked) {
            $link->likes()->firstOrCreate(['user_id' => auth()->user()->id]);
        } else {
            $link->likes()->where('user_id', auth()->user()->id)->delete();
        }
        
        $link->loadCount('likes');
        
        return response()->json([
            'status' => 'success',
            'unread' => $unread,
            'liked' => $liked,
            'likes_count' => $link->likes_count,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Http\Response
     */
    public function destroy(Link $link)
    {
        //
    }
}
