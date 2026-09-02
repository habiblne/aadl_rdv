@props(['title', 'subtitle' => ''])

<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $title }}</h1>
    @if ($subtitle)
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $subtitle }}</p>
    @endif
</div>
