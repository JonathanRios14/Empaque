<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $relativePath = 'profile-photos/' . $filename;

            // Delete old photo if exists
            if ($user->photo) {
                if (Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }
                $oldPublicPhoto = public_path('storage/' . $user->photo);
                if (File::exists($oldPublicPhoto)) {
                    @File::delete($oldPublicPhoto);
                }
            }

            // Save to storage/app/public/profile-photos
            $file->storeAs('profile-photos', $filename, 'public');

            // Also copy to public/storage/profile-photos in case symlink is missing on production server
            $publicDir = public_path('storage/profile-photos');
            if (!File::isDirectory($publicDir)) {
                File::makeDirectory($publicDir, 0777, true, true);
            }
            @copy($file->getRealPath(), $publicDir . '/' . $filename);

            $user->photo = $relativePath;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
