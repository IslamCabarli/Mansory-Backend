<?php

// Parse CLOUDINARY_URL if individual env vars aren't set
$cloudinaryUrl = env('CLOUDINARY_URL');
$cloudName = env('CLOUDINARY_CLOUD_NAME');
$apiKey = env('CLOUDINARY_API_KEY');
$apiSecret = env('CLOUDINARY_API_SECRET');

// If individual vars aren't set, parse from URL
if (!$cloudName && $cloudinaryUrl) {
    // Format: cloudinary://api_key:api_secret@cloud_name
    if (preg_match('/cloudinary:\/\/(.+?):(.+?)@(.+)/', $cloudinaryUrl, $matches)) {
        $apiKey = $matches[1];
        $apiSecret = $matches[2];
        $cloudName = $matches[3];
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is used for Cloudinary integration in your Laravel app
    |
    */

    'cloud_name' => $cloudName,
    'api_key' => $apiKey,
    'api_secret' => $apiSecret,
    'secure' => true,

];
