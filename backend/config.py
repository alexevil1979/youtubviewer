"""
Конфигурация для Python воркера
"""
import os
from pathlib import Path
from dotenv import load_dotenv

# Загружаем .env из корня проекта
BASE_DIR = Path(__file__).parent.parent
load_dotenv(BASE_DIR / 'config' / '.env')

# База данных
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASS', ''),
    'database': os.getenv('DB_NAME', 'youtubviewer'),
    'charset': 'utf8mb4',
    'collation': 'utf8mb4_unicode_ci'
}

# Настройки воркера
WORKER_ID = os.getenv('WORKER_ID', '1')
LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO')
CHROME_PATH = os.getenv('CHROME_PATH', '')

# Пути
LOGS_DIR = BASE_DIR / 'logs'
LOGS_DIR.mkdir(exist_ok=True)
