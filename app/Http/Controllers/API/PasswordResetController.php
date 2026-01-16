<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    // SEND RESET TOKEN
    public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ]);
    }

    $token = Str::random(64);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'token' => $token,
            'created_at' => Carbon::now()
        ]
    );

    $resetLink = url('/reset-password?token='.$token.'&email='.$request->email);

    Mail::send([], [], function ($message) use ($request, $resetLink) {
        $message->to($request->email)
            ->subject('Reset Password')
            ->html("
                <p>Click the button below to reset your password:</p>
                <a href='$resetLink' 
                   style='padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:5px;'>
                   Reset Password
                </a>
            ");
    });

    return response()->json([
        'status' => true,
        'message' => 'Reset password link sent to email'
    ]);
}

   
    // RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:6'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token'
            ]);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

            // ✅ THIS IS THE KEY LINE
    return back()->with('success', 'Password reset successful');
    }
}
