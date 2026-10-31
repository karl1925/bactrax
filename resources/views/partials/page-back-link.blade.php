@php
    $fallbackHref = $href ?? route('dashboard');
    $previousHref = url()->previous($fallbackHref);
    $previousHost = parse_url($previousHref, PHP_URL_HOST);
    $isInternalPrevious = !$previousHost || strcasecmp($previousHost, request()->getHost()) === 0;
    $backHref = $isInternalPrevious && $previousHref !== request()->fullUrl() ? $previousHref : $fallbackHref;
    $discardEnabled = ($discard ?? false) || isset($discardTitle);
@endphp
<a href="{{ $backHref }}" @if($discardEnabled) data-discard-url="{{ $backHref }}" data-discard-title="{{ $discardTitle ?? 'Leave this page?' }}" data-discard-message="{{ $discardMessage ?? 'Any unsaved changes will be discarded.' }}" @endif class="mb-3 inline-flex w-fit items-center gap-2 rounded-lg px-2 py-1 text-xs font-bold text-slate-500 transition hover:bg-white hover:text-blue-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-blue-300">
    <i class="fa-solid fa-arrow-left"></i><span>{{ $label ?? 'Back' }}</span>
</a>
