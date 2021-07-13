<x-app-layout>
    
    <div class="text-center mb-8">
        <x-button href="{{ route('links.create'); }}" btncolor="green">
            <x-icons.plus class="w-5 -my-2 relative bottom-[1px] -left-1"></x-icons.plus>
            Add Link
        </x-button>
    </div>
    
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    
    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    
    @foreach ($links as $link)
        <div x-data="link({{ $link->id }}, {{ $link->unread ? 'true' : 'false' }}, {{ $link->liked ? 'true' : 'false' }}, {{ $link->likes->count() }})" class="mb-4 px-6 py-4 bg-white shadow-md overflow-hidden border-l-4 border-transparent opacity-75 link" :class="{ 'unread': unread }">
            <div class="float-right ml-2 mb-2">
                <x-button btncolor="transparent" x-bind:title="(unread ? 'Mark read' : 'Mark unread')" x-on:click="toggleUnread" x-bind:class="{ 'text-green-600': !unread, 'text-purple-500': unread }">
                    <x-icons.eye x-show="unread" class="w-6 -m-2"></x-icons.eye>
                    <x-icons.eye-off x-show="!unread" class="w-6 -m-2 cloak"></x-icons.eye-off>
                </x-button>
                <x-button btncolor="transparent" x-bind:title="(liked ? 'Remove like' : 'Like this link')" x-on:click="toggleLiked" x-bind:class="{ 'text-gray-600': !liked, 'text-pink-600': liked }">
                    <x-icons.heart-filled x-show="liked" class="w-6 -m-2"></x-icons.heart-filled>
                    <x-icons.heart x-show="!liked" class="w-6 -m-2 cloak"></x-icons.heart>
                </x-button>
                <span class="inline-block w-8 text-left text-sm" x-text="likes_count"></span>
            </div>
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
            <div class="text-gray-600 text-sm">
                {{ $link->user->name }} — {{ $link->created_at->diffForHumans() }} &nbsp;
                @foreach ($link->tags as $tag)
                    <a href="/?tag={{ urlencode($tag->name) }}" class="inline-block bg-gray-200 text-green-800 font-semibold text-sm px-2 py-0.5 mr-1 rounded hover:text-green-900 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all">{{ $tag->name }}</a>
                @endforeach
            </div>
        </div>
    @endforeach
    
    {{ $links->links() }}
    
</x-app-layout>
