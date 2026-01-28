<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'YouTube Shorts Viewer' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bs-body-bg: #0d1117;
            --bs-body-color: #c9d1d9;
        }
        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }
        .sidebar {
            min-height: 100vh;
            background-color: #161b22;
            border-right: 1px solid #30363d;
        }
        .nav-link {
            color: #c9d1d9;
        }
        .nav-link:hover, .nav-link.active {
            background-color: #21262d;
            color: #58a6ff;
        }
        .card {
            background-color: #161b22;
            border: 1px solid #30363d;
        }
        .table {
            color: var(--bs-body-color);
        }
        .table-dark {
            --bs-table-bg: #161b22;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 sidebar p-3">
                <h4 class="text-white mb-4">
                    <i class="bi bi-youtube"></i> Shorts Viewer
                </h4>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : '' ?>" href="accounts.php">
                            <i class="bi bi-people"></i> Аккаунты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'proxies.php' ? 'active' : '' ?>" href="proxies.php">
                            <i class="bi bi-router"></i> Прокси
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'videos.php' ? 'active' : '' ?>" href="videos.php">
                            <i class="bi bi-play-circle"></i> Видео
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>" href="logs.php">
                            <i class="bi bi-journal-text"></i> Логи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                            <i class="bi bi-gear"></i> Настройки
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Выход
                        </a>
                    </li>
                </ul>
            </nav>
            <main class="col-md-10 p-4">
