<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ppf' => [
        'url' => env('PISTE_BASE_URL', 'https://sandbox-api.piste.gouv.fr/cpro/factures/v1'),
        'auth_url' => env('PISTE_AUTH_URL', 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token'),
        'client_id' => env('PISTE_CLIENT_ID'),
        'client_secret' => env('PISTE_CLIENT_SECRET'),
        'api_key' => env('PISTE_API_KEY'),
        'cpro_account_login' => env('CHORUS_TECH_LOGIN'),
        'cpro_account_password' => env('CHORUS_TECH_PASSWORD'),
        'syntaxe_flux' => env('CHORUS_SYNTAXE_FLUX', 'IN_DP_E1_UBL_INVOICE'),
        'id_fournisseur' => env('CHORUS_ID_FOURNISSEUR'),
        'id_service_fournisseur' => env('CHORUS_ID_SERVICE_FOURNISSEUR'),
    ],

    'urssaf' => [
        'url' => env('URSSAF_BASE_URL', 'https://api.urssaf.fr'),
        'client_id' => env('URSSAF_CLIENT_ID'),
        'client_secret' => env('URSSAF_CLIENT_SECRET'),
    ],

];
