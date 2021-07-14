<x-app-layout>
    
    <div class="sm:flex sm:justify-between mb-8">
        
        <x-button href="{{ route('links.create'); }}" btncolor="green" class="mr-4 whitespace-nowrap">
            <x-icons.plus class="w-5 -my-2 relative bottom-[1px] -left-1"></x-icons.plus>
            Add Link
        </x-button>
        
        <x-button :href="$toggleUnreadUrl" btncolor="transparent" class="ml-auto text-green-700 hover:text-green-900">
            Show {{ $showUnread ? 'all' : 'unread' }}
        </x-button>
        
        <form method="GET" action="./" class="block max-w-md mt-4 sm:mt-0 relative">
            <label for="q" class="sr-only">Search Links</label>
            <x-input id="q" class="block w-full pr-10" type="text" name="q" value="{{ $origQ }}" />
            <x-button btncolor="transparent" class="absolute inset-y-0 right-0">
                <x-icons.search class="w-5 -m-1"></x-icons.search>
            </x-button>
        </form>
        
    </div>
    
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    
    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    
    @if ($origQ)
        <div class="text-purple-600 mb-4">
            Search results for "<span class="font-semibold">{{ $origQ }}</span>"
            <a href="./" class="text-green-700 ml-3 hover:text-green-900 transition-all">
                <x-icons.close class="w-6 -my-2 relative bottom-0.5 -mr-0.5"></x-icons.close>
                Clear
            </a>
        </div>
    @endif
    
    @foreach ($links as $link)
        <div x-data="link({{ $link->id }}, {{ $link->unread ? 'true' : 'false' }}, {{ $link->liked ? 'true' : 'false' }}, {{ $link->likes->count() }})" class="mb-4 px-6 py-4 bg-white shadow-md overflow-hidden border-l-4 border-transparent opacity-75 link" :class="{ 'unread': unread }">
            
            @if ($link->is_short)
                <div class="float-left bg-purple-700 text-white font-semibold rounded-sm px-2 py-0.5 text-xs mr-2 relative top-0.5">
                    short
                </div>
            @endif
            
            <div class="font-semibold">{{ $link->title }}</div>
            
            <a href="{{ $link->url }}" class="text-blue-700 hover:text-blue-900 transition-all inline-flex max-w-full">
                <div class="overflow-hidden overflow-ellipsis">{{ $link->url }}</div>
                <div class="pl-2"><x-icons.open class="w-4 -my-2 relative bottom-0.5"></x-icons.open></div>
            </a>
            
            <div class="mb-1">
                {!! nl2br(e($link->description), false) !!}
            </div>
            
            <div class="sm:flex sm:items-end">
                
                <div class="text-gray-600 text-sm">
                    {{ $link->user->name }} — {{ $link->created_at->diffForHumans() }} &nbsp;
                    @foreach ($link->tags as $tag)
                        <a href="/?q=%23{{ urlencode($tag->name) }}" class="inline-block bg-gray-200 text-green-800 font-semibold text-sm px-2 py-0.5 mr-1 my-1 rounded hover:text-green-900 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all">{{ $tag->name }}</a>
                    @endforeach
                </div>
                
                <div class="ml-auto whitespace-nowrap text-right">
                    @if ($link->user_id == auth()->user()->id)
                        <x-button btncolor="transparent" :href="route('links.edit', ['link' => $link->id])" class="text-blue-700 hover:text-blue-900">
                            <x-icons.edit class="w-6 -m-2"></x-icons.edit>
                        </x-button>
                    @endif
                    <x-button btncolor="transparent" x-bind:title="(unread ? 'Mark read' : 'Mark unread')" x-on:click="toggleUnread" x-bind:class="{ 'text-green-600': !unread, 'text-purple-500': unread }">
                        <x-icons.eye x-show="unread" class="w-6 -m-2"></x-icons.eye>
                        <x-icons.eye-off x-show="!unread" class="w-6 -m-2 cloak"></x-icons.eye-off>
                    </x-button>
                    <x-button btncolor="transparent" x-bind:title="(liked ? 'Remove like' : 'Like this link')" x-on:click="toggleLiked" x-bind:class="{ 'text-gray-600': !liked, 'text-pink-600': liked }">
                        <x-icons.heart-filled x-show="liked" class="w-6 -m-2"></x-icons.heart-filled>
                        <x-icons.heart x-show="!liked" class="w-6 -m-2 cloak"></x-icons.heart>
                    </x-button>
                    <span class="inline-block w-8 text-left text-sm" x-text="likes_count"></span>
                    <x-button btncolor="transparent" :href="$link->slack_url" target="_blank">
                        <x-icons.chatbox class="w-6 -m-2"></x-icons.chatbox>
                    </x-button>
                </div>
                
            </div>
            
        </div>
    @endforeach
    
    @if (!$links->count())
        No links to show.
    @endif
    
    {{ $links->links() }}
    
</x-app-layout>
