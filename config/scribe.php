<?php

use Knuckles\Larupdate\Support\Larupdate;

return [
    'theme' => 'default',

    'title' => 'Synapse Backend API',
    'description' => 'Documentation de l\'API du système de gestion hospitalière Synapse.',
    'base_url' => env('APP_URL', 'http://localhost:8000'),

    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],

    'type' => 'laravel',  // génère dans public/docs

    'static' => [
        'output_path' => 'public/docs',
    ],

    'laravel' => [
        'add_routes' => true,
        'docs_url' => '/api/documentation',
        'middleware' => [],
    ],

    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{VOTRE_TOKEN_SANCTUM}',
        'extra_info' => 'Obtenez votre token via POST /api/v1/auth/login',
    ],

    'intro_text' => <<<INTRO
    # Introduction

    Bienvenue sur la documentation de l'API Synapse.

    Cette API permet de gérer :
    - **Réception** : patients, registres, tarifs
    - **Laboratoire** : demandes d'examens, worklist, résultats
    - **Soins** : consultations, hospitalisations, accouchements, actes opératoires
    - **Imagerie** : demandes d'imagerie, worklist
    - **Finance** : sessions de paiement, paiements, factures
    - **Pharmacie** : stocks, ventes, commandes

    ## Authentification
    Toutes les routes sont protégées par **Laravel Sanctum**. Incluez le token dans le header :
    ```
        Authorization: Bearer {votre_token}
    ```
    INTRO,

    'example_languages' => ['bash', 'javascript', 'php'],

    'postman' => [
        'enabled' => true,
        'overrides' => [
            'info.version' => '1.0.0',
        ],
    ],

    'openapi' => [
        'enabled' => true,
        'overrides' => [
            'info.version' => '1.0.0',
        ],
    ],

    'groups' => [
        'default' => 'Général',
        'order' => [
            'Authentification',
            'Réception',
            'Laboratoire',
            'Soins',
            'Imagerie',
            'Finance',
            'Pharmacie',
        ],
    ],

    'strategies' => [
        'metadata' => [
            \Knuckles\Scribe\Extracting\Strategies\Metadata\GetFromDocBlocks::class,
            \Knuckles\Scribe\Extracting\Strategies\Metadata\GetFromMetadataAttributes::class,
        ],
        'urlParameters' => [
            \Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromLaravelAPI::class,
            \Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromUrlParamAttribute::class,
            \Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromDocBlocks::class,
        ],
        'queryParameters' => [
            \Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromFormRequest::class,
            \Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromQueryParamAttribute::class,
            \Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromDocBlocks::class,
        ],
        'headers' => [
            \Knuckles\Scribe\Extracting\Strategies\Headers\GetFromRouteRules::class,
            \Knuckles\Scribe\Extracting\Strategies\Headers\GetFromHeaderAttribute::class,
        ],
        'bodyParameters' => [
            \Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromFormRequest::class,
            \Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromBodyParamAttribute::class,
            \Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromDocBlocks::class,
        ],
        'responses' => [
            \Knuckles\Scribe\Extracting\Strategies\Responses\UseTransformerTags::class,
            \Knuckles\Scribe\Extracting\Strategies\Responses\UseApiResourceTags::class,
            \Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseTag::class,
            \Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseFileTag::class,
            \Knuckles\Scribe\Extracting\Strategies\Responses\ResponseCalls::class,
        ],
        'responseFields' => [
            \Knuckles\Scribe\Extracting\Strategies\ResponseFields\GetFromResponseFieldAttribute::class,
            \Knuckles\Scribe\Extracting\Strategies\ResponseFields\GetFromDocBlocks::class,
        ],
    ],

    'database_connections_to_transact' => [config('database.default')],

    'fractal' => [
        'serializer' => null,
    ],

    'routeMatcher' => \Knuckles\Scribe\Matching\RouteMatcher::class,
];