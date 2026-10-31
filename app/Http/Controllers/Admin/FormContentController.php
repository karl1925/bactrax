<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormContentController extends Controller
{
    private const CONTENT_FIELDS = [
        'forms_header' => 'Shared Forms Header',
        'rfq_notice' => 'RFQ Notice',
        'rfq_instructions' => 'RFQ Instructions',
        'rfq_terms' => 'RFQ Terms and Conditions',
    ];

    public function index(): View
    {
        $content = [];
        foreach (self::CONTENT_FIELDS as $key => $label) {
            $content[$key] = Setting::get($key, '');
        }

        return view('admin.form-cms.index', compact('content'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'forms_header' => ['nullable', 'string', 'max:100000'],
            'rfq_notice' => ['nullable', 'string', 'max:100000'],
            'rfq_instructions' => ['nullable', 'string', 'max:100000'],
            'rfq_terms' => ['nullable', 'string', 'max:100000'],
        ]);

        foreach (self::CONTENT_FIELDS as $key => $label) {
            Setting::set($key, $validated[$key] ?? '', 'form_cms', $label);
        }

        return redirect()
            ->route('admin.form-cms.index')
            ->with('success', 'Form content settings saved.');
    }
}
