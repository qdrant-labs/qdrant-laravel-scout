<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Qdrant Connection
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Qdrant instance that Scout uses. The engine
    | sends requests to the Qdrant REST API. Set an API key when you use
    | Qdrant Cloud or an instance that has API key authentication on.
    |
    */

    'host' => env('QDRANT_HOST', 'http://localhost:6333'),

    'api_key' => env('QDRANT_API_KEY'),

    'timeout' => env('QDRANT_TIMEOUT', 60),

    'connect_timeout' => env('QDRANT_CONNECT_TIMEOUT', 5),

    'retries' => env('QDRANT_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Maximum Results
    |--------------------------------------------------------------------------
    |
    | Full-text, semantic, and hybrid searches return up to this number of
    | results across all pages. Calling "take" on a search overrides the
    | value for that search.
    |
    */

    'max_results' => env('QDRANT_MAX_RESULTS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Model Settings
    |--------------------------------------------------------------------------
    |
    | Here you may configure the searchable attributes, the embedding, and
    | the payload indexes of each of your searchable models. Scout uses
    | these settings to create the collection when it first indexes it.
    |
    */

    'model-settings' => [
        // App\Models\Post::class => [
        //     'searchable-attributes' => ['title', 'body'],
        //     'embedding' => [
        //         'attribute' => 'embedding',
        //         'dimensions' => 1536,
        //     ],
        //     'payload-indexes' => [
        //         'status' => 'keyword',
        //         'published_at' => 'integer',
        //     ],
        // ],
    ],

];
