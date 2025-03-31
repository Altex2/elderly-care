<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | This value is the API key for authenticating with the OpenAI API. You can
    | find this value in your OpenAI dashboard.
    |
    */
    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Organization ID
    |--------------------------------------------------------------------------
    |
    | This value is the Organization ID for authenticating with the OpenAI API.
    | You can find this value in your OpenAI dashboard. This value is optional.
    |
    */
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | This value is the maximum number of seconds the HTTP client will wait for
    | a response before timing out. You can increase this value if you find
    | that requests are timing out for large generations.
    |
    */
    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
];
