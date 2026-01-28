-- YouTube Shorts Viewer Database Schema
-- MySQL 8.x

CREATE DATABASE IF NOT EXISTS youtubviewer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE youtubviewer;

-- Таблица аккаунтов Google
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_encrypted TEXT NOT NULL COMMENT 'bcrypt hash',
    status ENUM('active', 'banned', 'need_phone', 'suspended') DEFAULT 'active',
    cookies_json TEXT COMMENT 'JSON cookies после логина',
    last_used_at DATETIME NULL,
    views_count INT DEFAULT 0,
    views_today INT DEFAULT 0,
    proxy_id INT NULL,
    success_rate DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Процент успешных просмотров',
    last_check_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_proxy (proxy_id),
    INDEX idx_last_used (last_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица прокси
CREATE TABLE IF NOT EXISTS proxies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proxy_string TEXT NOT NULL COMMENT 'http://user:pass@ip:port или socks5://...',
    type ENUM('http', 'https', 'socks4', 'socks5') DEFAULT 'http',
    status ENUM('active', 'dead', 'slow') DEFAULT 'active',
    success_rate DECIMAL(5,2) DEFAULT 100.00,
    last_check DATETIME NULL,
    views_today INT DEFAULT 0,
    max_views_per_day INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица видео/Shorts
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shorts_url VARCHAR(500) NOT NULL,
    video_id VARCHAR(50) NULL COMMENT 'Извлеченный ID из URL',
    target_views INT DEFAULT 100,
    current_views INT DEFAULT 0,
    priority INT DEFAULT 5 COMMENT '1-10, где 10 - высший приоритет',
    status ENUM('pending', 'active', 'completed', 'paused') DEFAULT 'pending',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Логи просмотров
CREATE TABLE IF NOT EXISTS view_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    video_id INT NOT NULL,
    proxy_id INT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    watch_seconds INT NOT NULL COMMENT 'Сколько секунд смотрел',
    success BOOLEAN DEFAULT TRUE,
    error_text TEXT NULL,
    liked BOOLEAN DEFAULT FALSE,
    subscribed BOOLEAN DEFAULT FALSE,
    commented BOOLEAN DEFAULT FALSE,
    INDEX idx_account (account_id),
    INDEX idx_video (video_id),
    INDEX idx_watched_at (watched_at),
    INDEX idx_success (success),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (proxy_id) REFERENCES proxies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Глобальные настройки
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица пользователей веб-интерфейса (админы)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    totp_secret VARCHAR(32) NULL COMMENT 'TOTP секрет для 2FA',
    totp_enabled BOOLEAN DEFAULT FALSE,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка дефолтных настроек
INSERT INTO settings (setting_key, setting_value, description) VALUES
('min_watch_sec', '9', 'Минимальное время просмотра в секундах'),
('max_watch_sec', '28', 'Максимальное время просмотра в секундах'),
('pause_min', '30', 'Минимальная пауза между просмотрами (секунды)'),
('pause_max', '120', 'Максимальная пауза между просмотрами (секунды)'),
('like_probability', '10', 'Вероятность лайка в процентах (0-100)'),
('subscribe_probability', '5', 'Вероятность подписки в процентах (0-100)'),
('comment_probability', '3', 'Вероятность комментария в процентах (0-100)'),
('max_views_per_account_per_hour', '5', 'Максимум просмотров с одного аккаунта в час'),
('headless_mode', 'false', 'Запуск браузера в headless режиме'),
('user_agent_type', 'mobile', 'Тип user-agent: mobile или desktop'),
('retry_attempts', '3', 'Количество попыток при ошибке'),
('check_accounts_interval_hours', '24', 'Интервал проверки аккаунтов (часы)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
