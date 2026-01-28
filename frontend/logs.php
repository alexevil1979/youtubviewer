<?php
session_start();
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// Фильтры
$account_id = $_GET['account_id'] ?? '';
$video_id = $_GET['video_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$success = $_GET['success'] ?? '';

$where = [];
$params = [];

if ($account_id) {
    $where[] = "vl.account_id = ?";
    $params[] = $account_id;
}
if ($video_id) {
    $where[] = "vl.video_id = ?";
    $params[] = $video_id;
}
if ($date_from) {
    $where[] = "DATE(vl.watched_at) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where[] = "DATE(vl.watched_at) <= ?";
    $params[] = $date_to;
}
if ($success !== '') {
    $where[] = "vl.success = ?";
    $params[] = $success;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$logs = $db->fetchAll(
    "SELECT vl.*, a.email, v.shorts_url 
     FROM view_logs vl
     LEFT JOIN accounts a ON vl.account_id = a.id
     LEFT JOIN videos v ON vl.video_id = v.id
     $where_sql
     ORDER BY vl.watched_at DESC
     LIMIT 500",
    $params
);

$accounts = $db->fetchAll("SELECT id, email FROM accounts ORDER BY email");
$videos = $db->fetchAll("SELECT id, shorts_url FROM videos ORDER BY id DESC LIMIT 100");

$pageTitle = 'Логи просмотров';
include 'includes/header.php';
?>

<h1 class="mb-4">Логи просмотров</h1>

<div class="card mb-4">
    <div class="card-header">
        <h5>Фильтры</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="account_id" class="form-select">
                    <option value="">Все аккаунты</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= $account_id == $acc['id'] ? 'selected' : '' ?>>
                            <?= escape($acc['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="video_id" class="form-select">
                    <option value="">Все видео</option>
                    <?php foreach ($videos as $vid): ?>
                        <option value="<?= $vid['id'] ?>" <?= $video_id == $vid['id'] ? 'selected' : '' ?>>
                            <?= escape(substr($vid['shorts_url'], 0, 50)) ?>...
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?= escape($date_from) ?>" placeholder="С даты">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?= escape($date_to) ?>" placeholder="По дату">
            </div>
            <div class="col-md-2">
                <select name="success" class="form-select">
                    <option value="">Все</option>
                    <option value="1" <?= $success === '1' ? 'selected' : '' ?>>Успешные</option>
                    <option value="0" <?= $success === '0' ? 'selected' : '' ?>>Ошибки</option>
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Применить фильтры</button>
                <a href="logs.php" class="btn btn-secondary">Сбросить</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Записи (<?= count($logs) ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Аккаунт</th>
                        <th>Видео</th>
                        <th>Длительность</th>
                        <th>Лайк</th>
                        <th>Подписка</th>
                        <th>Статус</th>
                        <th>Ошибка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= formatDate($log['watched_at']) ?></td>
                        <td><?= escape($log['email'] ?? 'N/A') ?></td>
                        <td>
                            <a href="<?= escape($log['shorts_url']) ?>" target="_blank" class="text-decoration-none">
                                <?= escape(substr($log['shorts_url'], 0, 40)) ?>...
                            </a>
                        </td>
                        <td><?= $log['watch_seconds'] ?>s</td>
                        <td><?= $log['liked'] ? '✓' : '-' ?></td>
                        <td><?= $log['subscribed'] ? '✓' : '-' ?></td>
                        <td>
                            <?php if ($log['success']): ?>
                                <span class="badge bg-success">Успешно</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Ошибка</span>
                            <?php endif; ?>
                        </td>
                        <td><?= escape(substr($log['error_text'] ?? '', 0, 50)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
