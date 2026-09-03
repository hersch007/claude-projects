<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
lw_require_admin();

$pdo = lw_db();
$view_id = isset( $_GET['session'] ) ? (int) $_GET['session'] : 0;

if ( $view_id ) {
    $session_stmt = $pdo->prepare( 'SELECT * FROM sessions WHERE id = ?' );
    $session_stmt->execute( array( $view_id ) );
    $session = $session_stmt->fetch( PDO::FETCH_ASSOC );

    $msgs_stmt = $pdo->prepare( 'SELECT * FROM chat_messages WHERE session_id = ? ORDER BY id ASC' );
    $msgs_stmt->execute( array( $view_id ) );
    $messages = $msgs_stmt->fetchAll( PDO::FETCH_ASSOC );
    ?><!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><title>Lifeward Admin — Transcript</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
    <body>
    <div class="lw-admin-wrap">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="lw-admin-header">
            <h1>Transcript</h1>
            <a href="chats.php" class="lw-btn-primary" style="background:#fff;color:#3c1053;border:1.5px solid #3c1053">&larr; All chats</a>
        </div>
        <?php if ( ! $session ): ?>
            <div class="lw-empty">Session not found.</div>
        <?php else: ?>
            <p class="lw-hint">
                <?php echo htmlspecialchars( $session['name'] ?: 'Anonymous visitor', ENT_QUOTES ); ?>
                <?php if ( $session['email'] ): ?> &middot; <?php echo htmlspecialchars( $session['email'], ENT_QUOTES ); ?><?php endif; ?>
                <?php if ( $session['phone'] ): ?> &middot; <?php echo htmlspecialchars( $session['phone'], ENT_QUOTES ); ?><?php endif; ?>
            </p>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:10px;">
                <?php foreach ( $messages as $m ):
                    $isUser = $m['role'] === 'user';
                ?>
                <div style="max-width:70%;<?php echo $isUser ? 'align-self:flex-end;background:#3c1053;color:#fff;' : 'align-self:flex-start;background:#faf7fb;border:1px solid #e9e0ee;color:#2b2530;'; ?>padding:10px 14px;border-radius:14px;font-size:.9rem;white-space:pre-wrap;">
                    <?php echo htmlspecialchars( $m['body'], ENT_QUOTES ); ?>
                    <div style="font-size:.68rem;opacity:.7;margin-top:4px;"><?php echo htmlspecialchars( $m['created_at'], ENT_QUOTES ); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if ( empty( $messages ) ): ?><div style="color:#6b6470;">No messages.</div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$rows = $pdo->query(
    "SELECT s.id, s.name, s.email, s.phone, s.created_at,
            COUNT(c.id) AS msg_count, MAX(c.created_at) AS last_at
     FROM sessions s
     JOIN chat_messages c ON c.session_id = s.id
     GROUP BY s.id
     ORDER BY last_at DESC
     LIMIT 200"
)->fetchAll( PDO::FETCH_ASSOC );
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Lifeward Admin — AI Chats</title>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
<body>
<div class="lw-admin-wrap">
    <?php include __DIR__ . '/_nav.php'; ?>
    <h1>AI Chats</h1>
    <p class="lw-hint">Every conversation through the "Or ask me anything" widget. A session with both a chat and a captured lead shows contact info here too.</p>

    <?php if ( empty( $rows ) ): ?>
        <div class="lw-empty">No chat conversations yet.</div>
    <?php else: ?>
        <table class="lw-table">
            <thead><tr><th>Last Activity</th><th>Visitor</th><th>Messages</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $rows as $r ): ?>
                <tr>
                    <td><?php echo htmlspecialchars( date( 'M j, g:i A', strtotime( $r['last_at'] ) ), ENT_QUOTES ); ?></td>
                    <td><?php echo htmlspecialchars( $r['name'] ?: 'Anonymous', ENT_QUOTES ); ?><br><span class="lw-muted"><?php echo htmlspecialchars( $r['email'] ?: '', ENT_QUOTES ); ?></span></td>
                    <td><?php echo (int) $r['msg_count']; ?></td>
                    <td><a href="?session=<?php echo (int) $r['id']; ?>" class="lw-btn-primary" style="background:#fff;color:#3c1053;border:1.5px solid #3c1053;padding:6px 14px;font-size:.82rem">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
