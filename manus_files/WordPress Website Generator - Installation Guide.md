# WordPress Website Generator - Installation Guide

## Quick Start Installation

This guide will help you install and configure the WordPress Website Generator application on your server.

### Prerequisites

Before starting the installation, ensure your server meets the following requirements:

- **Operating System**: Ubuntu 20.04+ (recommended) or CentOS 8+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 8.0 or newer with required extensions
- **Database**: MySQL 8.0+ or PostgreSQL 12+
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: At least 10GB available disk space

### Step 1: Download and Extract Files

1. Download the application files to your server:
```bash
cd /var/www/html
sudo wget https://github.com/your-repo/wordpress-website-generator/archive/main.zip
sudo unzip main.zip
sudo mv wordpress-website-generator-main website-generator
```

2. Set proper file permissions:
```bash
sudo chown -R www-data:www-data website-generator/
sudo chmod -R 755 website-generator/
sudo chmod -R 644 website-generator/css/ website-generator/js/
```

### Step 2: Install PHP Dependencies

1. Install required PHP extensions:
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.1-curl php8.1-json php8.1-mbstring php8.1-mysql php8.1-xml php8.1-zip php8.1-gd

# CentOS/RHEL
sudo dnf install php-curl php-json php-mbstring php-mysqlnd php-xml php-zip php-gd
```

2. Install Composer (if not already installed):
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

3. Install PHP dependencies:
```bash
cd website-generator/
composer install --no-dev --optimize-autoloader
```

### Step 3: Database Setup

1. Create a new database:
```sql
CREATE DATABASE website_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wp_generator'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON website_generator.* TO 'wp_generator'@'localhost';
FLUSH PRIVILEGES;
```

2. Import the database schema:
```bash
mysql -u wp_generator -p website_generator < database_schema.sql
```

### Step 4: Configuration

1. Copy the configuration template:
```bash
cp php/config.php.example php/config.php
```

2. Edit the configuration file:
```bash
nano php/config.php
```

3. Update the following settings:
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'website_generator');
define('DB_USER', 'wp_generator');
define('DB_PASS', 'your_secure_password');

// API Keys
define('OPENAI_API_KEY', 'your_openai_api_key');
define('UNSPLASH_ACCESS_KEY', 'your_unsplash_key');
define('PEXELS_API_KEY', 'your_pexels_key');
define('PIXABAY_API_KEY', 'your_pixabay_key');

// Security
define('ENCRYPTION_KEY', 'your_32_character_encryption_key');
```

### Step 5: Web Server Configuration

#### Apache Configuration

1. Create a virtual host file:
```bash
sudo nano /etc/apache2/sites-available/website-generator.conf
```

2. Add the following configuration:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/website-generator
    
    <Directory /var/www/html/website-generator>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/website-generator_error.log
    CustomLog ${APACHE_LOG_DIR}/website-generator_access.log combined
</VirtualHost>
```

3. Enable the site and required modules:
```bash
sudo a2ensite website-generator.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Nginx Configuration

1. Create a server block:
```bash
sudo nano /etc/nginx/sites-available/website-generator
```

2. Add the following configuration:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/website-generator;
    index index.html index.php;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

3. Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/website-generator /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 6: SSL Certificate (Recommended)

1. Install Certbot:
```bash
sudo apt install certbot python3-certbot-apache  # For Apache
# OR
sudo apt install certbot python3-certbot-nginx   # For Nginx
```

2. Obtain SSL certificate:
```bash
sudo certbot --apache -d your-domain.com        # For Apache
# OR
sudo certbot --nginx -d your-domain.com         # For Nginx
```

### Step 7: Create Required Directories

```bash
sudo mkdir -p website-generator/logs
sudo mkdir -p website-generator/cache
sudo mkdir -p website-generator/uploads
sudo chown -R www-data:www-data website-generator/logs website-generator/cache website-generator/uploads
sudo chmod -R 755 website-generator/logs website-generator/cache website-generator/uploads
```

### Step 8: Test Installation

1. Access the application in your web browser:
```
https://your-domain.com
```

2. You should see the WordPress Website Generator homepage.

3. Test the API endpoints:
```bash
curl -X POST https://your-domain.com/php/process.php \
  -H "Content-Type: application/json" \
  -d '{"action":"test_wordpress","wp_url":"https://test-site.com","wp_username":"admin","wp_password":"test"}'
```

### Step 9: Set Up Cron Jobs (Optional)

Add the following cron jobs for maintenance:

```bash
sudo crontab -e
```

Add these lines:
```cron
# Clean up old sessions daily at 2 AM
0 2 * * * /usr/bin/php /var/www/html/website-generator/php/cleanup.php

# Rotate logs weekly
0 0 * * 0 /usr/bin/find /var/www/html/website-generator/logs -name "*.log" -mtime +7 -delete
```

### Troubleshooting

#### Common Issues

**Database Connection Error**
- Verify database credentials in config.php
- Ensure MySQL/PostgreSQL service is running
- Check firewall settings

**API Key Errors**
- Verify all API keys are correctly set in config.php
- Test API connectivity manually
- Check API usage limits

**File Permission Issues**
- Ensure www-data owns all application files
- Verify directory permissions (755) and file permissions (644)
- Check SELinux settings if applicable

**PHP Extension Missing**
- Install required PHP extensions
- Restart web server after installing extensions
- Verify extensions are loaded with `php -m`

#### Log Files

Check the following log files for troubleshooting:
- Application logs: `website-generator/logs/application.log`
- PHP errors: `website-generator/logs/php_errors.log`
- Web server logs: `/var/log/apache2/` or `/var/log/nginx/`

### Security Considerations

1. **File Permissions**: Ensure configuration files are not web-accessible
2. **API Keys**: Store API keys securely and rotate them regularly
3. **Database**: Use strong passwords and limit database user privileges
4. **Updates**: Keep PHP, web server, and dependencies updated
5. **Monitoring**: Set up log monitoring and intrusion detection

### Performance Optimization

1. **PHP OPcache**: Enable PHP OPcache for better performance
2. **Database**: Optimize database queries and add appropriate indexes
3. **Caching**: Configure Redis or Memcached for session storage
4. **CDN**: Use a CDN for static assets
5. **Compression**: Enable gzip compression in web server

### Backup Strategy

1. **Database Backups**: Schedule regular database backups
2. **File Backups**: Backup application files and user uploads
3. **Configuration**: Keep configuration files in version control
4. **Testing**: Regularly test backup restoration procedures

For additional support and documentation, visit: https://your-documentation-site.com

