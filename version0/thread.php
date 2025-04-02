<?php
declare(strict_types=1);

$db = new PDO('sqlite:db.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid thread ID.');
}
$threadId = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
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

    $stmt = $db->prepare("INSERT INTO replies (thread_id, message, file) VALUES (?, ?, ?)");
    $stmt->execute([$threadId, $message, $file]);
    header("Location: thread.php?id=$threadId");
    exit;
}

$stmt = $db->prepare("SELECT * FROM threads WHERE id = ?");
$stmt->execute([$threadId]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thread) {
    die('Thread not found.');
}

$replies = $db->prepare("SELECT * FROM replies WHERE thread_id = ? ORDER BY created_at ASC");
$replies->execute([$threadId]);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($thread['title']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><?= htmlspecialchars($thread['title']) ?></h1>
    <div class="thread">
        <p><?= nl2br(htmlspecialchars($thread['message'])) ?></p>
        <?php if ($thread['file']): ?>
            <div class="media">
                <?php
                    $ext = pathinfo($thread['file'], PATHINFO_EXTENSION);
                    if (in_array($ext, ['jpg','png','gif'])) {
                        echo "<img src='uploads/{$thread['file']}' alt='image'>";
                    } elseif ($ext === 'mp4') {
                        echo "<video controls src='uploads/{$thread['file']}'></video>";
                    }
                ?>
            </div>
        <?php endif; ?>
        <small>Posted at <?= $thread['created_at'] ?></small>
    </div>

    <hr>
    <h3>Reply to Topic</h3>
    <form action="" method="POST" enctype="multipart/form-data">
        <textarea name="message" required placeholder="Reply to OP..."></textarea><br>
        <input type="file" name="file"><br>
        <button type="submit">Post Reply</button>
    </form>

    <hr>
    <h3>Replies</h3>
    <?php foreach ($replies as $reply): ?>
        <div class="reply">
            <p><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
            <?php if ($reply['file']): ?>
                <div class="media">
                    <?php
                        $ext = pathinfo($reply['file'], PATHINFO_EXTENSION);
                        if (in_array($ext, ['jpg','png','gif'])) {
                            echo "<img src='uploads/{$reply['file']}' alt='reply media'>";
                        } elseif ($ext === 'mp4') {
                            echo "<video controls src='uploads/{$reply['file']}'></video>";
                        }
                    ?>
                </div>
            <?php endif; ?>
            <small>Posted at <?= $reply['created_at'] ?></small>
        </div>
        <hr>
    <?php endforeach; ?>

    <p><a href="index.php">&larr; Back to threads</a></p>
</body>
</html>
