<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcurementMode\Form;
use App\Models\Setting;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'pr_form' => Setting::get('pr_form'),
            'pr_prefix' => Setting::get('pr_prefix', 'PR-'),
            'pr_next_number' => Setting::get('pr_next_number', 1),
            'pr_number_padding' => Setting::get('pr_number_padding', 5),
            'pr_signatories' => json_decode(Setting::get('pr_signatories', '["end_user","budget_officer","hope"]'), true) ?: ['end_user', 'budget_officer', 'hope'],
        ];

        // Fetch all published forms for the dropdown selection
        $forms = Form::where('status', 'published')->orderBy('name', 'asc')->get();
        $signatoryRoles = Role::orderBy('name')->get();

        return view('admin.settings.index', compact('settings', 'forms', 'signatoryRoles'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'pr_form' => ['required', Rule::exists('forms', 'id')->where('status', 'published')],
            'pr_prefix' => ['required', 'string', 'max:16'],
            'pr_next_number' => ['required', 'integer', 'min:1'],
            'pr_number_padding' => ['required', 'integer', 'min:1', 'max:10'],
            'pr_signatories' => ['required', 'array', 'size:3'],
            'pr_signatories.*' => ['required', 'distinct', 'exists:roles,name'],
        ]);

        Setting::set('pr_form', $request->input('pr_form'), 'procurement', 'Active Purchase Request Form Template');
        Setting::set('pr_prefix', strtoupper(trim($request->input('pr_prefix'))), 'procurement', 'PR Tracking Number Prefix');
        Setting::set('pr_next_number', $request->input('pr_next_number'), 'procurement', 'Next PR Sequence Number');
        Setting::set('pr_number_padding', $request->input('pr_number_padding'), 'procurement', 'PR Number Zero Padding Length');
        Setting::set('pr_signatories', json_encode(array_values($request->input('pr_signatories'))), 'procurement', 'Ordered PR form signatory roles');

        return back()->with('success', 'Procurement form and tracking settings updated successfully.');
    }
}
