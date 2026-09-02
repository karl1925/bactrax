@extends('layouts.app')

@section('title', 'Profile')

@section('page-header')
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">
            Signature Configuration
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Configure your personal digital signing certificate.
        </p>
    </div>
@endsection

@section('content')
    @php
        $signature = $user->signature;
        $hasSignature = $signature !== null;
        $passwordSaved = $hasSignature && !empty($signature->p12_password);
        $validFrom = $hasSignature && $signature->valid_from ? \Carbon\Carbon::parse($signature->valid_from) : null;
        $validUntil = $hasSignature && $signature->valid_until ? \Carbon\Carbon::parse($signature->valid_until) : null;
        $isExpired = $validUntil && $validUntil->isPast();
        $isExpiringSoon = $validUntil && !$isExpired && $validUntil->diffInDays(now()) <= 30;
    @endphp

    <div class="space-y-6">
        <div
            class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-5">
            <div class="p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900 rounded-xl">
                <div class="flex items-start gap-3">
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 shrink-0">
                        <i class="fa-solid fa-shield-halved text-indigo-600 dark:text-indigo-400 text-xs"></i>
                    </div>
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300">
                            About P12 Digital Signing
                        </p>
                        <ol class="list-decimal list-inside space-y-1 text-xs text-indigo-700 dark:text-indigo-400">
                            <li>
                                Uploading your P12 certificate enables you to sign documents
                                online without downloading the PDF first.
                            </li>
                            <li>
                                Your P12 certificate will be kept in private storage and will
                                not be publicly accessible.
                            </li>
                            <li>
                                A P12 password is optional. Saving the password makes signing
                                more convenient while keeping the certificate encrypted.
                                If you do not save the password, you will be prompted for it
                                every time you sign a document.
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            @if (!$hasSignature)
                <form action="{{ route('user.signature.store') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-5">
                    @csrf
                    <div>
                        <label for="p12_certificate"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            P12 Certificate <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" id="p12_certificate" name="p12_certificate" accept=".p12,.pfx" required
                            class="block w-full text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 hover:file:bg-indigo-100">
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                            Accepted formats: .p12 and .pfx
                        </p>
                    </div>

                    <div>
                        <label for="p12_password"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            P12 Password <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="p12_password" name="p12_password" autocomplete="new-password"
                            placeholder="Enter your P12 certificate password"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600"
                            required>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                        <div>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Save my P12 password
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Save the password securely so you are not prompted when signing.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="save_p12_password" name="save_p12_password" value="1"
                                class="sr-only peer">
                            <div
                                class="w-10 h-5 bg-slate-300 dark:bg-slate-700 rounded-full peer peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500/30 peer-checked:bg-indigo-600 transition">
                            </div>
                            <div
                                class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-5">
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
                            <i class="fa-solid fa-shield-halved mr-1.5"></i>
                            Configure
                        </button>
                    </div>
                </form>
            @else
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                P12 Certificate
                            </p>
                            <a href="{{ route('user.signature.download') }}"
                                class="mt-1 flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 truncate">
                                <i class="fa-solid fa-file-shield shrink-0"></i>
                                <span class="truncate">
                                    {{ basename($signature->p12_path) }}
                                </span>
                                <i class="fa-solid fa-download text-[10px] shrink-0"></i>
                            </a>
                        </div>
                        <button type="button" onclick="openReplaceP12Modal()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-[11px] font-semibold transition whitespace-nowrap">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            Replace
                        </button>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Remember my password for future signings
                            </p>
                            @if ($passwordSaved)
                                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                                    <i class="fa-solid fa-circle-check mr-1"></i>
                                    Your P12 password is securely saved.
                                </p>
                            @else
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    You will be prompted for your password when signing.
                                </p>
                            @endif
                        </div>

                        @if ($passwordSaved)
                            {{-- Checked Toggle -> Deletes Saved Password --}}
                            <form action="{{ route('user.signature.password.delete') }}" method="POST"
                                id="disable-saved-password-form">
                                @csrf
                                @method('DELETE')
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox" checked class="sr-only peer"
                                        onchange="toggleSavedPassword(this)">
                                    <div
                                        class="w-10 h-5 bg-slate-300 dark:bg-slate-700 rounded-full peer peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500/30 peer-checked:bg-indigo-600 transition">
                                    </div>
                                    <div
                                        class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-5">
                                    </div>
                                </label>
                            </form>
                        @else
                            {{-- Unchecked Toggle -> Triggers Inline Password Form --}}
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" id="enable-password-toggle" class="sr-only peer"
                                    onchange="togglePasswordForm(this)">
                                <div
                                    class="w-10 h-5 bg-slate-300 dark:bg-slate-700 rounded-full peer peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500/30 peer-checked:bg-indigo-600 transition">
                                </div>
                                <div
                                    class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-5">
                                </div>
                            </label>
                        @endif
                    </div>

                    {{-- Inline Form for Saving Password --}}
                    @if (!$passwordSaved)
                        <div id="saved-password-form"
                            class="hidden mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <form action="{{ route('user.signature.password.update') }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label for="saved_p12_password"
                                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                        P12 Password
                                    </label>
                                    <input type="password" id="saved_p12_password" name="p12_password" required
                                        autocomplete="new-password" placeholder="Enter your P12 certificate password"
                                        class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="cancelPasswordConfiguration()"
                                        class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold">
                                        <i class="fa-solid fa-lock mr-1"></i>
                                        Save Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if ($hasSignature)
            <div
                class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-5">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        Signature Appearance
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Configure how your digital signature will appear on signed documents.
                    </p>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Appearance Preview
                        </label>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">
                            Live Preview
                        </span>
                    </div>
                    <div
                        class="rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-6 overflow-hidden">
                        <div id="signature-preview"
                            class="mx-auto max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg shadow-sm grid grid-cols-2">
                            <div
                                class="flex items-center justify-end p-5 border-r border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div id="signature-preview-image-container"
                                    class="flex items-center justify-end max-h-[70px]">
                                    @if ($signature && filled($signature->signature_image_path))
                                        <img id="signature-preview-image" src="{{ route('user.signature.image') }}"
                                            alt="Signature" class="block w-auto h-auto object-contain max-h-[50px]">
                                    @else
                                        <div id="signature-preview-image-placeholder"
                                            class="flex items-center justify-center max-h-[50px] border border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-2">
                                            <div class="text-center">
                                                <i class="fa-solid fa-signature text-slate-300 dark:text-slate-600"></i>
                                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                                                    Signature image
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-start p-5 overflow-hidden">
                                <p id="signature-preview-text"
                                    class="text-md leading-relaxed text-slate-700 dark:text-slate-200 break-words font-bold">
                                    {{ old('signature_text', $signature->signature_text ?? 'Digitally signed by ' . ($signature->p12_name ?? '')) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                        The preview updates immediately as you change the image or text.
                    </p>
                </div>

                <form action="{{ route('user.signature.appearance.update') }}" method="POST"
                    enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="signature_image"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Signature Image <span class="text-slate-400 font-normal normal-case">(PNG)</span>
                        </label>
                        <input type="file" id="signature_image" name="signature_image" accept="image/png,.png"
                            class="block w-full text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 hover:file:bg-indigo-100">
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                            Upload a PNG image of your handwritten or graphical signature. The aspect ratio will be
                            preserved.
                        </p>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
                            <i class="fa-solid fa-floppy-disk mr-1.5"></i>
                            {{ $signature && filled($signature->signature_image_path) ? 'Update' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>

            @if ($hasSignature && filled($signature->signature_image_path))
                <div
                    class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-5">
                    <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Test Sign a Document
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Upload a PDF document and test the placement of your configured digital signature.
                        </p>
                    </div>

                    <div
                        class="p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900 rounded-xl">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 shrink-0">
                                <i class="fa-solid fa-file-signature text-indigo-600 dark:text-indigo-400 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300">
                                    Test your digital signature
                                </p>
                                <p class="text-[11px] text-indigo-700 dark:text-indigo-400 mt-1">
                                    Select a PDF document, then click or drag on the document to position your signature.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="test_sign_pdf"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            PDF Document <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" id="test_sign_pdf" accept="application/pdf,.pdf"
                            class="block w-full text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 hover:file:bg-indigo-100">
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                            Select a PDF document to test your configured signature.
                        </p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" id="open-test-pdf-button" disabled
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed dark:disabled:bg-slate-700 dark:disabled:text-slate-500 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
                            <i class="fa-solid fa-file-pdf mr-1.5"></i>
                            Open PDF
                        </button>
                    </div>
                </div>
            @endif

            <div
                class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-5">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        P12 Certificate Details
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Information about your currently configured digital signing certificate.
                    </p>
                </div>

                @if ($isExpired)
                    <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation text-rose-600 dark:text-rose-400 mt-0.5"></i>
                            <div>
                                <p class="text-xs font-bold text-rose-800 dark:text-rose-300">
                                    Certificate Expired
                                </p>
                                <p class="text-[11px] text-rose-700 dark:text-rose-400 mt-1">
                                    Your P12 certificate expired on {{ $validUntil->format('F j, Y') }}. Replace your
                                    certificate before attempting to sign documents.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($isExpiringSoon)
                    <div
                        class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400 mt-0.5"></i>
                            <div>
                                <p class="text-xs font-bold text-amber-800 dark:text-amber-300">
                                    Certificate Expiring Soon
                                </p>
                                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1">
                                    Your certificate expires on {{ $validUntil->format('F j, Y') }}. Consider replacing it
                                    before it expires.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Subject
                        </p>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mt-1 break-words">
                            {{ $signature->certificate_subject ?? '—' }}
                        </p>
                    </div>
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Issuer
                        </p>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mt-1 break-words">
                            {{ $signature->certificate_issuer ?? '—' }}
                        </p>
                    </div>
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Serial Number
                        </p>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mt-1 break-words">
                            {{ $signature->certificate_serial ?? '—' }}
                        </p>
                    </div>
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Valid From
                        </p>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mt-1">
                            {{ $validFrom ? $validFrom->format('F j, Y') : '—' }}
                        </p>
                    </div>
                    <div
                        class="p-4 rounded-xl border {{ $isExpired ? 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900' : ($isExpiringSoon ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700') }}">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Valid Until
                        </p>
                        <p
                            class="text-xs font-semibold mt-1 {{ $isExpired ? 'text-rose-600 dark:text-rose-400' : ($isExpiringSoon ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-200') }}">
                            {{ $validUntil ? $validUntil->format('F j, Y') : '—' }}
                        </p>
                    </div>
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Status
                        </p>
                        @if ($isExpired)
                            <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1">
                                <i class="fa-solid fa-circle-xmark mr-1"></i>
                                Expired
                            </p>
                        @elseif ($isExpiringSoon)
                            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                Expiring Soon
                            </p>
                        @else
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                                <i class="fa-solid fa-circle-check mr-1"></i>
                                Active
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- REPLACE P12 MODAL --}}
    <div id="replace-p12-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onclick="closeReplaceP12Modal()"></div>
        <div
            class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50">
                            <i class="fa-solid fa-arrows-rotate text-indigo-600 dark:text-indigo-400 text-xs"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                Replace P12 Certificate
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Upload your new digital signing certificate.
                            </p>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeReplaceP12Modal()"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('user.signature.replace') }}" method="POST" enctype="multipart/form-data"
                class="p-5 space-y-5">
                @csrf
                @method('PUT')
                <div
                    class="p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-shield-halved text-indigo-600 dark:text-indigo-400 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300">
                                Certificate replacement
                            </p>
                            <p class="text-[11px] text-indigo-700 dark:text-indigo-400 mt-1">
                                The new certificate will be validated before replacing your current P12 certificate.
                            </p>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="replace_p12_certificate"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        New P12 Certificate <span class="text-rose-500">*</span>
                    </label>
                    <input type="file" id="replace_p12_certificate" name="p12_certificate" accept=".p12,.pfx"
                        required
                        class="block w-full text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-950">
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="replace_p12_password"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            P12 Password <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="replace_p12_password" name="p12_password" autocomplete="new-password"
                            placeholder="Enter your P12 certificate password"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600"
                            required>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                        <div>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                Remember my password for future signings
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Save the P12 password securely so you are not prompted when signing documents.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="replace_save_p12_password" name="save_p12_password"
                                value="1" class="sr-only peer">
                            <div
                                class="w-10 h-5 bg-slate-300 dark:bg-slate-700 rounded-full peer peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500/30 peer-checked:bg-indigo-600 transition">
                            </div>
                            <div
                                class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-5">
                            </div>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeReplaceP12Modal()"
                        class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-xs font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
                        <i class="fa-solid fa-arrows-rotate mr-1.5"></i>
                        Replace Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TEST PDF SIGNING MODAL --}}
    @if ($hasSignature && filled($signature->signature_image_path))
        <div id="test-pdf-sign-modal" class="hidden fixed inset-0 z-[100]">
            <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-md"></div>
            <div class="absolute inset-0 flex flex-col overflow-hidden">
                <div
                    class="relative z-30 h-14 shrink-0 bg-slate-900/95 backdrop-blur-xl border-b border-slate-700 flex items-center justify-between px-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 shrink-0">
                            <i class="fa-solid fa-file-signature text-white text-xs"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-white truncate">
                                Test Sign a Document
                            </h3>
                            <p id="test-pdf-file-name" class="text-[10px] text-slate-400 truncate">
                                No document selected
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="test-pdf-page-info"
                            class="hidden sm:inline-flex px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-[10px] text-slate-300">
                            Page 1
                        </span>
                        <button type="button" onclick="closeTestPdfModal()"
                            class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div
                    class="relative z-30 h-12 shrink-0 bg-slate-900/95 backdrop-blur-xl border-b border-slate-800 flex items-center justify-center gap-2">
                    <button type="button" id="pdf-prev-page"
                        class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-300">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <span id="pdf-current-page" class="min-w-[90px] text-center text-[11px] font-semibold text-slate-300">
                        Page 1 / 1
                    </span>
                    <button type="button" id="pdf-next-page"
                        class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-300">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>

                <div id="pdf-viewer-viewport"
                    class="relative z-10 flex-1 overflow-auto overscroll-contain bg-slate-950/40 px-0 py-4 touch-pan-x touch-pan-y">
                    <div id="pdf-page-wrapper" class="w-full flex justify-center min-w-full">
                        <div id="pdf-page-container" class="relative h-fit bg-white shadow-2xl shrink-0">
                            <canvas id="pdf-render-canvas" class="block"></canvas>
                            <div id="signature-placement" class="hidden absolute cursor-move select-none">
                                <div class="absolute inset-0 grid grid-cols-2 p-1 overflow-hidden pointer-events-none">
                                    <div class="flex items-center justify-end pr-1 border-0 overflow-hidden">
                                        <img id="pdf-signature-preview" src="{{ route('user.signature.image') }}"
                                            alt="Signature" draggable="false"
                                            class="max-w-full max-h-full object-contain">
                                    </div>
                                    <div class="flex items-center justify-start pl-1 overflow-hidden">
                                        <p id="pdf-signature-preview-text"
                                            class="text-[9px] leading-tight font-bold text-slate-700 break-words">
                                            {{ $signature->signature_text ?? 'Digitally signed by ' . ($signature->p12_name ?? '') }}
                                        </p>
                                    </div>
                                </div>
                                <button type="button" id="cancel-signature-placement"
                                    class="absolute -top-3 -right-3 w-7 h-7 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow-lg z-20">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                                <div
                                    class="absolute right-0 bottom-0 w-3 h-3 bg-indigo-600 border border-white rounded-tl-sm pointer-events-none">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="pdf-placement-help"
                    class="absolute left-1/2 -translate-x-1/2 bottom-5 z-30 px-4 py-2 rounded-xl bg-slate-900/95 border border-slate-700 shadow-xl text-[11px] text-slate-300 pointer-events-none whitespace-nowrap">
                    <i class="fa-solid fa-hand-pointer mr-1.5 text-indigo-400"></i>
                    Click to place the signature or drag to draw a custom rectangle.
                </div>

                <div id="pdf-sign-action"
                    class="hidden absolute bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-xl border-t border-slate-700 px-4 py-3">
                    <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-white">
                                Signature position selected
                            </p>
                            <p id="signature-position-info" class="text-[10px] text-slate-400 mt-0.5">
                                Ready to sign
                            </p>
                        </div>
                        <button type="button" id="test-sign-document-button"
                            class="shrink-0 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-500/20 transition">
                            <i class="fa-solid fa-signature mr-1.5"></i>
                            Sign Document
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>

    <script>
        function toggleSavedPassword(toggle) {
            if (toggle.checked) return;

            const confirmed = confirm('Stop remembering your P12 password for future signings?');
            if (!confirmed) {
                toggle.checked = true;
                return;
            }

            document.getElementById('disable-saved-password-form').submit();
        }

        function togglePasswordForm(toggle) {
            const form = document.getElementById('saved-password-form');
            if (!form) return;

            if (toggle.checked) {
                form.classList.remove('hidden');
                document.getElementById('saved_p12_password')?.focus();
            } else {
                cancelPasswordConfiguration();
            }
        }

        function cancelPasswordConfiguration() {
            const form = document.getElementById('saved-password-form');
            const toggle = document.getElementById('enable-password-toggle');
            const input = document.getElementById('saved_p12_password');

            if (form) form.classList.add('hidden');
            if (toggle) toggle.checked = false;
            if (input) input.value = '';
        }

        function openReplaceP12Modal() {
            const modal = document.getElementById('replace-p12-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => {
                document.getElementById('replace_p12_certificate')?.focus();
            }, 50);
        }

        function closeReplaceP12Modal() {
            const modal = document.getElementById('replace-p12-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('signature_image');
            const previewText = document.getElementById('signature-preview-text');
            const imageContainer = document.getElementById('signature-preview-image-container');

            if (previewText) {
                previewText.textContent = 'Digitally signed by {{ $signature->p12_name ?? '' }}';
            }

            if (!imageInput) return;

            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                if (file.type !== 'image/png') {
                    alert('Please select a PNG image.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    const existingImage = document.getElementById('signature-preview-image');
                    if (existingImage) existingImage.remove();

                    const placeholder = document.getElementById('signature-preview-image-placeholder');
                    if (placeholder) placeholder.remove();

                    const img = document.createElement('img');
                    img.id = 'signature-preview-image';
                    img.src = event.target.result;
                    img.alt = 'Signature';
                    img.className = 'block max-w-full max-h-[40px] w-auto h-auto object-contain';

                    if (imageContainer) imageContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>

    @if ($hasSignature && filled($signature->signature_image_path))
        <script>
            let testPdfFile = null;
            let testPdfDocument = null;
            let testPdfCurrentPage = 1;
            let pdfZoom = 1;

            let signatureRectangle = null;
            let isDrawingSignature = false;
            let wasSignatureDragged = false;
            let ignoreNextClick = false;

            let drawStartX = 0;
            let drawStartY = 0;

            const DEFAULT_SIGNATURE_WIDTH = 220;
            const DEFAULT_SIGNATURE_HEIGHT = 90;

            let testPdfInput;
            let openTestPdfButton;
            let testPdfModal;
            let pdfCanvas;
            let pdfPageContainer;
            let pdfViewerViewport;
            let signaturePlacement;
            let signaturePreview;
            let signAction;
            let placementHelp;
            let pageInfo;
            let fileNameElement;
            let positionInfo;

            let pinchStartDistance = null;
            let pinchStartZoom = 1;

            document.addEventListener('DOMContentLoaded', function() {
                testPdfInput = document.getElementById('test_sign_pdf');
                openTestPdfButton = document.getElementById('open-test-pdf-button');
                testPdfModal = document.getElementById('test-pdf-sign-modal');
                pdfCanvas = document.getElementById('pdf-render-canvas');
                pdfPageContainer = document.getElementById('pdf-page-container');
                pdfViewerViewport = document.getElementById('pdf-viewer-viewport');
                signaturePlacement = document.getElementById('signature-placement');
                signaturePreview = document.getElementById('pdf-signature-preview');
                signAction = document.getElementById('pdf-sign-action');
                placementHelp = document.getElementById('pdf-placement-help');
                pageInfo = document.getElementById('pdf-current-page');
                fileNameElement = document.getElementById('test-pdf-file-name');
                positionInfo = document.getElementById('signature-position-info');

                initializePdfSigning();
            });

            function initializePdfSigning() {
                if (!testPdfInput) return;

                testPdfInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (!file) {
                        testPdfFile = null;
                        if (openTestPdfButton) openTestPdfButton.disabled = true;
                        return;
                    }

                    const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                    if (!isPdf) {
                        alert('Please select a PDF file.');
                        this.value = '';
                        testPdfFile = null;
                        if (openTestPdfButton) openTestPdfButton.disabled = true;
                        return;
                    }
                    testPdfFile = file;
                    if (openTestPdfButton) openTestPdfButton.disabled = false;
                });

                if (openTestPdfButton) {
                    openTestPdfButton.addEventListener('click', async function() {
                        if (!testPdfFile) return;
                        await openTestPdf(testPdfFile);
                    });
                }

                if (pdfCanvas) {
                    pdfCanvas.addEventListener('click', handlePdfClick);
                    pdfCanvas.addEventListener('mousedown', startSignatureDrawing);
                    pdfCanvas.addEventListener('touchstart', startSignatureDrawing, {
                        passive: false
                    });
                }

                document.getElementById('cancel-signature-placement')?.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    clearSignaturePlacement();
                });

                document.getElementById('pdf-prev-page')?.addEventListener('click', async function() {
                    if (!testPdfDocument || testPdfCurrentPage <= 1) return;
                    await renderPdfPage(testPdfCurrentPage - 1, true);
                });

                document.getElementById('pdf-next-page')?.addEventListener('click', async function() {
                    if (!testPdfDocument || testPdfCurrentPage >= testPdfDocument.numPages) return;
                    await renderPdfPage(testPdfCurrentPage + 1, true);
                });

                document.getElementById('test-sign-document-button')?.addEventListener('click', async (event) => {
                    event.preventDefault();
                    const setFieldsResult = await handleSetFields();
                    if (setFieldsResult && currentPreparedPath) {
                        await handleSignDocument('Signature');
                    }
                    closeTestPdfModal();
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key !== 'Escape') return;
                    if (testPdfModal && !testPdfModal.classList.contains('hidden')) {
                        closeTestPdfModal();
                    } else {
                        closeReplaceP12Modal();
                    }
                });

                window.addEventListener('resize', handlePdfViewerResize);

                if (pdfViewerViewport) {
                    pdfViewerViewport.addEventListener('touchstart', handlePinchStart, {
                        passive: false
                    });
                    pdfViewerViewport.addEventListener('touchmove', handlePinchMove, {
                        passive: false
                    });
                    pdfViewerViewport.addEventListener('touchend', handlePinchEnd, {
                        passive: false
                    });
                }
            }

            async function openTestPdf(file) {
                if (!window.pdfjsLib) {
                    alert('PDF viewer could not be loaded.');
                    return;
                }

                try {
                    testPdfModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    fileNameElement.textContent = file.name;

                    pdfZoom = 1;
                    clearSignaturePlacement();

                    const arrayBuffer = await file.arrayBuffer();
                    testPdfDocument = await pdfjsLib.getDocument({
                        data: arrayBuffer
                    }).promise;
                    testPdfCurrentPage = 1;
                    await renderPdfPage(1, true);
                } catch (error) {
                    console.error('Unable to open PDF:', error);
                    alert('Unable to open this PDF document.');
                    closeTestPdfModal();
                }
            }

            function closeTestPdfModal() {
                if (!testPdfModal) return;
                testPdfModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                clearSignaturePlacement();
                testPdfDocument = null;
                testPdfFile = null;
                pdfZoom = 1;
                if (pdfCanvas) {
                    pdfCanvas.width = 0;
                    pdfCanvas.height = 0;
                }
            }

            async function renderPdfPage(pageNumber, pageChanged = false) {
                if (!testPdfDocument) return;

                const page = await testPdfDocument.getPage(pageNumber);
                const scale = calculatePdfScale(page);
                const viewport = page.getViewport({
                    scale: scale
                });

                pdfCanvas.width = Math.floor(viewport.width);
                pdfCanvas.height = Math.floor(viewport.height);
                pdfCanvas.style.width = `${viewport.width}px`;
                pdfCanvas.style.height = `${viewport.height}px`;

                pdfPageContainer.style.width = `${viewport.width}px`;
                pdfPageContainer.style.height = `${viewport.height}px`;

                const context = pdfCanvas.getContext('2d');
                context.clearRect(0, 0, pdfCanvas.width, pdfCanvas.height);
                await page.render({
                    canvasContext: context,
                    viewport: viewport
                }).promise;

                testPdfCurrentPage = pageNumber;
                updatePdfPageControls();

                if (pageChanged) {
                    clearSignaturePlacement();
                }
            }

            function calculatePdfScale(page) {
                const originalViewport = page.getViewport({
                    scale: 1
                });
                const viewerWidth = pdfViewerViewport.clientWidth;
                const horizontalPadding = 16;
                const availableWidth = Math.max(300, viewerWidth - horizontalPadding);
                const fitWidthScale = availableWidth / originalViewport.width;
                return Math.max(fitWidthScale * pdfZoom, 0.5);
            }

            function updatePdfPageControls() {
                if (!testPdfDocument) return;

                if (pageInfo) {
                    pageInfo.textContent = `Page ${testPdfCurrentPage} / ${testPdfDocument.numPages}`;
                }
                const previousButton = document.getElementById('pdf-prev-page');
                const nextButton = document.getElementById('pdf-next-page');

                if (previousButton) previousButton.disabled = testPdfCurrentPage <= 1;
                if (nextButton) nextButton.disabled = testPdfCurrentPage >= testPdfDocument.numPages;
            }

            function getPointerPosition(event) {
                const rect = pdfPageContainer.getBoundingClientRect();
                let clientX, clientY;
                if (event.touches && event.touches.length) {
                    clientX = event.touches[0].clientX;
                    clientY = event.touches[0].clientY;
                } else {
                    clientX = event.clientX;
                    clientY = event.clientY;
                }
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            }

            function handlePdfClick(event) {
                if (ignoreNextClick) {
                    ignoreNextClick = false;
                    return;
                }
                if (wasSignatureDragged) {
                    wasSignatureDragged = false;
                    return;
                }
                if (event.target !== pdfCanvas) return;

                const point = getPointerPosition(event);
                createDefaultSignatureRectangle(point.x, point.y);
            }

            function createDefaultSignatureRectangle(centerX, centerY) {
                let width = DEFAULT_SIGNATURE_WIDTH;
                let height = DEFAULT_SIGNATURE_HEIGHT;

                width = Math.min(width, pdfCanvas.width);
                height = Math.min(height, pdfCanvas.height);

                let x = centerX - width / 2;
                let y = centerY - height / 2;

                x = Math.max(0, Math.min(x, pdfCanvas.width - width));
                y = Math.max(0, Math.min(y, pdfCanvas.height - height));

                createSignatureRectangle(x, y, width, height);
            }

            function startSignatureDrawing(event) {
                if (event.target !== pdfCanvas) return;
                if (event.touches && event.touches.length > 1) return;

                if (event.type === 'touchstart') event.preventDefault();

                isDrawingSignature = true;
                wasSignatureDragged = false;
                ignoreNextClick = false;

                const point = getPointerPosition(event);
                drawStartX = point.x;
                drawStartY = point.y;

                clearSignaturePlacement();
                pdfCanvas.classList.add('cursor-crosshair');

                document.addEventListener('mousemove', drawSignature);
                document.addEventListener('mouseup', finishSignatureDrawing);
                document.addEventListener('touchmove', drawSignature, {
                    passive: false
                });
                document.addEventListener('touchend', finishSignatureDrawing);
            }

            function drawSignature(event) {
                if (!isDrawingSignature) return;
                if (event.type === 'touchmove') event.preventDefault();

                const point = getPointerPosition(event);
                if (Math.abs(point.x - drawStartX) > 5 || Math.abs(point.y - drawStartY) > 5) {
                    wasSignatureDragged = true;
                }

                const rectangle = calculateRectangle(drawStartX, drawStartY, point.x, point.y);
                if (rectangle.width < 10 || rectangle.height < 10) return;

                createSignatureRectangle(rectangle.x, rectangle.y, rectangle.width, rectangle.height);
            }

            function finishSignatureDrawing() {
                if (!isDrawingSignature) return;
                isDrawingSignature = false;

                pdfCanvas.classList.remove('cursor-crosshair');

                document.removeEventListener('mousemove', drawSignature);
                document.removeEventListener('mouseup', finishSignatureDrawing);
                document.removeEventListener('touchmove', drawSignature);
                document.removeEventListener('touchend', finishSignatureDrawing);

                if (wasSignatureDragged) {
                    ignoreNextClick = true;
                    setTimeout(() => {
                        ignoreNextClick = false;
                    }, 150);
                }

                if (!signatureRectangle) {
                    createDefaultSignatureRectangle(drawStartX, drawStartY);
                }
            }

            function calculateRectangle(startX, startY, endX, endY) {
                return {
                    x: Math.min(startX, endX),
                    y: Math.min(startY, endY),
                    width: Math.abs(endX - startX),
                    height: Math.abs(endY - startY)
                };
            }

            function createSignatureRectangle(x, y, width, height) {
                width = Math.max(1, Math.min(width, pdfCanvas.width));
                height = Math.max(1, Math.min(height, pdfCanvas.height));
                x = Math.max(0, Math.min(x, pdfCanvas.width - width));
                y = Math.max(0, Math.min(y, pdfCanvas.height - height));

                signatureRectangle = {
                    x: x,
                    y: y,
                    width: width,
                    height: height,
                    page: testPdfCurrentPage
                };

                signaturePlacement.classList.remove('hidden');
                signaturePlacement.style.left = `${x}px`;
                signaturePlacement.style.top = `${y}px`;
                signaturePlacement.style.width = `${width}px`;
                signaturePlacement.style.height = `${height}px`;

                updateSignaturePreview();
                updateSignaturePosition();

                signAction.classList.remove('hidden');
                placementHelp.classList.add('hidden');
            }

            function updateSignaturePreview() {
                if (!signatureRectangle || !signaturePreview) return;

                const imageRatio = signaturePreview.naturalWidth && signaturePreview.naturalHeight ?
                    signaturePreview.naturalWidth / signaturePreview.naturalHeight :
                    2.5;

                const boxRatio = signatureRectangle.width / signatureRectangle.height;

                if (imageRatio > boxRatio) {
                    signaturePreview.style.width = '90%';
                    signaturePreview.style.height = 'auto';
                } else {
                    signaturePreview.style.height = '90%';
                    signaturePreview.style.width = 'auto';
                }
            }

            function updateSignaturePosition() {
                if (!signatureRectangle) return;
                if (positionInfo) {
                    positionInfo.textContent =
                        `Page ${signatureRectangle.page} · ` +
                        `X: ${Math.round(signatureRectangle.x)} · ` +
                        `Y: ${Math.round(signatureRectangle.y)} · ` +
                        `W: ${Math.round(signatureRectangle.width)} · ` +
                        `H: ${Math.round(signatureRectangle.height)}`;
                }
            }

            function clearSignaturePlacement() {
                signatureRectangle = null;
                if (signaturePlacement) signaturePlacement.classList.add('hidden');
                if (signAction) signAction.classList.add('hidden');
                if (placementHelp) placementHelp.classList.remove('hidden');
                if (positionInfo) positionInfo.textContent = 'Ready to sign';
            }

            function handlePinchStart(event) {
                if (event.touches.length !== 2) return;
                event.preventDefault();
                pinchStartDistance = getTouchDistance(event);
                pinchStartZoom = pdfZoom;
            }

            function handlePinchMove(event) {
                if (event.touches.length !== 2 || !pinchStartDistance) return;
                event.preventDefault();

                const currentDistance = getTouchDistance(event);
                if (!currentDistance) return;

                const ratio = currentDistance / pinchStartDistance;
                let newZoom = pinchStartZoom * ratio;
                newZoom = Math.max(1, Math.min(newZoom, 4));

                if (Math.abs(newZoom - pdfZoom) < 0.03) return;

                pdfZoom = newZoom;
                const oldRectangle = signatureRectangle ? {
                    ...signatureRectangle
                } : null;

                renderPdfPage(testPdfCurrentPage, false).then(() => {
                    if (!oldRectangle || !signatureRectangle) return;
                    const scaleRatio = pdfZoom / pinchStartZoom;
                    createSignatureRectangle(
                        oldRectangle.x * scaleRatio,
                        oldRectangle.y * scaleRatio,
                        oldRectangle.width * scaleRatio,
                        oldRectangle.height * scaleRatio
                    );
                });
            }

            function handlePinchEnd(event) {
                if (event.touches.length < 2) pinchStartDistance = null;
            }

            function getTouchDistance(event) {
                if (!event.touches || event.touches.length < 2) return null;
                const touch1 = event.touches[0];
                const touch2 = event.touches[1];
                const dx = touch2.clientX - touch1.clientX;
                const dy = touch2.clientY - touch1.clientY;
                return Math.sqrt(dx * dx + dy * dy);
            }

            async function handlePdfViewerResize() {
                if (!testPdfModal || testPdfModal.classList.contains('hidden') || !testPdfDocument) return;

                const oldRectangle = signatureRectangle ? {
                    ...signatureRectangle
                } : null;
                const oldCanvasWidth = pdfCanvas.width;

                await renderPdfPage(testPdfCurrentPage, false);

                if (!oldRectangle || !oldCanvasWidth) return;
                const ratio = pdfCanvas.width / oldCanvasWidth;

                createSignatureRectangle(
                    oldRectangle.x * ratio,
                    oldRectangle.y * ratio,
                    oldRectangle.width * ratio,
                    oldRectangle.height * ratio
                );
            }

            let currentPreparedPath = null;

            async function handleSetFields() {
                try {
                    const pdfFileInput = document.getElementById('test_sign_pdf');
                    if (!pdfFileInput || !pdfFileInput.files[0]) {
                        alert('Please select a PDF file first.');
                        return null;
                    }

                    if (!signatureRectangle) {
                        alert('Please select or draw a signature position on the PDF viewer.');
                        return null;
                    }

                    const pdfPage = await testPdfDocument.getPage(signatureRectangle.page);
                    const unscaledViewport = pdfPage.getViewport({
                        scale: 1.0
                    });

                    const fields = [{
                        field: 'Signature',
                        page: signatureRectangle.page,
                        display_x: signatureRectangle.x,
                        display_y: signatureRectangle.y,
                        display_width: signatureRectangle.width,
                        display_height: signatureRectangle.height,
                        display_page_width: pdfCanvas.width,
                        display_page_height: pdfCanvas.height,
                        pdf_page_width: unscaledViewport.width,
                        pdf_page_height: unscaledViewport.height
                    }];

                    const formData = new FormData();
                    formData.append('pdf', pdfFileInput.files[0]);
                    formData.append('fields', JSON.stringify(fields));

                    const response = await fetch('/user/signature/set-fields', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        currentPreparedPath = data.path;
                        return data;
                    }

                    return null;
                } catch (error) {
                    console.error('SET FIELDS ERROR:', error);
                    alert('An error occurred while preparing signature fields.');
                    return null;
                }
            }

            async function handleSignDocument(fieldName) {
                try {
                    if (!currentPreparedPath) {
                        throw new Error('No prepared PDF path found. Please set fields first.');
                    }

                    const pdfUrl = `/user/signature/prepared`;
                    const pdfResponse = await fetch(pdfUrl);

                    if (!pdfResponse.ok) {
                        throw new Error(`Failed to download prepared PDF. Status: ${pdfResponse.status}`);
                    }

                    const pdfBlob = await pdfResponse.blob();
                    const pdfFile = new File([pdfBlob], "prepared.pdf", {
                        type: "application/pdf"
                    });

                    const formData = new FormData();
                    formData.append('pdf', pdfFile);
                    formData.append('field', fieldName);

                    const response = await fetch("{{ route('user.signature.sign') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData // or JSON data depending on your payload
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Failed to sign document.');
                    }

                    window.location.href = result.url;
                } catch (error) {
                    console.log(error);
                    alert('For this signing test, please use an unsigned PDF');
                    if (currentPreparedPath) {
                        await cleanupPreparedFile(currentPreparedPath);
                        currentPreparedPath = null;
                    }
                }
            }

            async function cleanupPreparedFile(path) {
                try {
                    await fetch('/user/signature/delete-prepared', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            path: path
                        })
                    });
                } catch (cleanupError) {
                    console.error('Failed to clean up prepared file:', cleanupError);
                }
            }

            function extractErrorMessage(data, status, fallbackPrefix) {
                let message = data?.message;

                if (data?.errors && typeof data.errors === 'object') {
                    const validationMessages = Object.values(data.errors).flat().filter(Boolean);
                    if (validationMessages.length) {
                        message = validationMessages.join('\n');
                    }
                }

                if (message && typeof message === 'object') {
                    message = JSON.stringify(message, null, 2);
                }

                if (!message) {
                    message = `${fallbackPrefix} with HTTP ${status}.`;
                }

                return message;
            }

            window.closeTestPdfModal = closeTestPdfModal;
        </script>
    @endif
@endpush
