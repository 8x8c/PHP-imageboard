<?php
declare(strict_types=1);

// index.php and post.php combined with pagination
$db = new PDO('sqlite:db.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    file TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER,
    parent_id INTEGER DEFAULT NULL,
    message TEXT NOT NULL,
    file TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// Handle new thread post
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
    header('Location: index.php');
    exit;
}

// Pagination setup
$threadsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $threadsPerPage;

$totalThreads = (int)$db->query("SELECT COUNT(*) FROM threads")->fetchColumn();
$totalPages = (int)ceil($totalThreads / $threadsPerPage);

$stmt = $db->prepare("SELECT * FROM threads ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $threadsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>ChessBoard Lite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>ChessBoard Lite</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Thread title" required><br>
        <textarea name="message" placeholder="Your post" required></textarea><br>
        <input type="file" name="file"><br>
        <button type="submit">Post</button>
    </form>
    <hr>
    <?php foreach ($threads as $thread): ?>
        <div class="thread">
            <h2><a href="thread.php?id=<?= $thread['id'] ?>"><?= htmlspecialchars($thread['title']) ?></a></h2>
            <p><?= nl2br(htmlspecialchars($thread['message'])) ?></p>
            <?php if ($thread['file']): ?>
                <div class="media">
                    <?php
                        $ext = pathinfo($thread['file'], PATHINFO_EXTENSION);
                        if (in_array($ext, ['jpg','png','gif'])) {
                            echo "<img src='uploads/{$thread['file']}' alt='attached image'>";
                        } elseif ($ext === 'mp4') {
                            echo "<video controls src='uploads/{$thread['file']}'></video>";
                        }
                    ?>
                </div>
            <?php endif; ?>
            <small>Posted at <?= $thread['created_at'] ?></small>
        </div>
        <hr>
    <?php endforeach; ?>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>
