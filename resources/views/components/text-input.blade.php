@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400 dark:disabled:bg-slate-800 dark:disabled:text-slate-500']) }}>
