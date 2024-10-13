<?php

return [


    'host'         => env('LIVEKIT_HOST', 'https://live-kit-server.cotopia.social'),
    'apiKey'       => env('LIVEKIT_API_KEY', 'devkey'),
    'apiSecret'    => env('LIVEKIT_API_SECRET', 'secret'),
    'egressLayout' => env('LIVEKIT_EGRESS_LAYOUT', 'speaker'),
];
