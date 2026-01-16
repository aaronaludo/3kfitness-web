<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\UserQrCode;
use App\Traits\ResolvesActiveMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    use ResolvesActiveMembership;

    public function issue(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ((int) ($user->role_id ?? 0) === 3) {
            $activeMembership = $this->resolveActiveMembershipForUser($user);

            if (! $activeMembership) {
                return response()->json([
                    'message' => 'Your membership has expired. Please renew to access your QR code.',
                ], 403);
            }
        }

        UserQrCode::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $token = $this->generateToken();

        $record = UserQrCode::create([
            'user_id' => $user->id,
            'token' => $token,
            'issued_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'token' => $record->token,
            'issued_at' => optional($record->issued_at)->toIso8601String(),
        ]);
    }

    private function generateToken(): string
    {
        do {
            $token = Str::random(40);
        } while (UserQrCode::where('token', $token)->exists());

        return $token;
    }
}
