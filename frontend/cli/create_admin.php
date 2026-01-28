<?php
/**
 * Скрипт для создания администратора
 * Использование: php create_admin.php
 */
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

echo "Создание администратора\n";
echo "======================\n\n";

$username = readline("Логин: ");
$password = readline("Пароль: ");

if (empty($username) || empty($password)) {
    die("Логин и пароль обязательны!\n");
}

$password_hash = password_hash($password, PASSWORD_BCRYPT);

$result = $db->execute(
    "INSERT INTO users (username, password_hash) VALUES (?, ?)",
    [$username, $password_hash]
);

if ($result) {
    echo "\n✓ Администратор создан успешно!\n";
} else {
    echo "\n✗ Ошибка при создании администратора\n";
}
