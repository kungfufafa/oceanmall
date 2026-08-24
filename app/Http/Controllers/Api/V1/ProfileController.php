<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

final class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);
        $user->fill($data);
        $user->save();

        return response()->json([
            'data' => $this->payload($user->fresh()),
        ]);
    }

    public function updatePassword(PasswordUpdateRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        return response()->json(['message' => 'Kata sandi diperbarui.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset kata sandi sudah dikirim.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            static function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Reset kata sandi gagal.'], 422);
        }

        return response()->json(['message' => 'Kata sandi berhasil direset. Masuk lagi di aplikasi.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ];
    }
}
