<?php
session_start();
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// Статистика
$stats = [
    'total_accounts' => $db->fetchOne("SELECT COUNT(*) as count FROM accounts")['count'] ?? 0,
    'active_accounts' => $db->fetchOne("SELECT COUNT(*) as count FROM accounts WHERE status = 'active'")['count'] ?? 0,
    'total_videos' => $db->fetchOne("SELECT COUNT(*) as count FROM videos")['count'] ?? 0,
    'active_videos' => $db->fetchOne("SELECT COUNT(*) as count FROM videos WHERE status IN ('pending', 'active')")['count'] ?? 0,
    'views_today' => $db->fetchOne("SELECT COUNT(*) as count FROM view_logs WHERE DATE(watched_at) = CURDATE() AND success = 1")['count'] ?? 0,
    'total_views' => $db->fetchOne("SELECT COUNT(*) as count FROM view_logs WHERE success = 1")['count'] ?? 0,
];

$recent_logs = $db->fetchAll(
    "SELECT vl.*, a.email, v.shorts_url 
     FROM view_logs vl
     LEFT JOIN accounts a ON vl.account_id = a.id
     LEFT JOIN videos v ON vl.video_id = v.id
     ORDER BY vl.watched_at DESC
     LIMIT 10"
);

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<h1 class="mb-4">Dashboard</h1>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Аккаунты</h5>
                <h2><?= formatNumber($stats['active_accounts']) ?> / <?= formatNumber($stats['total_accounts']) ?></h2>
                <small class="text-muted">Активных / Всего</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Видео</h5>
                <h2><?= formatNumber($stats['active_videos']) ?> / <?= formatNumber($stats['total_videos']) ?></h2>
                <small class="text-muted">Активных / Всего</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Просмотры сегодня</h5>
                <h2><?= formatNumber($stats['views_today']) ?></h2>
                <small class="text-muted">Успешных просмотров</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Всего просмотров</h5>
                <h2><?= formatNumber($stats['total_views']) ?></h2>
                <small class="text-muted">За всё время</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Последние просмотры</h5>
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
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?= formatDate($log['watched_at']) ?></td>
                        <td><?= escape($log['email'] ?? 'N/A') ?></td>
                        <td>
                            <a href="<?= escape($log['shorts_url']) ?>" target="_blank" class="text-decoration-none">
                                <?= escape(substr($log['shorts_url'], 0, 50)) ?>...
                            </a>
                        </td>
                        <td><?= $log['watch_seconds'] ?>s</td>
                        <td>
                            <?php if ($log['success']): ?>
                                <span class="badge bg-success">Успешно</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Ошибка</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
