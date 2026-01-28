"""
Модуль для работы с базой данных MySQL
"""
import mysql.connector
from mysql.connector import Error, pooling
from typing import Optional, Dict, List, Any, Tuple
import logging
from backend.config import DB_CONFIG

logger = logging.getLogger(__name__)

# Connection pool
_pool: Optional[pooling.MySQLConnectionPool] = None


def init_db_pool():
    """Инициализация connection pool"""
    global _pool
    try:
        _pool = pooling.MySQLConnectionPool(
            pool_name="youtubviewer_pool",
            pool_size=5,
            pool_reset_session=True,
            **DB_CONFIG
        )
        logger.info("Database connection pool initialized")
    except Error as e:
        logger.error(f"Error creating connection pool: {e}")
        raise


def get_connection():
    """Получить соединение из pool"""
    if _pool is None:
        init_db_pool()
    return _pool.get_connection()


class Database:
    """Класс для работы с БД"""
    
    @staticmethod
    def execute_query(query: str, params: Optional[Tuple] = None, fetch: bool = False) -> Optional[List[Dict]]:
        """Выполнить SELECT запрос"""
        conn = None
        try:
            conn = get_connection()
            cursor = conn.cursor(dictionary=True)
            cursor.execute(query, params or ())
            if fetch:
                result = cursor.fetchall()
                return result
            conn.commit()
            return None
        except Error as e:
            logger.error(f"Database error: {e}")
            if conn:
                conn.rollback()
            raise
        finally:
            if conn and conn.is_connected():
                cursor.close()
                conn.close()
    
    @staticmethod
    def execute_update(query: str, params: Optional[Tuple] = None) -> int:
        """Выполнить INSERT/UPDATE/DELETE"""
        conn = None
        try:
            conn = get_connection()
            cursor = conn.cursor()
            cursor.execute(query, params or ())
            conn.commit()
            return cursor.lastrowid
        except Error as e:
            logger.error(f"Database error: {e}")
            if conn:
                conn.rollback()
            raise
        finally:
            if conn and conn.is_connected():
                cursor.close()
                conn.close()
    
    @staticmethod
    def get_setting(key: str, default: str = '') -> str:
        """Получить настройку"""
        result = Database.execute_query(
            "SELECT setting_value FROM settings WHERE setting_key = %s",
            (key,),
            fetch=True
        )
        return result[0]['setting_value'] if result else default
    
    @staticmethod
    def get_active_accounts(limit: int = 10) -> List[Dict]:
        """Получить активные аккаунты"""
        return Database.execute_query(
            """
            SELECT id, email, cookies_json, proxy_id, views_today, success_rate
            FROM accounts 
            WHERE status = 'active'
            ORDER BY RAND()
            LIMIT %s
            """,
            (limit,),
            fetch=True
        ) or []
    
    @staticmethod
    def get_pending_videos(limit: int = 10) -> List[Dict]:
        """Получить видео для просмотра"""
        return Database.execute_query(
            """
            SELECT id, shorts_url, video_id, target_views, current_views
            FROM videos
            WHERE status IN ('pending', 'active')
            AND current_views < target_views
            ORDER BY priority DESC, id ASC
            LIMIT %s
            """,
            (limit,),
            fetch=True
        ) or []
    
    @staticmethod
    def get_proxy(proxy_id: Optional[int]) -> Optional[Dict]:
        """Получить прокси по ID"""
        if not proxy_id:
            return None
        result = Database.execute_query(
            "SELECT * FROM proxies WHERE id = %s AND status = 'active'",
            (proxy_id,),
            fetch=True
        )
        return result[0] if result else None
    
    @staticmethod
    def log_view(account_id: int, video_id: int, watch_seconds: int, 
                 success: bool, error_text: Optional[str] = None,
                 proxy_id: Optional[int] = None, liked: bool = False,
                 subscribed: bool = False, commented: bool = False) -> int:
        """Записать лог просмотра"""
        return Database.execute_update(
            """
            INSERT INTO view_logs 
            (account_id, video_id, proxy_id, watch_seconds, success, error_text, liked, subscribed, commented)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            """,
            (account_id, video_id, proxy_id, watch_seconds, success, error_text or '', 
             liked, subscribed, commented)
        )
    
    @staticmethod
    def update_account_cookies(account_id: int, cookies_json: str):
        """Обновить cookies аккаунта"""
        Database.execute_update(
            "UPDATE accounts SET cookies_json = %s, last_used_at = NOW() WHERE id = %s",
            (cookies_json, account_id)
        )
    
    @staticmethod
    def update_account_status(account_id: int, status: str):
        """Обновить статус аккаунта"""
        Database.execute_update(
            "UPDATE accounts SET status = %s WHERE id = %s",
            (status, account_id)
        )
    
    @staticmethod
    def increment_account_views(account_id: int):
        """Увеличить счетчик просмотров"""
        Database.execute_update(
            "UPDATE accounts SET views_count = views_count + 1, views_today = views_today + 1, last_used_at = NOW() WHERE id = %s",
            (account_id,)
        )
    
    @staticmethod
    def increment_video_views(video_id: int):
        """Увеличить счетчик просмотров видео"""
        Database.execute_update(
            "UPDATE videos SET current_views = current_views + 1 WHERE id = %s",
            (video_id,)
        )
    
    @staticmethod
    def check_account_views_limit(account_id: int) -> bool:
        """Проверить лимит просмотров в час"""
        max_per_hour = int(Database.get_setting('max_views_per_account_per_hour', '5'))
        result = Database.execute_query(
            """
            SELECT COUNT(*) as count FROM view_logs
            WHERE account_id = %s 
            AND watched_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND success = 1
            """,
            (account_id,),
            fetch=True
        )
        count = result[0]['count'] if result else 0
        return count < max_per_hour
