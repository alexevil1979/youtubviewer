<?php
session_start();
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $urls_text = $_POST['urls'] ?? '';
        $target_views = intval($_POST['target_views'] ?? 100);
        $priority = intval($_POST['priority'] ?? 5);
        
        $urls = explode("\n", $urls_text);
        $added = 0;
        foreach ($urls as $url) {
            $url = trim($url);
            if ($url && (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false)) {
                $video_id = extractVideoId($url);
                $db->execute(
                    "INSERT INTO videos (shorts_url, video_id, target_views, priority) VALUES (?, ?, ?, ?)",
                    [$url, $video_id, $target_views, $priority]
                );
                $added++;
            }
        }
        $message = "Добавлено видео: $added";
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $db->execute("DELETE FROM videos WHERE id = ?", [$id]);
        $message = 'Видео удалено';
    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 'pending';
        $db->execute("UPDATE videos SET status = ? WHERE id = ?", [$status, $id]);
        $message = 'Статус обновлен';
    }
}

$videos = $db->fetchAll(
    "SELECT * FROM videos ORDER BY priority DESC, id DESC"
);

$pageTitle = 'Видео';
include 'includes/header.php';
?>

<h1 class="mb-4">Управление видео</h1>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5>Добавить видео</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row mb-3">
                <div class="col-md-8">
                    <textarea name="urls" class="form-control" rows="3" 
                              placeholder="https://youtube.com/shorts/... (по одному на строку)" required></textarea>
                </div>
                <div class="col-md-2">
                    <input type="number" name="target_views" class="form-control" 
                           placeholder="Целевые просмотры" value="100" min="1">
                </div>
                <div class="col-md-2">
                    <input type="number" name="priority" class="form-control" 
                           placeholder="Приоритет (1-10)" value="5" min="1" max="10">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Список видео (<?= count($videos) ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>URL</th>
                        <th>Просмотры</th>
                        <th>Цель</th>
                        <th>Прогресс</th>
                        <th>Приоритет</th>
                        <th>Статус</th>
                        <th>Добавлено</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videos as $video): ?>
                    <tr>
                        <td><?= $video['id'] ?></td>
                        <td>
                            <a href="<?= escape($video['shorts_url']) ?>" target="_blank" class="text-decoration-none">
                                <?= escape(substr($video['shorts_url'], 0, 60)) ?>...
                            </a>
                        </td>
                        <td><?= formatNumber($video['current_views']) ?></td>
                        <td><?= formatNumber($video['target_views']) ?></td>
                        <td>
                            <?php 
                            $progress = $video['target_views'] > 0 
                                ? ($video['current_views'] / $video['target_views']) * 100 
                                : 0;
                            ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= min($progress, 100) ?>%">
                                    <?= number_format($progress, 1) ?>%
                                </div>
                            </div>
                        </td>
                        <td><?= $video['priority'] ?></td>
                        <td><?= getStatusBadge($video['status']) ?></td>
                        <td><?= formatDate($video['added_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $video['id'] ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="pending" <?= $video['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                    <option value="active" <?= $video['status'] == 'active' ? 'selected' : '' ?>>Активно</option>
                                    <option value="paused" <?= $video['status'] == 'paused' ? 'selected' : '' ?>>Приостановлено</option>
                                    <option value="completed" <?= $video['status'] == 'completed' ? 'selected' : '' ?>>Завершено</option>
                                </select>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Удалить видео?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $video['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
