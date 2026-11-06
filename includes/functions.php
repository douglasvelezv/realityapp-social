<?php
// Reality Social · utilidades comunes
declare(strict_types=1);
require_once __DIR__ . '/session.php';

/* ---------- Básicos ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function want_json(): bool {
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['social_csrf'])) $_SESSION['social_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['social_csrf'];
}
function csrf_ok(?string $t): bool {
    start_session();
    return is_string($t) && !empty($_SESSION['social_csrf']) && hash_equals($_SESSION['social_csrf'], $t);
}
// Verdadero si la petición viene del mismo dominio (quitando puerto y www).
function same_origin(): bool {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') return true; // navegaciones normales no mandan Origin
    $strip = fn($h) => preg_replace(['/:\d+$/', '/^www\./'], '', strtolower((string)$h));
    return $strip(parse_url($origin, PHP_URL_HOST)) === $strip($_SERVER['HTTP_HOST'] ?? '');
}
// Para endpoints que escriben: exige sesión, método POST, CSRF y mismo origen.
function require_write(): array {
    $u = current_user();
    if (!$u) json_out(['error' => 'Inicia sesión para continuar.'], 401);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_out(['error' => 'Método no permitido.'], 405);
    if (!same_origin()) json_out(['error' => 'Origen no permitido.'], 403);
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = $_POST;
    if (!csrf_ok($body['csrf'] ?? ($_POST['csrf'] ?? null))) json_out(['error' => 'Sesión caducada, recarga la página.'], 419);
    return [$u, $body];
}

/* ---------- Tiempo ---------- */
function time_ago(string $dt): string {
    $t = strtotime($dt . ' UTC');
    if (!$t) return '';
    $s = time() - $t;
    if ($s < 60)    return 'ahora';
    if ($s < 3600)  return floor($s / 60) . 'min';
    if ($s < 86400) return floor($s / 3600) . 'h';
    if ($s < 604800) return floor($s / 86400) . 'd';
    return date($s < 31536000 ? 'j M' : 'j M Y', $t);
}
function date_long(string $dt): string {
    $t = strtotime($dt . ' UTC');
    $mes = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return $t ? ('Se unió en ' . $mes[(int)date('n', $t)] . ' de ' . date('Y', $t)) : '';
}

/* ---------- Identidad (base compartida) + perfil social ---------- */
// Devuelve un mapa id => datos de usuario listos para mostrar.
function users_by_ids(array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $out = [];
    // Identidad desde la base compartida
    $st = authdb()->prepare("SELECT id, display_name, app_handle, avatar AS shop_avatar, google_uid, x_uid, x_handle
                               FROM users WHERE id IN ($in)");
    $st->execute($ids);
    foreach ($st as $r) {
        $id = (int)$r['id'];
        $out[$id] = [
            'id'      => $id,
            'name'    => $r['display_name'] ?: ('Usuario ' . $id),
            'handle'  => $r['app_handle'] ?: ('user' . $id),
            'medium'  => $r['google_uid'] ? 'google' : ($r['x_uid'] ? 'x' : 'correo'),
            'avatar'  => null, 'bio' => '', 'location' => '', 'link' => '', 'banner' => null,
        ];
    }
    // Datos propios de social (bio, avatar, banner…)
    $st = db()->prepare("SELECT user_id, bio, location, link, avatar, banner FROM profiles WHERE user_id IN ($in)");
    $st->execute($ids);
    foreach ($st as $r) {
        $id = (int)$r['user_id'];
        if (!isset($out[$id])) continue;
        $out[$id]['bio']      = $r['bio'] ?? '';
        $out[$id]['location'] = $r['location'] ?? '';
        $out[$id]['link']     = $r['link'] ?? '';
        $out[$id]['avatar']   = $r['avatar'] ?: null;
        $out[$id]['banner']   = $r['banner'] ?: null;
    }
    return $out;
}
function user_by_id(int $id): ?array { return users_by_ids([$id])[$id] ?? null; }
function user_by_handle(string $handle): ?array {
    $st = authdb()->prepare('SELECT id FROM users WHERE app_handle = ? AND status = "active"');
    $st->execute([ltrim($handle, '@')]);
    $id = $st->fetchColumn();
    return $id ? user_by_id((int)$id) : null;
}
function ids_by_handles(array $handles): array {
    $h = array_values(array_unique(array_filter(array_map(fn($x) => ltrim(strtolower($x), '@'), $handles))));
    if (!$h) return [];
    $in = implode(',', array_fill(0, count($h), '?'));
    $st = authdb()->prepare("SELECT id FROM users WHERE LOWER(app_handle) IN ($in) AND status = 'active'");
    $st->execute($h);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

/* ---------- Avatares e imágenes ---------- */
function upload_url(string $kind, ?string $file): ?string {
    return $file ? base_url('/uploads/' . $kind . '/' . rawurlencode($file)) : null;
}
function avatar_html(array $u, int $size = 48): string {
    $url = upload_url('avatars', $u['avatar'] ?? null);
    $st  = "width:{$size}px;height:{$size}px";
    if ($url) return '<img class="av" style="' . $st . '" src="' . e($url) . '" alt="" loading="lazy">';
    // Inicial con color estable por id
    $hues = [203, 330, 265, 20, 150, 45];
    $h = $hues[($u['id'] ?? 0) % count($hues)];
    $ini = mb_strtoupper(mb_substr($u['name'] ?: $u['handle'], 0, 1));
    return '<span class="av av-ini" style="' . $st . ';background:hsl(' . $h . ',60%,45%);font-size:' . round($size * .42) . 'px">' . e($ini) . '</span>';
}

/* ---------- Texto: menciones, hashtags, enlaces ---------- */
function linkify(string $text): string {
    $s = e($text);
    // @menciones
    $s = preg_replace_callback('/(^|[^\w@])@(\w{1,30})/u', function ($m) {
        return $m[1] . '<a class="ment" href="' . base_url('/profile.php?u=' . rawurlencode($m[2])) . '">@' . $m[2] . '</a>';
    }, $s);
    // #hashtags
    $s = preg_replace_callback('/(^|[^\w#&])#(\w{1,60})/u', function ($m) {
        return $m[1] . '<a class="tag" href="' . base_url('/hashtag.php?tag=' . rawurlencode($m[2])) . '">#' . $m[2] . '</a>';
    }, $s);
    // enlaces http(s)
    $s = preg_replace_callback('#(^|[\s])(https?://[^\s<]+)#', function ($m) {
        $u = rtrim($m[2], '.,);!?');
        return $m[1] . '<a class="ext" href="' . $u . '" target="_blank" rel="noopener nofollow">' . $u . '</a>';
    }, $s);
    return nl2br($s);
}
function extract_tags(string $text): array {
    preg_match_all('/(?:^|[^\w#&])#(\w{1,60})/u', $text, $m);
    return array_values(array_unique(array_map('mb_strtolower', $m[1] ?? [])));
}
function extract_handles(string $text): array {
    preg_match_all('/(?:^|[^\w@])@(\w{1,30})/u', $text, $m);
    return array_values(array_unique($m[1] ?? []));
}

/* ---------- Estadísticas de posts (likes, reposts, respuestas, mío) ---------- */
function post_stats(array $postIds, int $me): array {
    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));
    if (!$postIds) return [];
    $in = implode(',', array_fill(0, count($postIds), '?'));
    $out = [];
    foreach ($postIds as $id) $out[$id] = ['likes' => 0, 'reposts' => 0, 'replies' => 0, 'liked' => false, 'reposted' => false];
    $q = db()->prepare("SELECT post_id, COUNT(*) n FROM likes WHERE post_id IN ($in) GROUP BY post_id");  $q->execute($postIds);
    foreach ($q as $r) $out[(int)$r['post_id']]['likes'] = (int)$r['n'];
    $q = db()->prepare("SELECT post_id, COUNT(*) n FROM reposts WHERE post_id IN ($in) GROUP BY post_id"); $q->execute($postIds);
    foreach ($q as $r) $out[(int)$r['post_id']]['reposts'] = (int)$r['n'];
    $q = db()->prepare("SELECT reply_to post_id, COUNT(*) n FROM posts WHERE reply_to IN ($in) AND deleted_at IS NULL GROUP BY reply_to"); $q->execute($postIds);
    foreach ($q as $r) $out[(int)$r['post_id']]['replies'] = (int)$r['n'];
    if ($me) {
        $q = db()->prepare("SELECT post_id FROM likes WHERE user_id = ? AND post_id IN ($in)"); $q->execute(array_merge([$me], $postIds));
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $pid) $out[(int)$pid]['liked'] = true;
        $q = db()->prepare("SELECT post_id FROM reposts WHERE user_id = ? AND post_id IN ($in)"); $q->execute(array_merge([$me], $postIds));
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $pid) $out[(int)$pid]['reposted'] = true;
    }
    return $out;
}

/* ---------- Render de un post ---------- */
function icon(string $n): string {
    $p = [
        'reply'  => '<path d="M1.75 12a9.25 9.25 0 1 1 4.5 7.93V22l-3.1-1.6A9.2 9.2 0 0 1 1.75 12Z"/>',
        'repost' => '<path d="M17 2.5 20.5 6 17 9.5"/><path d="M3.5 11V9a3 3 0 0 1 3-3h14"/><path d="M7 21.5 3.5 18 7 14.5"/><path d="M20.5 13v2a3 3 0 0 1-3 3h-14"/>',
        'like'   => '<path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 1.9 4.5 5.5 4.5c2 0 3.3 1.1 4.1 2.3L12 9l2.4-2.2c.8-1.2 2.1-2.3 4.1-2.3 3.6 0 5.2 3.8 3.5 7.2C19.5 16.4 12 21 12 21Z"/>',
        'liked'  => '<path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 1.9 4.5 5.5 4.5c2 0 3.3 1.1 4.1 2.3L12 9l2.4-2.2c.8-1.2 2.1-2.3 4.1-2.3 3.6 0 5.2 3.8 3.5 7.2C19.5 16.4 12 21 12 21Z" fill="currentColor"/>',
        'trash'  => '<path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-9 0 1 13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1l1-13"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}
function num(int $n): string {
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    if ($n >= 1000)    return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return $n ? (string)$n : '';
}
// $p: fila de posts; $users: mapa; $stats: mapa; $me: id actual.
function render_post(array $p, array $users, array $stats, int $me, bool $link = true): string {
    $au = $users[(int)$p['user_id']] ?? ['id' => (int)$p['user_id'], 'name' => 'Usuario', 'handle' => 'user', 'avatar' => null];
    $st = $stats[(int)$p['id']] ?? ['likes' => 0, 'reposts' => 0, 'replies' => 0, 'liked' => false, 'reposted' => false];
    $purl = base_url('/post.php?id=' . (int)$p['id']);
    $prof = base_url('/profile.php?u=' . rawurlencode($au['handle']));
    $img = upload_url('posts', $p['image'] ?? null);
    ob_start(); ?>
    <article class="post" data-id="<?= (int)$p['id'] ?>"<?= $link ? ' data-href="' . e($purl) . '"' : '' ?>>
      <a class="post-av" href="<?= e($prof) ?>"><?= avatar_html($au, 44) ?></a>
      <div class="post-main">
        <div class="post-head">
          <a class="post-name" href="<?= e($prof) ?>"><?= e($au['name']) ?></a>
          <span class="post-handle">@<?= e($au['handle']) ?></span>
          <span class="dot">·</span>
          <a class="post-time" href="<?= e($purl) ?>"><?= e(time_ago($p['created_at'])) ?></a>
          <?php if ($me === (int)$p['user_id']): ?>
            <button class="post-del" data-del="<?= (int)$p['id'] ?>" title="Eliminar" aria-label="Eliminar"><?= icon('trash') ?></button>
          <?php endif; ?>
        </div>
        <?php if (trim((string)$p['body']) !== ''): ?><div class="post-body"><?= linkify($p['body']) ?></div><?php endif; ?>
        <?php if ($img): ?><a class="post-img" href="<?= e($img) ?>" target="_blank" rel="noopener"><img src="<?= e($img) ?>" alt="" loading="lazy"></a><?php endif; ?>
        <div class="post-acts">
          <a class="act act-reply" href="<?= e($purl) ?>"><?= icon('reply') ?><span><?= num($st['replies']) ?></span></a>
          <button class="act act-repost <?= $st['reposted'] ? 'on' : '' ?>" data-repost="<?= (int)$p['id'] ?>"><?= icon('repost') ?><span><?= num($st['reposts']) ?></span></button>
          <button class="act act-like <?= $st['liked'] ? 'on' : '' ?>" data-like="<?= (int)$p['id'] ?>"><?= $st['liked'] ? icon('liked') : icon('like') ?><span><?= num($st['likes']) ?></span></button>
        </div>
      </div>
    </article>
    <?php return ob_get_clean();
}

/* ---------- Notificaciones ---------- */
function notify(int $to, int $actor, string $type, ?int $postId = null): void {
    if ($to === $actor) return;
    db()->prepare('INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?,?,?,?)')
        ->execute([$to, $actor, $type, $postId]);
}
function unread_count(int $me): int {
    if (!$me) return 0;
    $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $st->execute([$me]);
    return (int)$st->fetchColumn();
}

/* ---------- Procesa el texto de un post: guarda hashtags y menciones + notifica ---------- */
function process_post_text(int $postId, int $author, string $text): void {
    foreach (extract_tags($text) as $tag) {
        db()->prepare('INSERT INTO hashtags (tag) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)')->execute([$tag]);
        $hid = (int)db()->lastInsertId();
        db()->prepare('INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?,?)')->execute([$postId, $hid]);
    }
    foreach (ids_by_handles(extract_handles($text)) as $uid) {
        if ($uid === $author) continue;
        db()->prepare('INSERT IGNORE INTO mentions (post_id, user_id) VALUES (?,?)')->execute([$postId, $uid]);
        notify($uid, $author, 'mention', $postId);
    }
}

/* ---------- Guardado seguro de imágenes ---------- */
function save_upload(string $field, string $kind): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? 4) !== UPLOAD_ERR_OK) return null;
    $f = $_FILES[$field];
    if ($f['size'] > 8 * 1024 * 1024) json_out(['error' => 'La imagen supera los 8 MB.'], 413);
    $info = @getimagesize($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
    if (!$ext) json_out(['error' => 'Formato de imagen no válido.'], 415);
    $dir = __DIR__ . '/../uploads/' . $kind;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) json_out(['error' => 'No se pudo guardar la imagen.'], 500);
    return $name;
}

/* ---------- Feeds ---------- */
// Devuelve referencias [ ['post_id'=>int,'repost_by'=>?int], ... ] según el tipo.
function feed_refs(string $type, array $p, int $limit, int $offset): array {
    $limit = max(1, min($limit, 50)); $offset = max(0, $offset);
    switch ($type) {
        case 'home': {
            $ids = $p['ids'] ?? [];
            if (!$ids) return [];
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT id AS post_id, created_at AS ts, NULL AS rb FROM posts
                      WHERE user_id IN ($in) AND reply_to IS NULL AND repost_of IS NULL AND deleted_at IS NULL
                    UNION ALL
                    SELECT post_id, created_at AS ts, user_id AS rb FROM reposts WHERE user_id IN ($in)
                    ORDER BY ts DESC LIMIT $limit OFFSET $offset";
            $st = db()->prepare($sql); $st->execute(array_merge($ids, $ids));
            break;
        }
        case 'explore':
            $st = db()->prepare("SELECT id AS post_id, NULL AS rb FROM posts
                WHERE reply_to IS NULL AND repost_of IS NULL AND deleted_at IS NULL
                ORDER BY id DESC LIMIT $limit OFFSET $offset"); $st->execute();
            break;
        case 'user':
            $st = db()->prepare("SELECT post_id, ts, rb FROM (
                    SELECT id AS post_id, created_at AS ts, NULL AS rb FROM posts
                      WHERE user_id = ? AND reply_to IS NULL AND deleted_at IS NULL
                    UNION ALL
                    SELECT post_id, created_at AS ts, user_id AS rb FROM reposts WHERE user_id = ?
                  ) t ORDER BY ts DESC LIMIT $limit OFFSET $offset");
            $st->execute([$p['uid'], $p['uid']]);
            break;
        case 'user_replies':
            $st = db()->prepare("SELECT id AS post_id, NULL AS rb FROM posts
                WHERE user_id = ? AND reply_to IS NOT NULL AND deleted_at IS NULL
                ORDER BY id DESC LIMIT $limit OFFSET $offset"); $st->execute([$p['uid']]);
            break;
        case 'user_likes':
            $st = db()->prepare("SELECT l.post_id AS post_id, NULL AS rb FROM likes l
                JOIN posts p ON p.id = l.post_id AND p.deleted_at IS NULL
                WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset"); $st->execute([$p['uid']]);
            break;
        case 'hashtag':
            $st = db()->prepare("SELECT ph.post_id AS post_id, NULL AS rb FROM post_hashtags ph
                JOIN hashtags h ON h.id = ph.hashtag_id
                JOIN posts p ON p.id = ph.post_id AND p.deleted_at IS NULL
                WHERE h.tag = ? ORDER BY ph.post_id DESC LIMIT $limit OFFSET $offset"); $st->execute([mb_strtolower($p['tag'])]);
            break;
        case 'replies':
            $st = db()->prepare("SELECT id AS post_id, NULL AS rb FROM posts
                WHERE reply_to = ? AND deleted_at IS NULL ORDER BY id ASC LIMIT $limit OFFSET $offset"); $st->execute([$p['post']]);
            break;
        default: return [];
    }
    $refs = [];
    foreach ($st as $r) $refs[] = ['post_id' => (int)$r['post_id'], 'repost_by' => isset($r['rb']) ? ($r['rb'] !== null ? (int)$r['rb'] : null) : null];
    return $refs;
}

// Carga los posts de las referencias y los renderiza (con banner de repost si aplica).
function render_refs(array $refs, int $me): string {
    if (!$refs) return '';
    $pids = array_values(array_unique(array_map(fn($r) => $r['post_id'], $refs)));
    $in = implode(',', array_fill(0, count($pids), '?'));
    $st = db()->prepare("SELECT * FROM posts WHERE id IN ($in) AND deleted_at IS NULL");
    $st->execute($pids);
    $posts = [];
    foreach ($st as $r) $posts[(int)$r['id']] = $r;
    $uids = [];
    foreach ($posts as $pp) $uids[] = (int)$pp['user_id'];
    foreach ($refs as $r) if ($r['repost_by']) $uids[] = $r['repost_by'];
    $users = users_by_ids($uids);
    $stats = post_stats(array_keys($posts), $me);
    $html = '';
    foreach ($refs as $r) {
        $p = $posts[$r['post_id']] ?? null; if (!$p) continue;
        if ($r['repost_by']) {
            $ru = $users[$r['repost_by']] ?? null;
            $html .= '<div class="repost-flag">' . icon('repost') . ' <a href="' . e(base_url('/profile.php?u=' . rawurlencode($ru['handle'] ?? ''))) . '">' . e(($ru['name'] ?? 'Alguien')) . ' reposteó</a></div>';
        }
        $html .= render_post($p, $users, $stats, $me);
    }
    return $html;
}

// Render de un feed completo con botón "mostrar más". Devuelve [html, nextOffset|null].
function render_feed(string $type, array $p, int $me, int $offset = 0, int $limit = 20): array {
    $refs = feed_refs($type, $p, $limit, $offset);
    $html = render_refs($refs, $me);
    $next = count($refs) >= $limit ? $offset + $limit : null;
    return [$html, $next];
}
