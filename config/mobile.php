<?php

return [
    'ceo' => [
        'allow_all_users' => (bool) env('MOBILE_CEO_ALLOW_ALL_USERS', false),
        'allowed_emails' => array_values(array_filter(array_map(
            static fn (string $email): string => strtolower(trim($email)),
            explode(',', (string) env('MOBILE_CEO_ALLOWED_EMAILS', ''))
        ))),
    ],
];
