<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'pr_prefix' => Setting::get('pr_prefix', 'PR-'),
            'pr_next_number' => Setting::get('pr_next_number', 1),
            'pr_number_padding' => Setting::get('pr_number_padding', 5), // Default set to 5
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'pr_prefix' => ['required', 'string', 'max:16'],
            'pr_next_number' => ['required', 'integer', 'min:1'],
            'pr_number_padding' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        Setting::set('pr_prefix', strtoupper(trim($request->input('pr_prefix'))), 'procurement', 'PR Tracking Number Prefix');
        Setting::set('pr_next_number', $request->input('pr_next_number'), 'procurement', 'Next PR Sequence Number');
        Setting::set('pr_number_padding', $request->input('pr_number_padding'), 'procurement', 'PR Number Zero Padding Length');

        return back()->with('success', 'System procurement tracking number settings updated successfully.');
    }
}
