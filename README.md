# Минималистичный функциональный форум

Форум на базе Laravel с расширенными функциями безопасности и управления пользователями.
![DEMO](https://i.ibb.co/WWkbqVhy/localhost-8888.png)

## Возможности

- Регистрация и аутентификация пользователей
- Управление темами и сообщениями
- Загрузка файлов со сканированием безопасности
- Интеграция hCaptcha для защиты от спама
- Интеграция API StopForumSpam
- Ограниченное по времени редактирование сообщений
- Настраиваемые ограничения загрузки файлов
- Защита от загрузки SVG

## Требования

- PHP 8.1 или выше
- Composer
- MySQL 5.7+ или MariaDB 10.3+
- Node.js 16+ и NPM
- Веб-сервер (Apache/Nginx)

## Установка

### 1. Клонируйте репозиторий
```bash
git clone https://github.com/yourusername/your-repo-name.git
cd your-repo-name
```

### 2. Установите Composer и PHP зависимости
```bash
# Установите Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Запустите установку зависимостей
php composer.phar install
```

### 3. Установите Node.js зависимости
```bash
npm install
```

### 4. Настройка окружения
```bash
cp .env.example .env
```

Отредактируйте файл `.env` и настройте следующее:

#### Настройки базы данных
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=имя_вашей_базы_данных
DB_USERNAME=пользователь_базы_данных
DB_PASSWORD=пароль_базы_данных
```

#### Настройки приложения
```
APP_NAME=НазваниеВашегоФорума
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ваш-домен.com
```

#### Настройки hCaptcha
Зарегистрируйтесь на https://www.hcaptcha.com/ и получите ключи:
```
HCAPTCHA_SITE_KEY=ваш_ключ_сайта
HCAPTCHA_SECRET_KEY=ваш_секретный_ключ
```

#### API StopForumSpam (опционально)
Зарегистрируйтесь на https://www.stopforumspam.com/keys и получите API ключ:
```
STOPFORUMSPAM_API_KEY=ваш_api_ключ
STOPFORUMSPAM_ENABLED=true
```

### 5. Сгенерируйте ключ приложения
```bash
php artisan key:generate
```

### 6. Выполните миграции базы данных
```bash
php artisan migrate
```

### 7. Создайте символическую ссылку для хранилища
```bash
php artisan storage:link
```

### 8. Установите правильные права доступа
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Соберите фронтенд ресурсы

Для разработки:
```bash
npm run dev
```

Для продакшена:
```bash
npm run build
```

## Параметры конфигурации

### Настройки загрузки файлов

Настройте в `.env`:
```
# Разрешить скачивание файлов (1 = всем, 0 = только зарегистрированным)
ALLOW_FILE_DOWNLOAD=0

# Лимиты времени редактирования (в минутах)
POST_EDIT_TIME_LIMIT=60       # 1 час
TOPIC_EDIT_TIME_LIMIT=1440    # 24 часа

# Лимиты загрузки файлов
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M
MAX_FILE_SIZE=7240             # в КБ
MAX_IMAGE_PIXELS=16000

# Разрешенные типы файлов
ALLOWED_FILE_EXTENSIONS="zip,rar,7z,txt,pdf,doc,docx,json,xml"
ALLOWED_IMAGE_FORMATS="jpg,png,gif"

# Функции безопасности
DISABLE_SVG_UPLOAD=true
ENABLE_FILE_SCANNING=true
FILE_UPLOAD_QUARANTINE_ENABLED=true
```

### Настройки отображения
```
# Показывать имя пользователя вместо имени
DISPLAY_USERNAME_INSTEAD_OF_NAME=true
```

## Запуск приложения

### Сервер разработки
```bash
php artisan serve
```

Перейдите по адресу http://localhost:8000

### Развертывание на продакшене

Для продакшена настройте ваш веб-сервер (Apache/Nginx) так, чтобы он указывал на директорию `public`.

#### Пример для Nginx:
```nginx
server {
    listen 80;
    server_name ваш-домен.com;
    root /путь/к/вашему-приложению/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Вопросы безопасности

### Важные шаги по безопасности

1. **Никогда не коммитьте файл `.env`** - Он содержит конфиденциальные данные
2. **Сгенерируйте новый APP_KEY** после развертывания, используя `php artisan key:generate`
3. **Используйте сложные пароли базы данных**
4. **Включите HTTPS** на продакшене
5. **Обновляйте зависимости**: Регулярно выполняйте `composer update` и `npm update`
6. **Настройте правильные права доступа к файлам**
7. **Включите файрвол** и ограничьте доступ к базе данных
8. **Регулярные резервные копии** базы данных и загруженных файлов

### Перед запуском в продакшен

- [ ] Установите `APP_ENV=production`
- [ ] Установите `APP_DEBUG=false`
- [ ] Настройте SSL сертификат
- [ ] Настройте автоматические резервные копии
- [ ] Настройте параметры email для уведомлений
- [ ] Протестируйте функциональность hCaptcha
- [ ] Проверьте и настройте лимиты загрузки файлов
- [ ] Настройте логирование и мониторинг

## Решение проблем

### Ошибки прав доступа
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Проблемы с подключением к базе данных
Проверьте учетные данные базы данных в `.env` и убедитесь, что MySQL запущен.

### Ошибки сборки фронтенда
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Проблемы с кешем
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## Поддержка

По вопросам и проблемам, пожалуйста, создайте issue в GitHub репозитории.

## Лицензия

Этот проект является программным обеспечением с открытым исходным кодом, лицензированным по лицензии MIT.
