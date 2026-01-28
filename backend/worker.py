#!/usr/bin/env python3
"""
Основной воркер для просмотра YouTube Shorts
"""
import argparse
import time
import random
import logging
import sys
from pathlib import Path
from typing import Optional, Dict
from rich.console import Console
from rich.logging import RichHandler
from rich.progress import Progress, SpinnerColumn, TextColumn

from backend.config import LOGS_DIR, WORKER_ID
from backend.db import Database, init_db_pool
from backend.browser import BrowserManager
from backend.utils import (
    extract_video_id, human_delay, load_cookies, save_cookies,
    should_perform_action, parse_proxy
)

# Настройка логирования
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOGS_DIR / 'worker.log', encoding='utf-8'),
        RichHandler(console=Console(), rich_tracebacks=True)
    ]
)
logger = logging.getLogger(__name__)
console = Console()


class YouTubeShortsWorker:
    """Воркер для просмотра YouTube Shorts"""
    
    def __init__(self, worker_id: str = '1'):
        self.worker_id = worker_id
        self.db = Database()
        self.running = False
        self.stats = {
            'total_views': 0,
            'successful_views': 0,
            'failed_views': 0,
            'accounts_used': set()
        }
    
    def get_settings(self) -> Dict:
        """Получить настройки из БД"""
        return {
            'min_watch_sec': int(self.db.get_setting('min_watch_sec', '9')),
            'max_watch_sec': int(self.db.get_setting('max_watch_sec', '28')),
            'pause_min': int(self.db.get_setting('pause_min', '30')),
            'pause_max': int(self.db.get_setting('pause_max', '120')),
            'like_probability': int(self.db.get_setting('like_probability', '10')),
            'subscribe_probability': int(self.db.get_setting('subscribe_probability', '5')),
            'headless': self.db.get_setting('headless_mode', 'false').lower() == 'true',
            'device_type': self.db.get_setting('user_agent_type', 'mobile'),
            'retry_attempts': int(self.db.get_setting('retry_attempts', '3')),
        }
    
    def process_view(self, account: Dict, video: Dict, settings: Dict) -> bool:
        """Обработать один просмотр"""
        browser = None
        try:
            # Проверка лимита просмотров
            if not self.db.check_account_views_limit(account['id']):
                logger.warning(f"Account {account['id']} reached hourly limit")
                return False
            
            # Получение прокси
            proxy = None
            if account.get('proxy_id'):
                proxy_data = self.db.get_proxy(account['proxy_id'])
                if proxy_data:
                    try:
                        proxy = parse_proxy(proxy_data['proxy_string'])
                    except Exception as e:
                        logger.warning(f"Failed to parse proxy: {e}")
            
            # Запуск браузера
            browser = BrowserManager(
                proxy=proxy,
                headless=settings['headless'],
                device_type=settings['device_type']
            )
            browser.start()
            
            # Загрузка cookies или логин
            cookies = load_cookies(account.get('cookies_json'))
            if cookies:
                browser.load_cookies(cookies)
                browser.driver.get('https://www.youtube.com')
                human_delay(2, 3)
                
                # Проверка, нужен ли логин
                if 'accounts.google.com' in browser.driver.current_url:
                    logger.info(f"Cookies expired for account {account['id']}, need login")
                    # Здесь можно добавить логику логина, но для безопасности пропускаем
                    return False
            else:
                logger.warning(f"No cookies for account {account['id']}, skipping")
                return False
            
            # Просмотр видео
            watch_seconds = random.randint(settings['min_watch_sec'], settings['max_watch_sec'])
            success, error = browser.watch_shorts(video['shorts_url'], watch_seconds)
            
            if not success:
                self.db.log_view(
                    account_id=account['id'],
                    video_id=video['id'],
                    watch_seconds=0,
                    success=False,
                    error_text=error,
                    proxy_id=account.get('proxy_id')
                )
                return False
            
            # Дополнительные действия
            liked = False
            subscribed = False
            commented = False
            
            if should_perform_action(settings['like_probability']):
                liked = browser.like_video()
            
            if should_perform_action(settings['subscribe_probability']):
                subscribed = browser.subscribe_channel()
            
            # Сохранение обновленных cookies
            new_cookies = browser.get_cookies()
            if new_cookies:
                self.db.update_account_cookies(account['id'], save_cookies(new_cookies))
            
            # Логирование
            self.db.log_view(
                account_id=account['id'],
                video_id=video['id'],
                watch_seconds=watch_seconds,
                success=True,
                proxy_id=account.get('proxy_id'),
                liked=liked,
                subscribed=subscribed,
                commented=commented
            )
            
            # Обновление счетчиков
            self.db.increment_account_views(account['id'])
            self.db.increment_video_views(video['id'])
            
            self.stats['successful_views'] += 1
            self.stats['accounts_used'].add(account['id'])
            
            logger.info(f"✓ Viewed {video['shorts_url']} for {watch_seconds}s (account {account['id']})")
            return True
            
        except Exception as e:
            logger.error(f"Error processing view: {e}")
            if account and video:
                self.db.log_view(
                    account_id=account['id'],
                    video_id=video['id'],
                    watch_seconds=0,
                    success=False,
                    error_text=str(e),
                    proxy_id=account.get('proxy_id') if account else None
                )
            self.stats['failed_views'] += 1
            return False
        finally:
            if browser:
                browser.close()
                human_delay(1, 2)  # Пауза перед следующим браузером
    
    def run_once(self):
        """Однократный запуск"""
        console.print(f"[bold green]Worker {self.worker_id} started (once mode)[/bold green]")
        
        settings = self.get_settings()
        accounts = self.db.get_active_accounts(limit=5)
        videos = self.db.get_pending_videos(limit=10)
        
        if not accounts:
            console.print("[yellow]No active accounts found[/yellow]")
            return
        
        if not videos:
            console.print("[yellow]No videos to watch[/yellow]")
            return
        
        console.print(f"[cyan]Found {len(accounts)} accounts and {len(videos)} videos[/cyan]")
        
        # Выбираем случайные аккаунт и видео
        account = random.choice(accounts)
        video = random.choice(videos)
        
        self.process_view(account, video, settings)
        self.stats['total_views'] += 1
        
        console.print(f"[green]Completed. Stats: {self.stats}[/green]")
    
    def run_daemon(self):
        """Запуск в режиме daemon"""
        console.print(f"[bold green]Worker {self.worker_id} started (daemon mode)[/bold green]")
        self.running = True
        
        try:
            while self.running:
                settings = self.get_settings()
                
                # Получаем задачи
                accounts = self.db.get_active_accounts(limit=10)
                videos = self.db.get_pending_videos(limit=20)
                
                if not accounts or not videos:
                    console.print("[yellow]Waiting for accounts/videos...[/yellow]")
                    time.sleep(60)
                    continue
                
                # Выбираем случайные
                account = random.choice(accounts)
                video = random.choice(videos)
                
                # Обрабатываем
                self.process_view(account, video, settings)
                self.stats['total_views'] += 1
                
                # Пауза между просмотрами
                pause = random.randint(settings['pause_min'], settings['pause_max'])
                console.print(f"[dim]Pausing for {pause}s...[/dim]")
                time.sleep(pause)
                
        except KeyboardInterrupt:
            console.print("\n[yellow]Stopping worker...[/yellow]")
            self.running = False
        except Exception as e:
            logger.error(f"Daemon error: {e}")
            raise
        finally:
            console.print(f"[green]Worker stopped. Final stats: {self.stats}[/green]")


def main():
    parser = argparse.ArgumentParser(description='YouTube Shorts Viewer Worker')
    parser.add_argument('--mode', choices=['once', 'daemon'], default='once',
                       help='Worker mode: once or daemon')
    parser.add_argument('--worker-id', default=WORKER_ID,
                       help='Worker ID')
    
    args = parser.parse_args()
    
    # Инициализация БД
    try:
        init_db_pool()
    except Exception as e:
        console.print(f"[red]Failed to connect to database: {e}[/red]")
        sys.exit(1)
    
    # Запуск воркера
    worker = YouTubeShortsWorker(worker_id=args.worker_id)
    
    if args.mode == 'once':
        worker.run_once()
    else:
        worker.run_daemon()


if __name__ == '__main__':
    main()
