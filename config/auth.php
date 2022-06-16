<?php

return [
    "fields" => [
        "device_type" => "nullable|string|max:180|in:android,ios",
        "fcm_token" => "nullable|string",
    ]
];