<?php
/**
 * Вспомогательные функции
 */

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function formatNumber($number) {
    return number_format($number, 0, '.', ' ');
}

function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Активен</span>',
        'banned' => '<span class="badge bg-danger">Забанен</span>',
        'need_phone' => '<span class="badge bg-warning">Нужен телефон</span>',
        'suspended' => '<span class="badge bg-secondary">Приостановлен</span>',
        'pending' => '<span class="badge bg-info">Ожидает</span>',
        'completed' => '<span class="badge bg-success">Завершено</span>',
        'paused' => '<span class="badge bg-secondary">Приостановлено</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . escape($status) . '</span>';
}

function extractVideoId($url) {
    preg_match('/(?:youtube\.com\/shorts\/|youtu\.be\/|youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/', $url, $matches);
    return $matches[1] ?? null;
}
