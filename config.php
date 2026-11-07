<?php
/**
 * Reality Social · carga de configuración (tolerante).
 *
 * Secretos fuera de public_html. Busca, en orden, en estas carpetas:
 *   1) domains/realityapp.org/config   (al lado de public_html — recomendado)
 *   2) <raíz de la cuenta>/config
 *   3) public_html/social/config        (respaldo; .htaccess lo bloquea)
 *
 * - La base de datos PROPIA de social se lee de  config/social.php
 *   Acepta formato plano (host/name/user/pass) o anidado (['db' => [...]]).
 * - Las credenciales del LOGIN compartido se toman de  config/social.php ['auth']
 *   si existe; si no, del propio  config/db.php  de la tienda (misma carpeta),
 *   así no hay que duplicar la contraseña de la base del login.
 */
declare(strict_types=1);

function social_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $web  = __DIR__;                       // public_html/social
    $site = dirname($web, 2);              // domains/realityapp.org
    $dirs = [];
    $dirs[] = $site . '/config';
    if (preg_match('#^(/home/[^/]+)#', $web, $m)) $dirs[] = $m[1] . '/config';
    $dirs[] = $web . '/config';

    $load = function (string $file) use ($dirs) {
        // Prueba local: SOCIAL_CONFIG apunta directo al social.php
        if ($file === 'social.php' && getenv('SOCIAL_CONFIG') && is_file(getenv('SOCIAL_CONFIG'))) {
            $a = require getenv('SOCIAL_CONFIG');
            return is_array($a) ? $a : null;
        }
        foreach ($dirs as $d) {
            $f = $d . '/' . $file;
            if (is_file($f)) { $a = require $f; if (is_array($a)) return $a; }
        }
        return null;
    };

    $s = $load('social.php');
    if (!$s) { http_response_code(500); exit('Falta config/social.php. Copia config-example/social.sample.php y rellena tus datos.'); }

    // Base propia de social (acepta plano o anidado)
    $db = (isset($s['db']) && is_array($s['db'])) ? $s['db'] : $s;
    // Base del login: social.php ['auth'] si existe; si no, el config/db.php de la tienda
    $auth = (isset($s['auth']) && is_array($s['auth'])) ? $s['auth'] : $load('db.php');
    if (!$auth) { http_response_code(500); exit('No encuentro las credenciales del login (config/db.php de la tienda).'); }

    $norm = fn(array $x) => [
        'host' => $x['host'] ?? 'localhost',
        'name' => $x['name'] ?? '',
        'user' => $x['user'] ?? '',
        'pass' => $x['pass'] ?? '',
    ];
    return $cfg = [
        'db'      => $norm($db),
        'auth'    => $norm($auth),
        'base'    => $s['base'] ?? '/social',
        'app_url' => $s['app_url'] ?? 'https://realityapp.org',
    ];
}
