"""
Вспомогательные утилиты
"""
import random
import time
import json
import re
from typing import Optional, Dict, List
from fake_useragent import UserAgent
import logging

logger = logging.getLogger(__name__)

ua = UserAgent()


def get_random_user_agent(device_type: str = 'mobile') -> str:
    """Получить случайный user-agent"""
    try:
        if device_type == 'mobile':
            return ua.random  # По умолчанию мобильный
        else:
            return ua.random
    except:
        # Fallback user-agents
        mobile_agents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36',
        ]
        desktop_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
        ]
        return random.choice(mobile_agents if device_type == 'mobile' else desktop_agents)


def get_random_window_size(device_type: str = 'mobile') -> tuple:
    """Получить случайный размер окна"""
    if device_type == 'mobile':
        # Мобильные пропорции (9:16)
        heights = [720, 800, 900, 1080, 1280]
        height = random.choice(heights)
        width = int(height * 9 / 16)
        return (width, height)
    else:
        # Десктоп
        sizes = [(1280, 720), (1366, 768), (1920, 1080), (1440, 900)]
        return random.choice(sizes)


def extract_video_id(url: str) -> Optional[str]:
    """Извлечь ID видео из YouTube URL"""
    patterns = [
        r'(?:youtube\.com\/shorts\/|youtu\.be\/|youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})',
        r'youtube\.com\/embed\/([a-zA-Z0-9_-]{11})',
    ]
    for pattern in patterns:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    return None


def human_delay(min_sec: float = 0.5, max_sec: float = 2.0):
    """Человеческая задержка с нормальным распределением"""
    delay = random.uniform(min_sec, max_sec)
    time.sleep(delay)


def bezier_mouse_move(start_x: int, start_y: int, end_x: int, end_y: int, 
                     steps: int = 20) -> List[tuple]:
    """Генерация точек для движения мыши по Bezier-кривой"""
    # Простая квадратичная Bezier с контрольной точкой
    control_x = start_x + (end_x - start_x) * random.uniform(0.3, 0.7)
    control_y = start_y + (end_y - start_y) * random.uniform(0.3, 0.7) + random.randint(-50, 50)
    
    points = []
    for i in range(steps + 1):
        t = i / steps
        # Квадратичная Bezier формула
        x = (1 - t) ** 2 * start_x + 2 * (1 - t) * t * control_x + t ** 2 * end_x
        y = (1 - t) ** 2 * start_y + 2 * (1 - t) * t * control_y + t ** 2 * end_y
        points.append((int(x), int(y)))
    return points


def parse_proxy(proxy_string: str) -> Dict[str, str]:
    """Парсинг прокси строки"""
    # Формат: http://user:pass@ip:port или socks5://user:pass@ip:port
    pattern = r'^(https?|socks[45])://(?:([^:]+):([^@]+)@)?([^:]+):(\d+)$'
    match = re.match(pattern, proxy_string)
    if not match:
        raise ValueError(f"Invalid proxy format: {proxy_string}")
    
    proxy_type, username, password, host, port = match.groups()
    return {
        'type': proxy_type,
        'host': host,
        'port': int(port),
        'username': username,
        'password': password
    }


def should_perform_action(probability: int) -> bool:
    """Проверить, нужно ли выполнить действие (лайк, подписка и т.д.)"""
    return random.randint(1, 100) <= probability


def load_cookies(cookies_json: Optional[str]) -> List[Dict]:
    """Загрузить cookies из JSON"""
    if not cookies_json:
        return []
    try:
        return json.loads(cookies_json)
    except:
        return []


def save_cookies(cookies: List[Dict]) -> str:
    """Сохранить cookies в JSON"""
    return json.dumps(cookies)
