<?php

return [
    'components' => [
        // This app doesn't use <x-heroicon-*> components, so skip icon set
        // discovery on every request to avoid expensive filesystem scans.
        'disabled' => true,
    ],
];
