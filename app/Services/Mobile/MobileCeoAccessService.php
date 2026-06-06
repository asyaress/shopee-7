<?php

namespace App\Services\Mobile;

use App\Models\User;

class MobileCeoAccessService
{
    public function allowed(User $user): bool
    {
        if ((bool) config('mobile.ceo.allow_all_users', false)) {
            return true;
        }

        $email = strtolower((string) $user->email);
        $allowed = (array) config('mobile.ceo.allowed_emails', []);

        return in_array($email, $allowed, true);
    }
}
