<?php
/**
 * Reality Social · carga de configuración.
 * Los secretos NO están en el repositorio: se leen de un archivo fuera de
 * public_html. Candidatos, en orden (el primero que exista, gana):
 *   1) domains/realityapp.org/config/social.php   (recomendado, fuera de la web)
 *   2) <raíz de la cuenta>/config/social.php
 *   3) public_html/social/config/social.php        (respaldo; .htaccess lo bloquea)
 * Copia config-example/social.sample.php a una de esas rutas y rellénalo.
 */
declare(strict_types=1);

function social_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $web  = __DIR__;                       // public_html/social
    $site = dirname($web, 2);              // domains/realityapp.org
    $cands = [];
    if (getenv('SOCIAL_CONFIG')) $cands[] = getenv('SOCIAL_CONFIG');   // pruebas locales
    $cands[] = $site . '/config/social.php';
    if (preg_match('#^(/home/[^/]+)#', $web, $m)) $cands[] = $m[1] . '/config/social.php';
    $cands[] = $web . '/config/social.php';
    foreach ($cands as $f) {
        if (is_file($f)) { $c = require $f; if (is_array($c)) return $cfg = $c; }
    }
    http_response_code(500);
    exit('Falta config/social.php. Copia config-example/social.sample.php y rellena tus datos.');
}
