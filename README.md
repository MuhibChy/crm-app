# CRM Application

A modern, secure CRM (Customer Relationship Management) application built with PHP, featuring email management, AI-powered suggestions, and Microsoft Graph integration.

## Features

- **Email Management**: IMAP/SMTP integration for multiple email accounts
- **AI-Powered Suggestions**: OpenAI integration for intelligent email responses
- **Microsoft Graph Integration**: OAuth2 integration with Microsoft 365
- **Google Sheets Integration**: Sync data with Google Sheets
- **Responsive Design**: Modern UI with TailwindCSS and Alpine.js
- **Security**: CSRF protection, input validation, and secure configuration management

## Security Features

- Environment-based configuration (no hardcoded credentials)
- CSRF token protection on all forms
- Input validation and sanitization
- Rate limiting for API calls
- Secure password hashing
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- PHP Extensions:
  - mysqli
  - curl
  - imap (for email functionality)
  - openssl
  - json
  - mbstring

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/crm-app.git
cd crm-app
```

### 2. Database Setup

1. Create a MySQL database named `crm_db`
2. Import the database schema:

```bash
mysql -u root -p crm_db < crm_db.sql
```

### 3. Environment Configuration

1. Copy the example environment file:

```bash
cp .env.example .env
```

2. Edit `.env` file with your configuration:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=crm_db

# Email Configuration
IMAP_HOST=your-imap-host.com
SMTP_HOST=your-smtp-host.com
EMAIL_USER=your-email@domain.com
EMAIL_PASS=your-email-password

# OpenAI Configuration
OPENAI_API_KEY=your-openai-api-key-here

# Google Sheets Configuration (Optional)
GOOGLE_SHEET_ID=your-google-sheet-id
GOOGLE_API_KEY=your-google-api-key

# Microsoft Graph Configuration (Optional)
GRAPH_CLIENT_ID=your-azure-client-id
GRAPH_CLIENT_SECRET=your-azure-client-secret
GRAPH_TENANT=common
GRAPH_REDIRECT_URI=https://yourdomain.com/crm-app/ms_callback.php

# Application Configuration
APP_URL=https://yourdomain.com/crm-app
APP_ENV=production
```

### 4. File Permissions

Ensure the application has write permissions for logs:

```bash
mkdir logs
chmod 755 logs
```

### 5. Web Server Configuration

#### Apache (.htaccess)

Create `.htaccess` file in the root directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Hide sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/crm-app;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security
    location ~ /\. {
        deny all;
    }

    location ~ \.(log|env)$ {
        deny all;
    }
}
```

## Configuration

### Email Accounts

1. Navigate to **Email Accounts** in the application
2. Add your email accounts with IMAP/SMTP settings
3. Test the connection to ensure proper configuration

### OpenAI Integration

1. Get an API key from [OpenAI](https://platform.openai.com/api-keys)
2. Add it to your `.env` file as `OPENAI_API_KEY`

### Microsoft Graph (Optional)

1. Register an application in [Azure Portal](https://portal.azure.com)
2. Configure OAuth2 redirect URI: `https://yourdomain.com/crm-app/ms_callback.php`
3. Add the client ID and secret to your `.env` file

### Google Sheets (Optional)

1. Create a project in [Google Cloud Console](https://console.cloud.google.com)
2. Enable the Google Sheets API
3. Create credentials and add to your `.env` file

## Usage

### Dashboard

Access the main dashboard at `/dashboard.php` to view:
- Total emails today
- Pending follow-ups
- Agent activity
- SLA countdown

### Email Management

- **Custom Email**: `/custom_email.php` - Manage emails from configured accounts
- **Email Accounts**: `/email_accounts.php` - Configure IMAP/SMTP accounts

### Admin Panel

Access admin functions at `/admin.php` for system configuration.

## Security Best Practices

1. **Never commit `.env` files** to version control
2. **Use HTTPS** in production environments
3. **Regularly update** PHP and dependencies
4. **Monitor logs** for security events in `logs/security.log`
5. **Use strong passwords** for database and email accounts
6. **Limit file permissions** appropriately
7. **Enable firewall** and restrict access to sensitive ports

## Development

### Local Development

1. Use XAMPP, WAMP, or similar local server
2. Set `APP_ENV=development` in `.env`
3. Enable error reporting for debugging

### Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Disable error display
3. Enable HTTPS
4. Configure proper file permissions
5. Set up regular backups

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `.env`
   - Ensure MySQL service is running
   - Verify database exists

2. **Email Connection Issues**
   - Verify IMAP/SMTP settings
   - Check firewall settings
   - Enable "Less secure app access" for Gmail (or use App Passwords)

3. **OpenAI API Errors**
   - Verify API key is correct
   - Check API usage limits
   - Ensure internet connectivity

4. **Permission Denied Errors**
   - Check file permissions
   - Ensure web server has write access to logs directory

### Logs

Check the following log files for debugging:
- `logs/security.log` - Security events
- Web server error logs
- PHP error logs

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue on GitHub
- Check the troubleshooting section
- Review the logs for error details

## Changelog

### Version 1.0.0
- Initial release
- Email management functionality
- AI-powered suggestions
- Microsoft Graph integration
- Security enhancements
- Environment-based configuration
