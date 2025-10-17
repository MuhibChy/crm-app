# Deployment Guide

This guide covers deploying the CRM application to various hosting environments.

## Pre-Deployment Checklist

### Security Checklist
- [ ] All sensitive credentials moved to `.env` file
- [ ] `.env` file added to `.gitignore`
- [ ] Database passwords are strong
- [ ] OpenAI API key is valid and secured
- [ ] HTTPS is configured (for production)
- [ ] File permissions are properly set
- [ ] Security headers are configured

### Configuration Checklist
- [ ] Database connection tested
- [ ] Email accounts configured and tested
- [ ] AI functionality tested (if using OpenAI)
- [ ] All required PHP extensions installed
- [ ] Error logging configured
- [ ] Backup strategy in place

## Deployment Options

### 1. Shared Hosting (cPanel/Plesk)

#### Steps:
1. **Upload Files**
   ```bash
   # Upload all files except .env to public_html or equivalent
   # Do not upload: .env, .git/, logs/, *.log files
   ```

2. **Create Database**
   - Create MySQL database via hosting control panel
   - Import `crm_db.sql` via phpMyAdmin or similar

3. **Configure Environment**
   - Create `.env` file on server with production values
   - Set file permissions: `.env` should be 600 (read/write owner only)

4. **Set Permissions**
   ```bash
   chmod 755 /path/to/crm-app
   chmod 644 /path/to/crm-app/*.php
   chmod 600 /path/to/crm-app/.env
   mkdir logs && chmod 755 logs
   ```

### 2. VPS/Dedicated Server (Ubuntu/CentOS)

#### Prerequisites:
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-imap php8.0-mbstring php8.0-xml -y

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

#### Deployment Steps:

1. **Clone Repository**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/yourusername/crm-app.git
   sudo chown -R www-data:www-data crm-app
   ```

2. **Database Setup**
   ```bash
   sudo mysql -u root -p
   CREATE DATABASE crm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'strong_password_here';
   GRANT ALL PRIVILEGES ON crm_db.* TO 'crm_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   
   # Import schema
   mysql -u crm_user -p crm_db < /var/www/html/crm-app/crm_db.sql
   ```

3. **Environment Configuration**
   ```bash
   cd /var/www/html/crm-app
   sudo cp .env.example .env
   sudo nano .env  # Edit with your values
   sudo chmod 600 .env
   sudo chown www-data:www-data .env
   ```

4. **Apache Virtual Host**
   ```bash
   sudo nano /etc/apache2/sites-available/crm-app.conf
   ```
   
   Add:
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/html/crm-app
       
       <Directory /var/www/html/crm-app>
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/crm-app_error.log
       CustomLog ${APACHE_LOG_DIR}/crm-app_access.log combined
   </VirtualHost>
   ```
   
   Enable site:
   ```bash
   sudo a2ensite crm-app.conf
   sudo systemctl reload apache2
   ```

### 3. Docker Deployment

#### Dockerfile:
```dockerfile
FROM php:8.0-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y \
    libc-client-dev libkrb5-dev \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy application
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
```

#### docker-compose.yml:
```yaml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./logs:/var/www/html/logs
    environment:
      - DB_HOST=db
      - DB_USER=crm_user
      - DB_PASS=password
      - DB_NAME=crm_db
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: crm_db
      MYSQL_USER: crm_user
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./crm_db.sql:/docker-entrypoint-initdb.d/init.sql

volumes:
  mysql_data:
```

Deploy:
```bash
docker-compose up -d
```

## SSL/HTTPS Configuration

### Let's Encrypt (Certbot)
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

### Manual SSL Certificate
Update Apache virtual host:
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/crm-app
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

## Environment Variables

### Production .env Example:
```env
# Database
DB_HOST=localhost
DB_USER=crm_user
DB_PASS=your_secure_password
DB_NAME=crm_db

# Email (example for Gmail)
IMAP_HOST=imap.gmail.com
SMTP_HOST=smtp.gmail.com
EMAIL_USER=your-email@gmail.com
EMAIL_PASS=your-app-password

# OpenAI
OPENAI_API_KEY=sk-your-actual-api-key

# Application
APP_URL=https://yourdomain.com/crm-app
APP_ENV=production

# Security (optional)
ALLOWED_IPS=192.168.1.100,203.0.113.0
```

## Monitoring and Maintenance

### Log Monitoring
```bash
# Monitor application logs
tail -f /var/www/html/crm-app/logs/security.log

# Monitor Apache logs
tail -f /var/log/apache2/crm-app_error.log
```

### Database Backup
```bash
# Create backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u crm_user -p crm_db > /backups/crm_db_$DATE.sql
find /backups -name "crm_db_*.sql" -mtime +7 -delete
```

### Security Updates
```bash
# Regular system updates
sudo apt update && sudo apt upgrade -y

# Monitor security logs
grep "security" /var/www/html/crm-app/logs/security.log
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `.env`
   - Verify database server is running
   - Test connection: `mysql -u crm_user -p crm_db`

2. **Email Connection Issues**
   - Verify IMAP/SMTP settings
   - Check firewall rules (ports 993, 587, 465)
   - For Gmail: Use App Passwords, not regular password

3. **Permission Denied Errors**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/crm-app
   sudo chmod -R 755 /var/www/html/crm-app
   sudo chmod 600 /var/www/html/crm-app/.env
   ```

4. **OpenAI API Errors**
   - Verify API key is correct
   - Check API usage limits
   - Ensure curl extension is installed

5. **CSRF Token Errors**
   - Check session configuration
   - Verify session directory is writable
   - Clear browser cache/cookies

### Performance Optimization

1. **Enable PHP OPcache**
   ```bash
   sudo nano /etc/php/8.0/apache2/php.ini
   # Add:
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

2. **Database Optimization**
   ```sql
   -- Add indexes for better performance
   CREATE INDEX idx_emails_created_status ON emails(created_at, status);
   CREATE INDEX idx_tasks_due_status ON tasks(due_date, status);
   ```

3. **Apache Optimization**
   ```apache
   # Enable compression
   LoadModule deflate_module modules/mod_deflate.so
   
   # Enable caching
   LoadModule expires_module modules/mod_expires.so
   ExpiresActive On
   ExpiresByType text/css "access plus 1 month"
   ExpiresByType application/javascript "access plus 1 month"
   ```

## Security Hardening

### Server Level
```bash
# Disable unnecessary services
sudo systemctl disable apache2-doc
sudo systemctl disable apache2-utils

# Configure firewall
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Secure MySQL
sudo mysql_secure_installation
```

### Application Level
- Regularly update dependencies
- Monitor security logs
- Use strong passwords
- Enable 2FA where possible
- Regular security audits

## Backup Strategy

### Automated Backup Script
```bash
#!/bin/bash
BACKUP_DIR="/backups/crm-app"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u crm_user -p crm_db > $BACKUP_DIR/db_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/crm-app --exclude=logs

# Cleanup old backups (keep 30 days)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

Add to crontab:
```bash
# Daily backup at 2 AM
0 2 * * * /path/to/backup-script.sh
```
