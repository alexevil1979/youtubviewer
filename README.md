# YouTube Shorts Viewer - Мультиаккаунтная система просмотра

## ⚠️ ВАЖНОЕ ПРЕДУПРЕЖДЕНИЕ

**Проект только для образовательных целей. Использование для искусственной накрутки просмотров нарушает правила YouTube и может привести к перманентному бану аккаунтов и каналов.**

Этот проект создан исключительно для изучения технологий веб-автоматизации, работы с Selenium, обхода анти-бот систем и разработки full-stack приложений. Авторы не несут ответственности за использование проекта в коммерческих или незаконных целях.

---

## 📋 Описание

Полноценная система для управления просмотром YouTube Shorts с нескольких Google аккаунтов. Включает веб-интерфейс управления, автоматизацию через Selenium, поддержку прокси, систему логирования и статистики.

## 🏗️ Архитектура

- **Backend**: Python 3.9+ с Selenium + undetected-chromedriver
- **Frontend**: PHP 8.1 с MySQLi
- **База данных**: MySQL 8.x
- **UI**: TailwindCSS + Bootstrap 5 (dark mode)

## 📁 Структура проекта

```
youtubviewer/
├── backend/              # Python скрипты
│   ├── worker.py        # Основной воркер
│   ├── browser.py       # Управление браузером
│   ├── account_manager.py
│   ├── proxy_manager.py
│   └── utils.py
├── frontend/            # PHP веб-интерфейс
│   ├── index.php
│   ├── login.php
│   ├── api/             # AJAX endpoints
│   ├── includes/        # Классы и функции
│   ├── pages/           # Страницы
│   └── assets/          # CSS, JS
├── config/              # Конфигурация
│   └── .env.example
├── db/                  # SQL схемы
│   └── schema.sql
├── logs/                # Логи
├── requirements.txt     # Python зависимости
└── composer.json        # PHP зависимости
```

## 🚀 Установка

### Требования

- Python 3.9+
- PHP 8.1+
- MySQL 8.x
- Chrome/Chromium браузер
- Composer (для PHP)

### Шаг 1: Клонирование и настройка окружения

```bash
git clone <repository>
cd youtubviewer
```

### Шаг 2: Настройка базы данных

```bash
mysql -u root -p < db/schema.sql
```

### Шаг 3: Конфигурация

```bash
cp config/.env.example config/.env
# Отредактируйте config/.env с вашими данными
```

### Шаг 4: Установка Python зависимостей

```bash
pip install -r requirements.txt
```

### Шаг 5: Установка PHP зависимостей

```bash
composer install
```

### Шаг 6: Настройка веб-сервера

Настройте Apache/Nginx для работы с PHP:
- DocumentRoot: `frontend/`
- Убедитесь, что PHP имеет доступ к MySQL

### Шаг 7: Создание администратора

```bash
php frontend/cli/create_admin.php
```

## 🔄 Workflow: правки → Git → VPS

**Правило:** после любых правок в коде:
1. Изменения отправляются в Git (`git add` → `commit` → `push`).
2. В выводе или в коммите указываются команды для обновления на VPS (см. ниже).

Так поддерживается синхронизация репозитория и сервера.

### 1. После правок (локально) — отправить в Git

```bash
cd youtubviewer
git add .
git status                    # проверить список изменений
git commit -m "описание правок"
git push origin main
```

### 2. Обновление на VPS-сервере

Подключитесь по SSH к серверу и выполните:

```bash
cd /path/to/youtubviewer       # путь к проекту на VPS

git pull origin main

# при изменении PHP-зависимостей
composer install --no-dev

# при изменении Python-зависимостей
pip install -r requirements.txt

# при изменении схемы БД (миграции)
mysql -u USER -p DATABASE < db/schema.sql
# или применяйте только нужные миграции вручную

# перезапуск воркеров (если запущены через systemd/supervisor)
sudo systemctl restart youtubviewer-worker
# или убить старые процессы и запустить заново:
# pkill -f "worker.py --mode=daemon"
# nohup python backend/worker.py --mode=daemon >> logs/worker.log 2>&1 &
```

Краткая шпаргалка для быстрого обновления только кода (без миграций и перезапуска воркеров):

```bash
cd /path/to/youtubviewer && git pull origin main
```

---

## 💻 Использование

### Запуск веб-интерфейса

Откройте в браузере: `http://localhost/youtubviewer/frontend/`

### Запуск Python воркера

**Однократный запуск:**
```bash
python backend/worker.py --mode=once
```

**Фоновый режим (daemon):**
```bash
python backend/worker.py --mode=daemon
```

**С несколькими воркерами:**
```bash
python backend/worker.py --mode=daemon --worker-id=1 &
python backend/worker.py --mode=daemon --worker-id=2 &
```

## 🔧 Настройки

Основные настройки находятся в `config/.env`:

- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` - параметры БД
- `ADMIN_USERNAME`, `ADMIN_PASSWORD` - учетные данные администратора
- `MIN_WATCH_SEC`, `MAX_WATCH_SEC` - диапазон времени просмотра
- `PAUSE_MIN`, `PAUSE_MAX` - паузы между просмотрами

## 📊 Функционал

### Веб-интерфейс

- **Dashboard**: Статистика и обзор системы
- **Аккаунты**: Управление Google аккаунтами (добавление, импорт, проверка)
- **Прокси**: Управление прокси-серверами
- **Видео**: Добавление YouTube Shorts для просмотра
- **Логи**: Просмотр истории просмотров
- **Настройки**: Глобальные параметры системы

### Python воркер

- Автоматический логин в Google/YouTube
- Сохранение и использование cookies
- Имитация человеческого поведения
- Обход анти-бот систем
- Поддержка прокси (HTTP/SOCKS5)
- Обработка ошибок и retry логика

## 🛡️ Защита от обнаружения

- Рандомные user-agent (мобильные + десктоп)
- Рандомные размеры окна
- Отключение webdriver-сигнатур
- Имитация человеческих движений мыши (Bezier-кривые)
- Случайные паузы и задержки
- Ограничение просмотров на аккаунт/час

## 📝 Логирование

Логи сохраняются в:
- `logs/worker.log` - логи Python воркера
- База данных `view_logs` - история просмотров

## ⚙️ Troubleshooting

### Проблемы с ChromeDriver

Убедитесь, что версия Chrome совместима с undetected-chromedriver. При необходимости обновите:

```bash
pip install --upgrade undetected-chromedriver
```

### Проблемы с подключением к БД

Проверьте настройки в `config/.env` и убедитесь, что MySQL запущен.

### Аккаунты блокируются

- Уменьшите количество просмотров в час
- Используйте качественные прокси
- Увеличьте паузы между просмотрами
- Используйте мобильные user-agent

## 📄 Лицензия

Проект создан исключительно в образовательных целях.

## 🤝 Вклад

Проект предназначен для обучения. Любые улучшения приветствуются через Pull Requests.

---

**Помните**: Использование этого проекта для накрутки просмотров может привести к бану аккаунтов. Используйте ответственно и только для образовательных целей.
