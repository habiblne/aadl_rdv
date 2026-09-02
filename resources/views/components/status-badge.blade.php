@props(['status'])

@php
    $classes = match ((int) $status) {
        \App\Models\Rdv::STATUT_RDV_PRIS => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-800',
        \App\Models\Rdv::STATUT_RDV_ACCEPTE => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-800',
        \App\Models\Rdv::STATUT_RDV_VALIDE => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-800',
        \App\Models\Rdv::STATUT_RDV_COMPLETE => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:ring-slate-600',
        default => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:ring-slate-600',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
