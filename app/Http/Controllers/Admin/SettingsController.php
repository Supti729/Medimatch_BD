<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => Setting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(Setting::MAP)->map(fn ($m) => $m[1] === 'bool' ? ['nullable', 'boolean'] : ['required', 'integer', "min:{$m[2]}", "max:{$m[3]}"])->all();
        $data = $request->validate($rules);

        $before = Setting::current();
        foreach (Setting::MAP as $key => [, $type]) {
            Setting::put($key, $type === 'bool' ? $request->boolean($key) : (int) $data[$key]);
        }
        Setting::applyToConfig();

        $changed = collect(Setting::current())->filter(fn ($value, $key) => $value != $before[$key]);
        if ($changed->isNotEmpty()) {
            AuditLog::record('settings.updated', null, 'Changed settings: '.$changed->keys()->implode(', '), ['before' => $before, 'after' => Setting::current()]);
        }

        return back()->with('success', $changed->isEmpty() ? 'Nothing changed.' : 'Settings saved.');
    }
}
