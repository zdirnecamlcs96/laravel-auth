<?php

return [

    /**
     * ======================================
     * Available: passport, sanctum
     * ======================================
     */
    "mode" => env('AUTH_MODE', 'passport'),

    /**
     * ======================================
     * API endpoint
     * ======================================
     */
    "endpoint" => env('API_URL', 'api.example.test'),

    /**
     * ======================================
     * Model Classes
     * ======================================
     */
    "models" => [
        // User Class
        "user" => "App\\Models\\User",

        // Contact Class
        "contact" => "Zdirnecamlcs96\\Auth\\Models\\Contact"
    ],

    /**
     * ======================================
     * Validation Rules
     * ======================================
     */
    "rules" => [
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
        ],
        "account" => [
            "update" => [
                'name' => ['required', 'string', 'max:255'],
            ]
        ],
        "password" => [
            "reset" => [
                'email' => ['required', 'string', 'email'],
            ],
            "change" => [
                'old_password' => ['required', 'string'],
                'new_password' => ['required', 'min:8', 'string', 'confirmed'],
            ]
        ]
    ],

    /**
     * ======================================
     * Validation Custom Message
     * ======================================
     */
    "messages" => [
        "login" => [
            // ...
        ],
        "register" => [
            // ...
        ]
    ],

    /**
     * ======================================
     * Third Party Configuration
     * ======================================
     *
     * Before you start, please make sure to setup the client key and secret key for each of defined provider.
     * Current supported provider: Google, Facebook, Apple
     *
     * `app_login_url` - Redirect back to application's login page once third party callback verified completed
     * `username` - User account name column
     *
     */

    "third_party" => [
        "app_login_url" => env("THIRD_PARTY_APP_LOGIN_URL", "https://localhost:8000/login"),
        "username" => "name"
    ],

    /**
     * ======================================
     * Reset Password Configuration
     * ======================================
     *
     * Custom reset password configuration used for different link
     *
     * `password_reset_route` - Email redirect route (Default: link to api password)
     * `password_reset_redirect` - Frontend view link (Web Application)
     * `password_update_route` - Password reset route
     *
     *
     */
    "reset_password" => [
        // GET
        "password_reset_route" => "authentication.api.password.reset",

        // View
        "password_reset_redirect" => env("RESET_PASSWORD_VIEW_REDIRECT", NULL),

        // POST
        "password_update_route" => "authentication.api.password.update",

    ]
];