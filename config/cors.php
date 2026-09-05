<?php

return [
    'paths' => ['api/member-registrations', 'api/member-registrations/bulk', 'api/addresses/*'],
    'allowed_methods' => ['GET', 'POST'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Accept'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
