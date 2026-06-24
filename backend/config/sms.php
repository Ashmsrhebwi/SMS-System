<?php

return [
    'rate_limit_delay'  => env('SMS_RATE_LIMIT_DELAY', 1),
    'opt_out_text'      => env('SMS_OPT_OUT_TEXT', 'To stop receiving messages, visit: {opt_out_url}'),
    'whatsapp_url'      => env('SMS_WHATSAPP_URL', 'https://wa.me/'),
    'cost_per_segment'  => env('SMS_COST_PER_SEGMENT', 0.0079),
    'currency'          => env('SMS_CURRENCY', 'USD'),
    'currency_symbol'   => env('SMS_CURRENCY_SYMBOL', '$'),
];
