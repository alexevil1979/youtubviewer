"""
Управление браузером через Selenium с обходом детекции
"""
import undetected_chromedriver as uc
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import TimeoutException, WebDriverException
from selenium_stealth import stealth
import random
import time
import logging
from typing import Optional, Dict, List, Tuple
from backend.utils import get_random_user_agent, get_random_window_size, bezier_mouse_move, human_delay
from backend.config import CHROME_PATH

logger = logging.getLogger(__name__)


class BrowserManager:
    """Менеджер браузера с анти-детект функциями"""
    
    def __init__(self, proxy: Optional[Dict] = None, headless: bool = False, device_type: str = 'mobile'):
        self.proxy = proxy
        self.headless = headless
        self.device_type = device_type
        self.driver: Optional[webdriver.Chrome] = None
        self.user_agent = get_random_user_agent(device_type)
        self.window_size = get_random_window_size(device_type)
    
    def start(self) -> webdriver.Chrome:
        """Запустить браузер"""
        options = uc.ChromeOptions()
        
        # Базовые опции
        if self.headless:
            options.add_argument('--headless=new')
        
        # Размер окна
        width, height = self.window_size
        options.add_argument(f'--window-size={width},{height}')
        
        # User-agent
        options.add_argument(f'--user-agent={self.user_agent}')
        
        # Анти-детект опции
        options.add_argument('--disable-blink-features=AutomationControlled')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-gpu')
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option('useAutomationExtension', False)
        
        # Прокси
        if self.proxy:
            proxy_str = f"{self.proxy['host']}:{self.proxy['port']}"
            if self.proxy.get('username'):
                proxy_str = f"{self.proxy['username']}:{self.proxy['password']}@{proxy_str}"
            options.add_argument(f'--proxy-server={self.proxy["type"]}://{proxy_str}')
        
        # Путь к Chrome
        if CHROME_PATH:
            options.binary_location = CHROME_PATH
        
        try:
            self.driver = uc.Chrome(options=options, version_main=None)
            
            # Применяем stealth
            stealth(
                self.driver,
                languages=["en-US", "en"],
                vendor="Google Inc.",
                platform="Win32",
                webgl_vendor="Intel Inc.",
                renderer="Intel Iris OpenGL Engine",
                fix_hairline=True,
            )
            
            # Устанавливаем размер окна
            self.driver.set_window_size(width, height)
            
            # Удаляем webdriver свойство
            self.driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
                'source': '''
                    Object.defineProperty(navigator, 'webdriver', {
                        get: () => undefined
                    });
                '''
            })
            
            logger.info(f"Browser started: {width}x{height}, UA: {self.user_agent[:50]}...")
            return self.driver
            
        except Exception as e:
            logger.error(f"Failed to start browser: {e}")
            raise
    
    def load_cookies(self, cookies: List[Dict], domain: str = '.youtube.com'):
        """Загрузить cookies"""
        if not self.driver or not cookies:
            return
        
        try:
            self.driver.get(f'https://{domain}')
            time.sleep(2)
            for cookie in cookies:
                try:
                    # Удаляем лишние поля
                    cookie_clean = {k: v for k, v in cookie.items() 
                                  if k in ['name', 'value', 'domain', 'path', 'expires', 'secure', 'httpOnly']}
                    if 'expires' in cookie_clean and isinstance(cookie_clean['expires'], str):
                        # Пропускаем expires если это строка
                        cookie_clean.pop('expires', None)
                    self.driver.add_cookie(cookie_clean)
                except Exception as e:
                    logger.debug(f"Failed to add cookie {cookie.get('name')}: {e}")
            logger.info(f"Loaded {len(cookies)} cookies")
        except Exception as e:
            logger.warning(f"Failed to load cookies: {e}")
    
    def get_cookies(self) -> List[Dict]:
        """Получить все cookies"""
        if not self.driver:
            return []
        try:
            return self.driver.get_cookies()
        except:
            return []
    
    def login_google(self, email: str, password: str) -> bool:
        """Логин в Google аккаунт"""
        if not self.driver:
            return False
        
        try:
            self.driver.get('https://accounts.google.com/signin')
            human_delay(2, 4)
            
            # Ввод email
            email_input = WebDriverWait(self.driver, 10).until(
                EC.presence_of_element_located((By.ID, 'identifierId'))
            )
            email_input.clear()
            for char in email:
                email_input.send_keys(char)
                time.sleep(random.uniform(0.05, 0.15))
            human_delay(1, 2)
            
            # Кнопка "Далее"
            next_btn = self.driver.find_element(By.ID, 'identifierNext')
            next_btn.click()
            human_delay(2, 4)
            
            # Ввод пароля
            password_input = WebDriverWait(self.driver, 10).until(
                EC.presence_of_element_located((By.NAME, 'password'))
            )
            password_input.clear()
            for char in password:
                password_input.send_keys(char)
                time.sleep(random.uniform(0.05, 0.15))
            human_delay(1, 2)
            
            # Кнопка "Далее"
            next_btn = self.driver.find_element(By.ID, 'passwordNext')
            next_btn.click()
            human_delay(3, 5)
            
            # Проверка успешного логина
            current_url = self.driver.current_url
            if 'accounts.google.com' not in current_url or 'challenge' not in current_url:
                logger.info("Login successful")
                return True
            else:
                logger.warning("Login may require additional verification")
                return False
                
        except TimeoutException:
            logger.error("Login timeout")
            return False
        except Exception as e:
            logger.error(f"Login error: {e}")
            return False
    
    def watch_shorts(self, url: str, watch_seconds: int) -> Tuple[bool, Optional[str]]:
        """Просмотр YouTube Shorts"""
        if not self.driver:
            return False, "Browser not started"
        
        try:
            # Открываем видео
            self.driver.get(url)
            human_delay(2, 4)
            
            # Ждем загрузки плеера
            try:
                WebDriverWait(self.driver, 15).until(
                    EC.presence_of_element_located((By.TAG_NAME, 'video'))
                )
            except TimeoutException:
                return False, "Video player not loaded"
            
            # Имитация просмотра
            video_element = self.driver.find_element(By.TAG_NAME, 'video')
            
            # Случайные движения мыши и скролл
            actions = ActionChains(self.driver)
            
            # Получаем размер окна
            window_size = self.driver.get_window_size()
            center_x = window_size['width'] // 2
            center_y = window_size['height'] // 2
            
            # Движения мыши во время просмотра
            elapsed = 0
            while elapsed < watch_seconds:
                # Случайное движение мыши
                if random.random() < 0.3:  # 30% шанс
                    target_x = center_x + random.randint(-100, 100)
                    target_y = center_y + random.randint(-50, 50)
                    points = bezier_mouse_move(center_x, center_y, target_x, target_y)
                    for x, y in points:
                        actions.move_by_offset(x - center_x, y - center_y)
                    actions.perform()
                    center_x, center_y = target_x, target_y
                
                # Случайный скролл
                if random.random() < 0.2:  # 20% шанс
                    scroll_amount = random.randint(-100, 100)
                    self.driver.execute_script(f"window.scrollBy(0, {scroll_amount});")
                
                # Пауза
                sleep_time = random.uniform(0.5, 2.0)
                time.sleep(sleep_time)
                elapsed += sleep_time
            
            return True, None
            
        except Exception as e:
            logger.error(f"Error watching shorts: {e}")
            return False, str(e)
    
    def like_video(self) -> bool:
        """Поставить лайк"""
        if not self.driver:
            return False
        
        try:
            like_button = WebDriverWait(self.driver, 5).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[aria-label*="like"], button[aria-label*="Like"]'))
            )
            like_button.click()
            human_delay(1, 2)
            return True
        except:
            return False
    
    def subscribe_channel(self) -> bool:
        """Подписаться на канал"""
        if not self.driver:
            return False
        
        try:
            subscribe_button = WebDriverWait(self.driver, 5).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[aria-label*="Subscribe"], button[aria-label*="subscribe"]'))
            )
            subscribe_button.click()
            human_delay(1, 2)
            return True
        except:
            return False
    
    def close(self):
        """Закрыть браузер"""
        if self.driver:
            try:
                self.driver.quit()
            except:
                pass
            self.driver = None
