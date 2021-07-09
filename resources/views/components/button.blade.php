@props([
    'type' => 'submit',
    'btncolor' => 'black',
    'padding' => 'normal',
    'uppercase' => true,
])

<{{ $attributes->has('href') ? 'a' : 'button type='.$type }}
    {{ $attributes->class([
        // Colors
        'text-white bg-gray-800 hover:bg-gray-700 active:bg-gray-900' => $btncolor === 'black',
        'text-white bg-purple-800 hover:bg-purple-700 active:bg-purple-900' => $btncolor === 'purple',
        'text-white bg-pink-800 hover:bg-pink-700 active:bg-pink-900' => $btncolor === 'pink',
        'text-white bg-yellow-600 hover:bg-yellow-700 active:bg-yellow-900' => $btncolor === 'yellow',
        'text-white bg-red-600 hover:bg-red-700 active:bg-red-900' => $btncolor === 'red',
        'text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-900' => $btncolor === 'blue',
        'text-white bg-green-700 hover:bg-green-800 active:bg-green-900' => $btncolor === 'green',
        'text-blue-800 bg-blue-200 hover:bg-blue-300 active:bg-blue-300' => $btncolor === 'light-blue',
        '' => $btncolor === 'transparent',
        // Padding
        'px-4 py-3' => $padding === 'normal',
        'p-2' => $padding === 'tight',
        '' => $padding === 'none',
        // Case
        'text-xs uppercase tracking-widest' => $uppercase,
        'text-sm' => !$uppercase,
    ])
    ->merge(['class' => 'inline-flex align-middle items-center justify-center border border-transparent rounded-md font-semibold focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</{{ $attributes->has('href') ? 'a' : 'button' }}>
