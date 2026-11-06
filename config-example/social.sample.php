<?php
/**
 * PLANTILLA. Copia este archivo a:  domains/realityapp.org/config/social.php
 * (fuera de public_html) y rellena tus datos reales. NO lo subas al repo.
 */
return [
    // Base de datos PROPIA de Reality Social
    'db' => [
        'host' => 'localhost',
        'name' => 'u712716917_social',
        'user' => 'u712716917_socialapp',
        'pass' => 'TU_CONTRASEÑA_DE_social',
    ],
    // Base COMPARTIDA del login (la misma de la tienda). Solo se leen usuarios.
    'auth' => [
        'host' => 'localhost',
        'name' => 'u712716917_market',
        'user' => 'u712716917_marketapp',
        'pass' => 'TU_CONTRASEÑA_DE_market',
    ],
    'app_url' => 'https://realityapp.org',
    'base'    => '/social',
];
