@props(['href', 'title', 'description' => ''])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'block rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-emerald-700 dark:focus:ring-offset-slate-900']) }}>
    <span class="block text-base font-semibold text-slate-950 dark:text-white">{{ $title }}</span>
    @if ($description)
        <span class="mt-2 block text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $description }}</span>
    @endif
</a>
