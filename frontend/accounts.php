<?php
session_start();
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$message = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $proxy_id = $_POST['proxy_id'] ?? null;
        
        if ($email && $password) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $db->execute(
                "INSERT INTO accounts (email, password_encrypted, proxy_id) VALUES (?, ?, ?)",
                [$email, $password_hash, $proxy_id ?: null]
            );
            $message = 'Аккаунт добавлен';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $db->execute("DELETE FROM accounts WHERE id = ?", [$id]);
        $message = 'Аккаунт удален';
    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 'active';
        $db->execute("UPDATE accounts SET status = ? WHERE id = ?", [$status, $id]);
        $message = 'Статус обновлен';
    }
}

$accounts = $db->fetchAll(
    "SELECT a.*, p.proxy_string as proxy_info 
     FROM accounts a
     LEFT JOIN proxies p ON a.proxy_id = p.id
     ORDER BY a.id DESC"
);

$proxies = $db->fetchAll("SELECT id, proxy_string FROM proxies WHERE status = 'active'");

$pageTitle = 'Аккаунты';
include 'includes/header.php';
?>

<h1 class="mb-4">Управление аккаунтами</h1>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5>Добавить аккаунт</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-4">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="col-md-4">
                    <input type="password" name="password" class="form-control" placeholder="Пароль" required>
                </div>
                <div class="col-md-3">
                    <select name="proxy_id" class="form-select">
                        <option value="">Без прокси</option>
                        <?php foreach ($proxies as $proxy): ?>
                            <option value="<?= $proxy['id'] ?>"><?= escape($proxy['proxy_string']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Добавить</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Список аккаунтов (<?= count($accounts) ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Статус</th>
                        <th>Просмотров</th>
                        <th>Сегодня</th>
                        <th>Успешность</th>
                        <th>Прокси</th>
                        <th>Последнее использование</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $acc): ?>
                    <tr>
                        <td><?= $acc['id'] ?></td>
                        <td><?= escape($acc['email']) ?></td>
                        <td><?= getStatusBadge($acc['status']) ?></td>
                        <td><?= formatNumber($acc['views_count']) ?></td>
                        <td><?= formatNumber($acc['views_today']) ?></td>
                        <td><?= number_format($acc['success_rate'], 1) ?>%</td>
                        <td><?= escape($acc['proxy_info'] ?? 'Нет') ?></td>
                        <td><?= $acc['last_used_at'] ? formatDate($acc['last_used_at']) : 'Никогда' ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="active" <?= $acc['status'] == 'active' ? 'selected' : '' ?>>Активен</option>
                                    <option value="banned" <?= $acc['status'] == 'banned' ? 'selected' : '' ?>>Забанен</option>
                                    <option value="need_phone" <?= $acc['status'] == 'need_phone' ? 'selected' : '' ?>>Нужен телефон</option>
                                </select>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Удалить аккаунт?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $acc['id'] ?>">
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
