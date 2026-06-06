<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $email = strtolower(trim((string) env('MOBILE_CEO_BOOTSTRAP_EMAIL', 'admin@gmail.com')));
        $name = trim((string) env('MOBILE_CEO_BOOTSTRAP_NAME', 'Admin Mobile'));
        $password = (string) env('MOBILE_CEO_BOOTSTRAP_PASSWORD', 'password');

        if ($email === '' || $password === '') {
            return;
        }

        $now = now();

        $existing = DB::table('users')->where('email', $email)->first();

        if ($existing) {
            DB::table('users')
                ->where('id', $existing->id)
                ->update([
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => $existing->email_verified_at ?: $now,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('users')->insert([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => $now,
            'password' => Hash::make($password),
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Intentionally left non-destructive:
        // rolling back should not silently remove an operational login user.
    }
};
