<?php
return [
    'crypto' => [
        'key' => 'i am key 1',
        'pin' => 'i am pin',
    ],
    'jwt'    => [
        'enabled'           => false,
        'api_key'           => '',
        'key'               => 'i am key', // special key for to encrypt JWT
        'token_length'      => 32,
        'issuer'            => 'MY APPLICATION',
        'issue_time_after'  => 0, // 0 sec.  Means, if issue time = now, if time = 0, then, can access now.  If issue_time_after = 60, means, after 1 minutes, able to access
        'expiry_time_after' => 300, // 60 sec = 1 min
        'algorithm'         => [ // Cryptographic Algorithm
            'HS256' => 'sha256',
        ],
    ],
];
