<?php

// Copy to paypal.local.php. Client Secret is server-only; never commit this file with real credentials.
return [
    'enabled' => false,
    'mode' => 'sandbox', // Set live only after Sandbox verification.
    'client_id' => '',
    'client_secret' => '',
    'merchant_id' => '', // PayPal Merchant ID of the receiving business.
    'app_url' => 'https://bpstore.gt.tc',
    'vnd_per_usd' => 0, // 0 = daily ExchangeRate-API rate; a positive value is an explicit manual rate.
    'webhook_id' => '', // Reserved for a future webhook receiver; none is installed on InfinityFree.
];
