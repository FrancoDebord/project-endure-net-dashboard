<?php

/*
 * Conservé pour compatibilité. La configuration principale est dans config/services.php
 * sous la clé 'kobotoolbox'. KoboToolboxService lit désormais config('services.kobotoolbox.*').
 */
return [
    'api_url'  => env('KOBO_API_URL', 'https://kf.kobotoolbox.org/api/v2'),
    'token'    => env('KOBO_API_TOKEN'),
    'form_uid' => env('KOBO_FORM_UID'),
];
