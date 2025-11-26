# 🚀 Online Notes Sharing Platform - Deployment Guide

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Prerequisites](#prerequisites)
3. [Installation Steps](#installation-steps)
4. [Security Configuration](#security-configuration)
5. [Database Setup](#database-setup)
6. [File Permissions](#file-permissions)
7. [Testing & Verification](#testing--verification)
8. [Monitoring & Maintenance](#monitoring--maintenance)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 System Overview

The Online Notes Sharing Platform is a comprehensive web application for uploading, sharing, and managing educational notes. It features user authentication, file management, search functionality, and administrative controls.

### Key Features:
- ✅ User registration and authentication
- ✅ Secure file upload and sharing
- ✅ Advanced search and filtering
- ✅ Admin dashboard and user management
- ✅ Like/favorite system
- ✅ Mobile-responsive design
- ✅ Security-hardened architecture

---

## 📋 Prerequisites

### Server Requirements:
- **PHP 7.4+** (8.0+ recommended)
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Apache 2.4+** with **mod_rewrite**
- **SSL Certificate** (for production)

### PHP Extensions Required:
```ini
extension=mysqli
extension=fileinfo
extension=gd
extension=curl
extension=openssl
extension=mbstring
extension=xml
```

### System Requirements:
- **Disk Space:** 1GB+ (for uploads)
- **Memory:** 512MB+ PHP memory limit
- **Upload Max:** 10MB+ file upload limit

---

## 🛠️ Installation Steps

### Step 1: Download and Extract
```bash
# Clone or download the platform files
# Extract to your web directory (e.g., /var/www/html/notes-platform)
```

### Step 2: Database Configuration
```bash
# Create database
mysql -u root -p
CREATE DATABASE notes_sharing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'notes_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON notes_sharing.* TO 'notes_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Environment Setup
```bash
# Copy environment template
cp .env.example .env

# Edit .env with your configuration
nano .env
```

### Step 4: Run Database Setup
```bash
# Access via web browser
http://your-domain.com/complete_database_setup.php

# Or run via command line
php complete_database_setup.php
```

### Step 5: Apply Security Fixes
```bash
# Run security implementation
http://your-domain.com/apply_security_fixes.php
```

### Step 6: File Permissions
```bash
# Set proper permissions
chmod 755 -R .
chmod 777 -R uploads/
chmod 644 -R *.php
chmod 600 .env
chmod 644 config/db.php
```

---

## 🔒 Security Configuration

### 1. Environment Variables (.env)
```bash
# Database Configuration
DB_HOST=localhost
DB_USER=notes_user
DB_PASS=secure_password
DB_NAME=notes_sharing

# Security Settings
ENVIRONMENT=production
SESSION_LIFETIME=3600
UPLOAD_MAX_SIZE=5242880
ALLOWED_FILE_TYPES=pdf,doc,docx,txt,ppt,pptx
```

### 2. Apache Configuration (.htaccess)
```apache
# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# URL Rewriting
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### 3. PHP Configuration (php.ini)
```ini
# Security Settings
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# File Upload Settings
file_uploads = On
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20

# Session Security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Strict
```

---

## 🗄️ Database Setup

### Automatic Setup (Recommended)
1. Access `complete_database_setup.php` via web browser
2. Follow the on-screen instructions
3. Verify all tables are created successfully

### Manual Setup
```sql
-- Import the database schema
mysql -u notes_user -p notes_sharing < sql/notes_platform.sql

-- Run additional setup scripts
mysql -u notes_user -p notes_sharing < add_missing_columns.php
```

### Database Tables Overview:
- `users` - User accounts and profiles
- `notes` - Note metadata and information
- `categories` - Note categories and subcategories
- `likes` - Note likes system
- `favorites` - User favorites
- `admin_users` - Administrator accounts
- `activity_logs` - System activity tracking

---

## 📁 File Permissions

### Critical Directories:
```bash
# Upload directory (writable by web server)
chmod 777 -R uploads/
chmod 644 uploads/.htaccess

# Configuration files (read-only)
chmod 600 .env
chmod 644 config/db.php

# Application files (read-only)
chmod 644 -R *.php
chmod 644 -R includes/
chmod 644 -R admin/
chmod 644 -R api/
```

### Ownership:
```bash
# Set web server as owner (Ubuntu/Debian)
chown -R www-data:www-data .

# Set web server as owner (CentOS/RHEL)
chown -R apache:apache .
```

---

## 🧪 Testing & Verification

### 1. System Verification
```bash
# Run system health check
http://your-domain.com/system_verification.php
```

### 2. Navigation Testing
```bash
# Test responsive navigation
http://your-domain.com/navigation_test.php
```

### 3. Core Functionality Tests
- [ ] User registration and login
- [ ] File upload and download
- [ ] Search functionality
- [ ] Dashboard statistics
- [ ] Admin panel access
- [ ] Mobile responsiveness

### 4. Security Tests
```bash
# Test security headers
curl -I http://your-domain.com

# Check for directory listing
curl http://your-domain.com/uploads/

# Verify file upload security
# Try uploading malicious files (should be blocked)
```

---

## 📊 Monitoring & Maintenance

### 1. Log Monitoring
```bash
# Application logs
tail -f logs/application.log

# Error logs
tail -f logs/errors.log

# Apache/Nginx logs
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log
```

### 2. Database Maintenance
```sql
-- Optimize tables monthly
OPTIMIZE TABLE users, notes, categories, likes, favorites;

-- Clean old activity logs (keep 6 months)
DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### 3. File Cleanup
```bash
# Remove orphaned files (run weekly)
find uploads/ -name "*.pdf" -mtime +30 -type f -exec ls -la {} \;

# Clean temporary files
find /tmp -name "php*" -mtime +1 -delete
```

### 4. Backup Strategy
```bash
# Database backup (daily)
mysqldump -u notes_user -p notes_sharing > backup_$(date +%Y%m%d).sql

# Files backup (weekly)
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

---

## 🚨 Troubleshooting

### Common Issues:

#### 1. Database Connection Errors
```bash
# Check database credentials
grep DB_ .env

# Test database connection
mysql -u notes_user -p -h localhost notes_sharing

# Check MySQL service
systemctl status mysql
```

#### 2. File Upload Issues
```bash
# Check upload directory permissions
ls -la uploads/

# Check PHP upload settings
php -i | grep upload

# Check disk space
df -h
```

#### 3. Session Issues
```bash
# Check session save path
php -i | grep session.save_path

# Clear session cache
rm -rf /var/lib/php/sessions/*
```

#### 4. Permission Errors
```bash
# Fix ownership
chown -R www-data:www-data .

# Fix permissions
chmod 755 -R .
chmod 777 -R uploads/
```

### Error Codes Reference:
- **403:** Permission denied (check file permissions)
- **404:** File not found (check URL rewriting)
- **500:** Server error (check PHP logs)
- **503:** Service unavailable (check database connection)

---

## 🔄 Updates & Maintenance

### Regular Maintenance Schedule:
- **Daily:** Monitor logs, check disk space
- **Weekly:** Review user activity, clean temp files
- **Monthly:** Database optimization, security updates
- **Quarterly:** Full system backup, security audit

### Update Process:
1. Backup current system
2. Test updates in staging
3. Apply updates to production
4. Verify functionality
5. Monitor for issues

---

## 📞 Support & Resources

### Documentation:
- [API Documentation](api/README.md)
- [Database Schema](sql/notes_platform.sql)
- [Security Guidelines](SECURITY.md)

### Emergency Contacts:
- System Administrator: [admin@yourdomain.com]
- Database Administrator: [dba@yourdomain.com]
- Security Team: [security@yourdomain.com]

### Useful Commands:
```bash
# Restart web server
systemctl restart apache2

# Clear PHP cache
systemctl restart php-fpm

# Check system status
systemctl status mysql apache2 php-fpm
```

---

## 📈 Performance Optimization

### Database Optimization:
```sql
-- Add indexes for better performance
CREATE INDEX idx_notes_user_id ON notes(user_id);
CREATE INDEX idx_notes_category_id ON notes(category_id);
CREATE INDEX idx_notes_created_at ON notes(created_at);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
```

### Caching Configuration:
```php
// Enable OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

### CDN Integration:
```html
<!-- Use CDN for static assets -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 🔐 Security Best Practices

### Regular Security Tasks:
1. **Update dependencies** monthly
2. **Scan for vulnerabilities** quarterly
3. **Review access logs** weekly
4. **Test backups** monthly
5. **Audit user accounts** quarterly

### Security Monitoring:
```bash
# Monitor failed logins
grep "Failed login" /var/log/auth.log

# Monitor suspicious activity
grep "SQL injection" /var/log/apache2/error.log

# Check file integrity
find . -type f -name "*.php" -exec md5sum {} \; > checksums.txt
```

---

## 📝 Version History

- **v1.0.0** - Initial platform release
- **v1.1.0** - Added security enhancements
- **v1.2.0** - Mobile responsiveness improvements
- **v1.3.0** - Advanced search functionality
- **v1.4.0** - Admin dashboard enhancements

---

## 🎯 Deployment Checklist

### Pre-Deployment:
- [ ] All security fixes applied
- [ ] Database schema updated
- [ ] File permissions set correctly
- [ ] Environment variables configured
- [ ] SSL certificate installed
- [ ] Backup strategy implemented

### Post-Deployment:
- [ ] System verification passed
- [ ] Core functionality tested
- [ ] Security headers verified
- [ ] Monitoring configured
- [ ] Documentation updated
- [ ] Team training completed

---

**🚀 Your Online Notes Sharing Platform is now ready for production deployment!**

For technical support or questions, refer to the troubleshooting section or contact your system administrator.