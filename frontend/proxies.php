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
        $proxies_text = $_POST['proxies'] ?? '';
        $type = $_POST['type'] ?? 'http';
        
        $proxies = explode("\n", $proxies_text);
        $added = 0;
        foreach ($proxies as $proxy) {
            $proxy = trim($proxy);
            if ($proxy) {
                $db->execute(
                    "INSERT INTO proxies (proxy_string, type) VALUES (?, ?)",
                    [$proxy, $type]
                );
                $added++;
            }
        }
        $message = "Добавлено прокси: $added";
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $db->execute("DELETE FROM proxies WHERE id = ?", [$id]);
        $message = 'Прокси удален';
    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 'active';
        $db->execute("UPDATE proxies SET status = ? WHERE id = ?", [$status, $id]);
        $message = 'Статус обновлен';
    }
}

$proxies = $db->fetchAll("SELECT * FROM proxies ORDER BY id DESC");

$pageTitle = 'Прокси';
include 'includes/header.php';
?>

<h1 class="mb-4">Управление прокси</h1>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5>Добавить прокси</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="http">HTTP</option>
                        <option value="https">HTTPS</option>
                        <option value="socks4">SOCKS4</option>
                        <option value="socks5">SOCKS5</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <textarea name="proxies" class="form-control" rows="3" 
                              placeholder="http://user:pass@ip:port (по одному на строку)" required></textarea>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Добавить</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Список прокси (<?= count($proxies) ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Прокси</th>
                        <th>Тип</th>
                        <th>Статус</th>
                        <th>Успешность</th>
                        <th>Просмотров сегодня</th>
                        <th>Последняя проверка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proxies as $proxy): ?>
                    <tr>
                        <td><?= $proxy['id'] ?></td>
                        <td><code><?= escape($proxy['proxy_string']) ?></code></td>
                        <td><?= escape($proxy['type']) ?></td>
                        <td><?= getStatusBadge($proxy['status']) ?></td>
                        <td><?= number_format($proxy['success_rate'], 1) ?>%</td>
                        <td><?= formatNumber($proxy['views_today']) ?></td>
                        <td><?= $proxy['last_check'] ? formatDate($proxy['last_check']) : 'Никогда' ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $proxy['id'] ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="active" <?= $proxy['status'] == 'active' ? 'selected' : '' ?>>Активен</option>
                                    <option value="dead" <?= $proxy['status'] == 'dead' ? 'selected' : '' ?>>Мертв</option>
                                    <option value="slow" <?= $proxy['status'] == 'slow' ? 'selected' : '' ?>>Медленный</option>
                                </select>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Удалить прокси?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $proxy['id'] ?>">
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
