<?php

return [
    'default_ip' => env('DNS_DEFAULT_IP', '127.0.0.1'),
    'nameservers' => [
        env('DNS_NS1', 'ns1.supercp.com.'),
        env('DNS_NS2', 'ns2.supercp.com.'),
    ],
];
