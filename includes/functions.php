<?php
// Utilidades compartidas (se ampliará en pasos posteriores).
declare(strict_types=1);
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['social_csrf'])) $_SESSION['social_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['social_csrf'];
}
function csrf_ok(?string $t): bool { return is_string($t) && hash_equals($_SESSION['social_csrf'] ?? '', $t); }
