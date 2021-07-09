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
        <div class="mb-4 px-6 py-4 bg-white shadow-md overflow-hidden rounded-lg">
            @if ($link->is_short)
                <div class="float-left bg-purple-600 text-white font-semibold rounded-sm px-2 py-0.5 text-xs mr-2 relative top-0.5">
                    short
                </div>
            @endif
            <div class="font-semibold">{{ $link->title }}</div>
            <a href="{{ $link->url }}" class="text-blue-600 hover:text-blue-900 transition-all inline-flex max-w-full">
                <div class="overflow-hidden overflow-ellipsis">{{ $link->url }}</div>
                <div class="pl-2"><x-icons.open class="w-4 -my-2 relative bottom-0.5"></x-icons.open></div>
            </a>
            <div class="mb-1">
                {!! nl2br(e($link->description), false) !!}
            </div>
            <div class="text-gray-600 text-sm">
                {{ $link->user->name }} — {{ $link->created_at->diffForHumans() }} &nbsp;
                @foreach ($link->tags as $tag)
                    <a href="/?tag={{ urlencode($tag->name) }}" class="inline-block bg-gray-200 text-green-700 font-semibold text-sm px-2 py-0.5 mr-1 rounded hover:text-green-900 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all">{{ $tag->name }}</a>
                @endforeach
            </div>
        </div>
    @endforeach
    
    {{ $links->links() }}
    
</x-app-layout>
