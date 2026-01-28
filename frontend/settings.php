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
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            $db->execute(
                "UPDATE settings SET setting_value = ? WHERE setting_key = ?",
                [$value, $setting_key]
            );
        }
    }
    $message = 'Настройки сохранены';
}

$settings = $db->fetchAll("SELECT * FROM settings ORDER BY setting_key");

$pageTitle = 'Настройки';
include 'includes/header.php';
?>

<h1 class="mb-4">Настройки системы</h1>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5>Глобальные параметры</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <?php foreach ($settings as $setting): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <?= escape($setting['description'] ?? $setting['setting_key']) ?>
                    </label>
                    <?php if (in_array($setting['setting_key'], ['headless_mode'])): ?>
                        <select name="setting_<?= escape($setting['setting_key']) ?>" class="form-select">
                            <option value="true" <?= $setting['setting_value'] == 'true' ? 'selected' : '' ?>>Да</option>
                            <option value="false" <?= $setting['setting_value'] == 'false' ? 'selected' : '' ?>>Нет</option>
                        </select>
                    <?php elseif (in_array($setting['setting_key'], ['user_agent_type'])): ?>
                        <select name="setting_<?= escape($setting['setting_key']) ?>" class="form-select">
                            <option value="mobile" <?= $setting['setting_value'] == 'mobile' ? 'selected' : '' ?>>Мобильный</option>
                            <option value="desktop" <?= $setting['setting_value'] == 'desktop' ? 'selected' : '' ?>>Десктоп</option>
                        </select>
                    <?php else: ?>
                        <input type="text" name="setting_<?= escape($setting['setting_key']) ?>" 
                               class="form-control" value="<?= escape($setting['setting_value']) ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить настройки</button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>⚠️ Важное предупреждение</h5>
    </div>
    <div class="card-body">
        <p class="text-warning">
            <strong>Проект только для образовательных целей.</strong> Использование для искусственной накрутки просмотров 
            нарушает правила YouTube и может привести к перманентному бану аккаунтов и каналов.
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
