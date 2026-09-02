@props(['label'])

<div class="border-b border-slate-100 py-2 last:border-0 sm:grid sm:grid-cols-3 sm:gap-4 dark:border-slate-700">
    <dt class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $label }}</dt>
    <dd class="mt-1 text-sm font-semibold text-slate-950 sm:col-span-2 sm:mt-0 dark:text-white">{{ $slot }}</dd>
</div>
