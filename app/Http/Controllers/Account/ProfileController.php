<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ContactDetailsRequest;
use App\Http\Requests\Account\ProfileUpdateRequest;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The signed-in user's own profile, privacy settings and contact details.
 * There is no user id in these URLs, so a user can only ever change their own account.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.profile.show', ['user' => $request->user()->load(['division', 'district', 'upazila'])]);
    }

    public function edit(Request $request): View
    {
        return view('account.profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'division_id' => $data['division_id'],
            'district_id' => $data['district_id'],
            'upazila_id' => $data['upazila_id'],
        ]);

        $oldAvatar = $user->avatar_path;

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar_path = null;
        }

        $user->save();

        if ($oldAvatar && $oldAvatar !== $user->avatar_path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('profile.show')->with('success', 'Your profile was updated.');
    }

    public function privacy(Request $request): View
    {
        return view('account.settings.privacy', ['user' => $request->user()]);
    }

    public function updatePrivacy(Request $request): RedirectResponse
    {
        $settings = ['show_avatar', 'show_contributions', 'show_verified_badge', 'matched_messages_only'];
        $request->validate(array_fill_keys($settings, ['nullable', 'boolean']));

        // An unticked checkbox is not sent at all, so a missing value means "off".
        $request->user()->update(collect($settings)->mapWithKeys(fn ($key) => [$key => $request->boolean($key)])->all());

        return back()->with('success', 'Your privacy settings were saved.');
    }

    public function security(Request $request): View
    {
        return view('account.settings.security', ['user' => $request->user()]);
    }

    /**
     * Change the email address and/or phone number. A changed contact must be verified again.
     */
    public function updateContact(ContactDetailsRequest $request, VerificationCodeService $codes): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($data['email'] !== $user->email) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }

        if ($data['phone'] !== $user->phone) {
            $user->phone = $data['phone'];
            $user->phone_verified_at = null;
        }

        if (! $user->isDirty()) {
            return back()->with('status', 'Nothing changed.');
        }

        $user->save();

        if (! $user->isVerified()) {
            $result = $codes->send($user);

            return redirect()->route('verification.notice')
                ->with('verification', $result)
                ->with('success', 'Your contact details were changed. Please verify them to continue.');
        }

        return back()->with('success', 'Your contact details were updated.');
    }
}
