# Forum Application

Laravel-based forum application with advanced security features and user management.
![DEMO](https://raw.githubusercontent.com/nezove/minfunforum/refs/heads/main/storage/app/public/demo-forum.png)
## Features

- User registration and authentication
- Topic and post management
- File uploads with security scanning
- hCaptcha integration for spam protection
- StopForumSpam API integration
- Time-limited post editing
- Customizable file upload restrictions
- SVG upload protection

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 16+ and NPM
- Web server (Apache/Nginx)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/your-repo-name.git
cd your-repo-name
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node.js dependencies

```bash
npm install
```

### 4. Environment configuration

```bash
cp .env.example .env
```

Edit `.env` file and configure the following:

#### Database settings
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

#### Application settings
```
APP_NAME=YourForumName
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

#### hCaptcha settings
Register at https://www.hcaptcha.com/ and get your keys:
```
HCAPTCHA_SITE_KEY=your_site_key
HCAPTCHA_SECRET_KEY=your_secret_key
```

#### StopForumSpam API (optional)
Register at https://www.stopforumspam.com/keys and get your API key:
```
STOPFORUMSPAM_API_KEY=your_api_key
STOPFORUMSPAM_ENABLED=true
```

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Run database migrations

```bash
php artisan migrate
```

### 7. Create storage symbolic link

```bash
php artisan storage:link
```

### 8. Set proper permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Build frontend assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

## Configuration Options

### File Upload Settings

Configure in `.env`:

```
# Allow file downloads (1 = everyone, 0 = registered users only)
ALLOW_FILE_DOWNLOAD=0

# Edit time limits (in minutes)
POST_EDIT_TIME_LIMIT=60       # 1 hour
TOPIC_EDIT_TIME_LIMIT=1440    # 24 hours

# File upload limits
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M
MAX_FILE_SIZE=7240             # in KB
MAX_IMAGE_PIXELS=16000

# Allowed file types
ALLOWED_FILE_EXTENSIONS="zip,rar,7z,txt,pdf,doc,docx,json,xml"
ALLOWED_IMAGE_FORMATS="jpg,png,gif"

# Security features
DISABLE_SVG_UPLOAD=true
ENABLE_FILE_SCANNING=true
FILE_UPLOAD_QUARANTINE_ENABLED=true
```

### Display Settings

```
# Show username instead of name
DISPLAY_USERNAME_INSTEAD_OF_NAME=true
```

## Running the Application

### Development server

```bash
php artisan serve
```

Visit http://localhost:8000

### Production deployment

For production, configure your web server (Apache/Nginx) to point to the `public` directory.

#### Nginx example:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your-app/public;

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

## Security Considerations

### Important Security Steps

1. **Never commit `.env` file** - It contains sensitive credentials
2. **Generate a new APP_KEY** after deployment using `php artisan key:generate`
3. **Use strong database passwords**
4. **Enable HTTPS** in production
5. **Keep dependencies updated**: Run `composer update` and `npm update` regularly
6. **Configure proper file permissions**
7. **Enable firewall** and restrict database access
8. **Regular backups** of database and uploaded files

### Before Going Live

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure SSL certificate
- [ ] Set up automated backups
- [ ] Configure email settings for notifications
- [ ] Test hCaptcha functionality
- [ ] Review and adjust file upload limits
- [ ] Set up logging and monitoring

## Troubleshooting

### Permission errors
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Database connection issues
Check your `.env` database credentials and ensure MySQL is running.

### Frontend build errors
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Cache issues
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## Support

For issues and questions, please create an issue in the GitHub repository.

## License

This project is open-sourced software licensed under the MIT license.
