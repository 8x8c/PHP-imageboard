<?php
declare(strict_types=1);

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error.txt');

$db = new PDO('sqlite:db.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    file TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$threadsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $threadsPerPage;

// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['message'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $file = null;

    if (!empty($_FILES['file']['name'])) {
        $allowed = ['jpg', 'png', 'gif', 'mp4'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], "uploads/$filename");
            $file = $filename;
        }
    }

    $stmt = $db->prepare("INSERT INTO threads (title, message, file) VALUES (?, ?, ?)");
    $stmt->execute([$title, $message, $file]);

    header("Location: index.php");
    exit;
}

// Get threads
$stmt = $db->prepare("SELECT * FROM threads ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $threadsPerPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>ChessBoard Lite</title>
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

    <h1 style="text-align: center;">ChessBoard Lite</h1>

    <form method="POST" enctype="multipart/form-data" class="post-form">
        <input type="text" name="title" placeholder="Thread Title" required><br>
        <textarea name="message" placeholder="Message..." required></textarea><br>
        <input type="file" name="file" accept=".jpg,.png,.gif,.mp4"><br>
        <button type="submit">Post Thread</button>
    </form>

    <hr>
    <?php foreach ($threads as $thread): ?>
        <div class="thread">
            <h2><a href="thread.php?id=<?= $thread['id'] ?>"><?= htmlspecialchars($thread['title']) ?></a></h2>
            <?php if ($thread['file']): ?>
                <div class="media">
                    <?php
                        $ext = pathinfo($thread['file'], PATHINFO_EXTENSION);
                        if (in_array($ext, ['jpg','png','gif'])) {
                            echo "<img src='uploads/{$thread['file']}' alt='image' data-expanded='false'>";
                        } elseif ($ext === 'mp4') {
                            echo "<video src='uploads/{$thread['file']}' controls data-expanded='false'></video>";
                        }
                    ?>
                </div>
            <?php endif; ?>
            <p><?= nl2br(htmlspecialchars($thread['message'])) ?></p>
        </div>
    <?php endforeach; ?>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php endif; ?>
        <span> Page <?= $page ?> </span>
        <?php if (count($threads) === $threadsPerPage): ?>
            <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>
