<?php

return [

    /**
     * Available: passport, sanctum
     */
    "mode" => env('AUTH_MODE', 'passport'),

    /**
     * API endpoint
     */
    "endpoint" => env('API_URL', 'api.example.test'),

    "models" => [
        "api" => "App\\Models\\User"
    ],

    /**
     * Validation Rules
     */
    "validation" => [
        "login" => [
            // Account Information (It's required. Please do not remove)
            "email" => 'required|string',
            'password' => 'required|string',

            // Device Information
            "device_type" => "nullable|string|max:180|in:android,ios",
            "fcm_token" => "nullable|string",

            // Extra Information
        ],
        "register" => [
            // Account Information (It's required. Please do not remove)
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,'',id,deleted_at,NULL"],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:255'],

            // Device Information
            "device_id" => "required_unless:device_type,web|string|max:180|nullable",
            "device_type" => "required|string|max:180|in:android,ios,web",
            "fcm_token" => "required_unless:device_type,web|string|nullable",

            // Extra Information
        ]
    ]
];