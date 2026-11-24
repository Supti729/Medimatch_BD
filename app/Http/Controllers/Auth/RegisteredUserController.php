<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Division;
use App\Models\User;
use App\Services\VerificationCodeService;
use App\Support\ImageSanitizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'divisions' => Division::with('districts.upazilas')->orderBy('name')->get(),
        ]);
    }

    public function store(RegisterRequest $request, VerificationCodeService $codes): RedirectResponse
    {
        $data = $request->validated();

        // Only these fields are taken from the form. role and status keep their safe defaults.
        $user = DB::transaction(fn () => User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'division_id' => $data['division_id'],
            'district_id' => $data['district_id'],
            'upazila_id' => $data['upazila_id'],
        ]));

        if ($request->hasFile('avatar')) {
            // Stored with a random file name; shown on profiles from push B2.
            $user->avatar_path = ImageSanitizer::store($request->file('avatar'), 'avatars'); // strips GPS/EXIF metadata
            $user->save();
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $result = $codes->send($user);

        return redirect()->route('verification.notice')
            ->with('verification', $result)
            ->with('success', 'Your account was created. One more step: verify your '.($result['channel'] === 'email' ? 'email address' : 'phone number').'.');
    }
}
