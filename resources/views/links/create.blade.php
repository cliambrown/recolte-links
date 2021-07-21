<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Link') }}
        </h2>
    </x-slot>
    
    @if (session('needSlackScope'))
        <p class="text-center mb-2">
            You need to give permission for this app to post to Slack on your behalf:
        </p>
        <p class="text-center mb-2">
            <x-button href="{{ route('slack.scope_redirect') }}" target="_blank">
                Authorize
            </x-button>
        </p>
    @else
        
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />
        
        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />
    
    @endif
    
    <form method="POST" action="{{ route('links.store') }}">
        
        @csrf
        
        <div x-data="urlMetaScraper('{{ addslashes(old('url')) }}', '{{ addslashes(old('title')) }}', '{{ addslashes(old('description')) }}')" x-init="checkUrl(url); $watch('url', value => checkUrl(value))">
            
            <div class="mb-6">
                <x-label for="url" :value="__('URL')" />
                <x-input id="url" class="block mt-1 w-full" type="text" name="url" x-model="url" required autofocus />
            </div>
            
            <div class="mb-6">
                <x-button type="button" btncolor="green" x-on:click="fetchUrlMetadata(url)" x-bind:disabled="loading || !is_valid_url">
                    <x-icons.bulb class="w-4 -my-2 relative bottom-[1px] -left-1"></x-icons.bulb>
                    Get info
                </x-button>
                <x-icons.sync class="animate-spin w-6 -my-2 ml-2" x-show="loading" x-transition x-cloak></x-icons.sync>
            </div>
            
            <div class="mb-6">
                <x-label for="title" :value="__('Title')" />
                <x-input id="title" class="block mt-1 w-full max-w-2xl" type="text" name="title" x-model="title" maxlength="100" required />
            </div>
            
            <div class="mb-6">
                <x-label for="description" :value="__('Description')" />
                <div class="text-purple-800 text-sm">
                    En quoi cela intéresserait-il vos collègues ?
                </div>
                <textarea id="description"
                    class="autoresize w-full mt-1 rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    name="description"
                    maxlength="1000"
                    x-model="description"
                    x-init="resizeTextarea($el)"
                    x-on:input="resizeTextarea($event.target)"
                    required
                    ></textarea>
            </div>
            
        </div>
        
        <div x-data="{ is_short: {{ old('is_short') ? 'true' : 'false' }} }" class="mb-6">
            Long read
            <label for="is_short" class="inline-block w-12 h-6 mx-2 -my-2 rounded-full relative transition-all cursor-pointer focus-within:ring focus-within:ring-indigo-500" :class="{ 'bg-gray-400': !is_short, 'bg-green-600': is_short }" tabindex="-1">
                <input type="checkbox" id="is_short" class="peer sr-only" x-model="is_short" value="1">
                <div class="bg-white rounded-full w-4 h-4 absolute top-1/2 -translate-y-1/2 transition-transform translate-x-1" :class="{ 'translate-x-1': !is_short, 'translate-x-7': is_short }"></div>
            </label>
            Short read
        </div>
        
        <div class="mb-6" x-data="tagList('{{ addslashes(old('tags')) }}', {{ $allTags }})">
            <x-label for="tags" :value="__('Tags')" />
            <div class="text-purple-800 text-sm">
                Separated by commas
            </div>
            <x-input id="tags" class="block mt-1 w-full max-w-2xl" type="text" name="tags" x-model="tags" maxlength="100" required />
            <div class="mt-2">
                @foreach ($allTags as $tag)
                    <button type="button" x-on:click="toggleTag('{{ $tag->name }}')" class="text-green-700 px-1 mr-1 rounded underline hover:text-green-900 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all">{{ $tag->name }}</button>
                @endforeach
            </div>
        </div>
        
        <div>
            <x-button btncolor="blue">
                Save
            </x-button>
        </div>
        
    </form>
    
</x-app-layout>
