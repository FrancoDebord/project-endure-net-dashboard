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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'microsoft' => [
        'client_id'     => env('MICROSOFT_CLIENT_ID', ''),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
        'redirect'      => env('MICROSOFT_REDIRECT_URI', '/auth/microsoft/callback'),
        'tenant'        => env('MICROSOFT_TENANT_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | KoboToolbox
    |--------------------------------------------------------------------------
    | API REST KoboToolbox pour la collecte des inspections physiques.
    | KOBO_API_URL   : URL de base de l'API (sans slash final)
    | KOBO_API_TOKEN : Token d'authentification Bearer
    | KOBO_FORM_UID  : UID du formulaire d'inspection dans KoboToolbox
    | KOBO_ACCUM_FORM_UID : UID du formulaire « Accumulation, stockage et utilisation des MII » (suivi 6 mois)
    */
    'kobotoolbox' => [
        'api_url'  => env('KOBO_API_URL',  'https://kf.kobotoolbox.org/api/v2'),
        'token'    => env('KOBO_API_TOKEN'),
        'form_uid' => env('KOBO_FORM_UID',  'aqK3SBNgxZcpoqKnMf2AAF'),
        'accum_form_uid' => env('KOBO_ACCUM_FORM_UID', 'ajbs4vtCUdXKfycPTLNgWb'),
    ],

];
