<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Delete Link') }}
        </h2>
    </x-slot>
    
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    
    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    
    <form method="POST" action="{{ route('links.destroy', ['link' => $link->id]) }}">
        
        @csrf
        
        @method('delete')
        
        <div class="mb-6 pl-4 border-l-4 border-blue-300">
            <div class="font-semibold mb-2">
                {{ $link->title }}
            </div>
            <div class="overflow-hidden overflow-ellipsis text-gray-700 mb-2 whitespace-nowrap">
                {{ $link->description }}
            </div>
            <div class="font-semibold mb-2 text-purple-600">
                {{ $link->url }}
            </div>
        </div>
        
        
        <p class="mb-4">
           Are you sure you want to delete this link? This cannot be undone.
        </p>
        
        <p class="mb-8">
            Note: you may want to <a href="{{ $link->slack_url }}" target="_blank" class="text-blue-700 hover:text-blue-900 transition-all">delete the link on Slack <x-icons.open class="w-4 relative bottom-0.5"></x-icons.open></a> as well.
        </p>
        
        <div>
            <x-button btncolor="red">
                Delete
            </x-button>
        </div>
        
    </form>
    
</x-app-layout>
