<?php
declare(strict_types=1);

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error.txt');

$db = new PDO('sqlite:db.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create replies table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$threadId = $_GET['id'] ?? null;
if (!$threadId || !is_numeric($threadId)) {
    error_log("Invalid thread ID access attempt: " . var_export($threadId, true));
    header('Location: index.php');
    exit;
}
$threadId = (int)$threadId;

// Handle reply post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $stmt = $db->prepare("INSERT INTO replies (thread_id, message) VALUES (?, ?)");
    $stmt->execute([$threadId, $message]);

    header("Location: thread.php?id=$threadId");
    exit;
}

// Fetch thread
$stmt = $db->prepare("SELECT * FROM threads WHERE id = ?");
$stmt->execute([$threadId]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thread) {
    error_log("Thread not found: ID $threadId");
    header('Location: index.php');
    exit;
}

// Fetch replies
$replies = $db->prepare("SELECT * FROM replies WHERE thread_id = ? ORDER BY created_at ASC");
$replies->execute([$threadId]);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($thread['title']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script src="css/theme.js" defer></script>
</head>
<body class="theme-light">
    <div style="position: fixed; top: 10px; left: 10px;">
        <label for="theme-select">Theme:</label>
        <select id="theme-select">
            <option value="theme-light">Light</option>
            <option value="theme-grey">Grey</option>
            <option value="theme-dark">Dark</option>
        </select>
    </div>

    <div style="position: fixed; top: 10px; right: 10px;">
        <a href="./">&laquo; Back</a>
    </div>

    <h1><?= htmlspecialchars($thread['title']) ?></h1>
    <div class="thread">
        <?php if ($thread['file']): ?>
            <div class="media">
                <?php
                    $ext = pathinfo($thread['file'], PATHINFO_EXTENSION);
                    if (in_array($ext, ['jpg','png','gif'])) {
                        echo "<img src='uploads/{$thread['file']}' alt='image' data-expanded='false'>";
                    } elseif ($ext === 'mp4') {
                        echo "<video controls src='uploads/{$thread['file']}' data-expanded='false'></video>";
                    }
                ?>
            </div>
        <?php endif; ?>
        <p><?= nl2br(htmlspecialchars($thread['message'])) ?></p>
    </div>

    <h3 style="text-align: center;">Reply to Topic</h3>
    <form method="POST" class="reply-form">
        <textarea name="message" required placeholder="Reply to OP..."></textarea><br>
        <button type="submit">Post Reply</button>
    </form>

    <hr>
    <h3>Replies</h3>
    <?php foreach ($replies as $reply): ?>
        <div class="reply">
            <p><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
        </div>
        <hr>
    <?php endforeach; ?>

    <p><a href="index.php">&larr; Back to threads</a></p>
</body>
</html>
