<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-md border border-transparent bg-emerald-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 active:bg-emerald-900 disabled:opacity-50 dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>
