@props(['title' => 'Sin datos', 'hint' => null, 'action' => null])

<div class="text-center rounded-lg border border-dashed p-8 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</h3>
    @if($hint)
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
    @if($action)
    <div class="mt-4">
        {{ $action }}
    </div>
    @endif
</div>