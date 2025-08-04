@props(['class' => 'w-16 h-16'])

<svg {{ $attributes->merge(['class' => $class . ' text-gray-800 dark:text-gray-200']) }} viewBox="0 0 48 48" fill="none"
    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <!-- Logo simple "SH" estilizado -->
    <rect x="4" y="4" width="40" height="40" rx="10" class="fill-current opacity-10"></rect>
    <path d="M16 30c2 2 6 2 8 0s2-6 0-8-6-2-8 0M32 18c-2-2-6-2-8 0s-2 6 0 8 6 2 8 0" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    <text x="24" y="28" text-anchor="middle" font-size="10" class="fill-current">SH</text>
</svg>