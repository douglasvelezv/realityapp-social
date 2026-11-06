<?php
require __DIR__ . '/../includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
$type = (string)($_GET['type'] ?? '');
$offset = max(0, (int)($_GET['offset'] ?? 0));
$p = [];
switch ($type) {
    case 'home':
        if (!$meId) json_out(['html' => '', 'next' => null]);
        $st = db()->prepare('SELECT followee_id FROM follows WHERE follower_id = ?'); $st->execute([$meId]);
        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)); $ids[] = $meId; $p['ids'] = $ids;
        break;
    case 'user': case 'user_replies': case 'user_likes': $p['uid'] = (int)($_GET['uid'] ?? 0); break;
    case 'hashtag': $p['tag'] = (string)($_GET['tag'] ?? ''); break;
    case 'replies': $p['post'] = (int)($_GET['post'] ?? 0); break;
    case 'explore': break;
    default: json_out(['html' => '', 'next' => null]);
}
[$html, $next] = render_feed($type, $p, $meId, $offset, 20);
json_out(['html' => $html, 'next' => $next !== null ? base_url('/api/stream.php?' . http_build_query(array_filter(['type'=>$type,'offset'=>$next,'uid'=>$p['uid']??null,'tag'=>$p['tag']??null,'post'=>$p['post']??null], fn($v)=>$v!==null&&$v!==''))) : null]);
