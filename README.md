# Chat Application - Installation & Setup Guide

A modern real-time chat application built with Laravel backend and Flutter mobile app, featuring channels, direct messages, file sharing, and video calls.

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Quick Start](#quick-start)
- [Laravel Backend Setup](#laravel-backend-setup)
- [Flutter Mobile App Setup](#flutter-mobile-app-setup)
- [Database Configuration](#database-configuration)
- [WebSocket Configuration](#websocket-configuration)
- [Development Workflow](#development-workflow)
- [Production Deployment](#production-deployment)
- [Troubleshooting](#troubleshooting)
- [API Documentation](#api-documentation)

## 🔧 System Requirements

### For Laravel Backend
- **Ubuntu 20.04+** (or compatible Linux distribution)
- **PHP 8.1+** with extensions: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml, curl, gd, zip
- **Composer 2.x**
- **Node.js 18+** and **npm 8+**
- **MySQL 8.0+** or **PostgreSQL 13+**
- **Redis 6.0+** (for caching and queues)
- **Nginx** or **Apache**

### For Flutter Mobile App
- **Flutter SDK 3.16+**
- **Dart SDK 3.2+**
- **Android Studio** (for Android development)
- **Xcode** (for iOS development - macOS required)
- **Android SDK 21+** (Android 5.0+)
- **iOS 12.0+** (for iOS development)

## 🚀 Quick Start

### 1. Clone the Repository
```bash
# Clone the project
git clone <repository-url>
cd chat-app

# Verify project structure
ls -la
# Should show: backend web/, app mobile - flutter/, README.md
```

### 2. Backend Setup (5 minutes)
```bash
cd "backend web"
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### 3. Mobile App Setup (3 minutes)
```bash
cd "../app mobile - flutter/chat_app"
flutter pub get
flutter run
```

## 🖥️ Laravel Backend Setup

### Step 1: Install System Dependencies

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1 and required extensions
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-gd \
    php8.1-opcache php8.1-mbstring php8.1-tokenizer php8.1-json \
    php8.1-bcmath php8.1-zip php8.1-curl php8.1-sqlite3 php8.1-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
node --version && npm --version
```

### Step 2: Install and Configure MySQL

```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
-- In MySQL prompt
CREATE DATABASE chatapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'chatapp_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON chatapp.* TO 'chatapp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Install Redis

```bash
# Install Redis
sudo apt install -y redis-server

# Start and enable Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test Redis connection
redis-cli ping
# Should return: PONG
```

### Step 4: Configure Laravel Application

```bash
# Navigate to backend directory
cd "backend web"

# Install PHP dependencies
composer install

# Copy and configure environment file
cp .env.example .env
```

Edit the `.env` file:
```bash
nano .env
```

```env
# Application Configuration
APP_NAME="Chat Application"
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatapp
DB_USERNAME=chatapp_user
DB_PASSWORD=secure_password_here

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache and Session Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting Configuration (for WebSocket)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=mt1

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@chatapp.com"
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=local
```

### Step 5: Generate Application Key and Run Migrations

```bash
# Generate application key
php artisan key:generate

# Create symbolic link for storage
php artisan storage:link

# Run database migrations and seeders
php artisan migrate --seed

# Clear application cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 6: Install Frontend Dependencies (for Web Admin Panel)

```bash
# Install Node.js dependencies
npm install

# Build frontend assets
npm run build

# For development with hot reload
npm run dev
```

### Step 7: Start Laravel Development Server

```bash
# Start the Laravel development server
php artisan serve --host=0.0.0.0 --port=8000

# In another terminal, start the queue worker
php artisan queue:work

# In another terminal, start the WebSocket server (if using Laravel WebSockets)
php artisan websockets:serve
```

### Step 8: Verify Backend Installation

Open your browser and visit:
- **API Health Check**: http://localhost:8000/api/health
- **Admin Panel**: http://localhost:8000/admin
- **API Documentation**: http://localhost:8000/api/documentation

Default admin credentials:
- **Email**: admin@chatapp.com
- **Password**: password

## 📱 Flutter Mobile App Setup

### Step 1: Install Flutter SDK

```bash
# Download Flutter SDK
cd ~/
wget https://storage.googleapis.com/flutter_infra_release/releases/stable/linux/flutter_linux_3.16.0-stable.tar.xz

# Extract Flutter
tar xf flutter_linux_3.16.0-stable.tar.xz

# Add Flutter to PATH
echo 'export PATH="$HOME/flutter/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc

# Verify Flutter installation
flutter --version
flutter doctor
```

### Step 2: Install Android Studio (for Android Development)

```bash
# Download and install Android Studio
sudo snap install android-studio --classic

# Accept Android licenses
flutter doctor --android-licenses
```

### Step 3: Configure Flutter Project

```bash
# Navigate to Flutter app directory
cd "app mobile - flutter/chat_app"

# Get Flutter dependencies
flutter pub get

# Verify project configuration
flutter doctor
flutter analyze
```

### Step 4: Configure API Endpoints

Edit `lib/core/constants/api_constants.dart`:

```dart
class ApiConstants {
  // Update this to your backend URL
  static const String baseUrl = 'http://YOUR_SERVER_IP:8000';
  static const String apiUrl = '$baseUrl/api';
  static const String socketUrl = '$baseUrl';

  // Rest of the configuration...
}
```

For local development:
```dart
static const String baseUrl = 'http://10.0.2.2:8000'; // Android Emulator
// OR
static const String baseUrl = 'http://localhost:8000'; // iOS Simulator
```

### Step 5: Run Flutter Application

```bash
# Check connected devices
flutter devices

# Run on connected device/emulator
flutter run

# Run in debug mode with hot reload
flutter run --debug

# Run in release mode
flutter run --release

# Build APK for Android
flutter build apk --release

# Build for iOS (macOS only)
flutter build ios --release
```

## 🗄️ Database Configuration

### MySQL Configuration

```bash
# Edit MySQL configuration for better performance
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add/modify these settings:
```ini
[mysqld]
# Performance Settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Binary Logging (for replication)
log-bin = mysql-bin
binlog_format = ROW
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### Database Backup and Restore

```bash
# Create backup
mysqldump -u chatapp_user -p chatapp > chatapp_backup.sql

# Restore backup
mysql -u chatapp_user -p chatapp < chatapp_backup.sql
```

## 🔌 WebSocket Configuration

### Option 1: Using Pusher (Recommended for Production)

1. **Create Pusher Account**: Visit [pusher.com](https://pusher.com) and create an account
2. **Create New App**: Get your App ID, Key, Secret, and Cluster
3. **Update .env file**:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
```

### Option 2: Using Laravel WebSockets (Self-hosted)

```bash
# Install Laravel WebSockets
composer require beyondcode/laravel-websockets

# Publish configuration
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate

# Update .env
echo "PUSHER_APP_ID=local" >> .env
echo "PUSHER_APP_KEY=local" >> .env
echo "PUSHER_APP_SECRET=local" >> .env
echo "PUSHER_APP_CLUSTER=mt1" >> .env

# Start WebSocket server
php artisan websockets:serve
```

## 🛠️ Development Workflow

### Backend Development

```bash
# Start all services for development
cd "backend web"

# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work --verbose

# Terminal 3: Asset compilation
npm run dev

# Terminal 4: WebSocket server (if using Laravel WebSockets)
php artisan websockets:serve
```

### Mobile Development

```bash
# Start Flutter development
cd "app mobile - flutter/chat_app"

# Run with hot reload
flutter run --hot

# Run tests
flutter test

# Generate code coverage
flutter test --coverage

# Analyze code quality
flutter analyze
```

### Useful Development Commands

```bash
# Laravel Commands
php artisan tinker                    # Interactive shell
php artisan route:list               # List all routes
php artisan make:controller MyController  # Create controller
php artisan make:model MyModel -m   # Create model with migration
php artisan migrate:fresh --seed    # Fresh migration with seeders
php artisan queue:failed            # Show failed jobs
php artisan queue:retry all         # Retry failed jobs

# Flutter Commands
flutter clean                       # Clean build cache
flutter pub upgrade                 # Upgrade dependencies
flutter build apk --split-per-abi  # Build optimized APKs
flutter install                     # Install to connected device
flutter logs                        # View device logs
```

## 🚀 Production Deployment

### Backend Production Setup

#### 1. Configure Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Create site configuration
sudo nano /etc/nginx/sites-available/chatapp
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/chatapp/backend web/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/chatapp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 2. Configure SSL with Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com

# Test renewal
sudo certbot renew --dry-run
```

#### 3. Configure Production Environment

```bash
# Update .env for production
cp .env .env.backup
nano .env
```

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use production database
DB_HOST=your-production-db-host
DB_DATABASE=chatapp_production
DB_USERNAME=chatapp_production_user
DB_PASSWORD=very_secure_password

# Configure production cache and sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

```bash
# Optimize for production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run production
```

#### 4. Setup Process Manager

```bash
# Install Supervisor
sudo apt install -y supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/chatapp-worker.conf
```

```ini
[program:chatapp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/chatapp/backend web/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/chatapp/backend web/storage/logs/worker.log
```

```bash
# Update and start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chatapp-worker:*
```

### Mobile App Production Build

#### Android Production Build

```bash
cd "app mobile - flutter/chat_app"

# Create keystore for app signing
keytool -genkey -v -keystore ~/key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias key

# Create key.properties file
nano android/key.properties
```

```properties
storePassword=your_keystore_password
keyPassword=your_key_password
keyAlias=key
storeFile=/home/your_username/key.jks
```

```bash
# Update android/app/build.gradle to use signing config
# Build release APK
flutter build apk --release

# Build App Bundle for Google Play
flutter build appbundle --release
```

#### iOS Production Build (macOS only)

```bash
# Build for iOS
flutter build ios --release

# Open in Xcode for App Store submission
open ios/Runner.xcworkspace
```

## 🐛 Troubleshooting

### Common Backend Issues

#### 1. Permission Issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
```

#### 2. Database Connection Issues
```bash
# Test database connection
php artisan tinker
# In tinker: DB::connection()->getPdo();

# Check MySQL status
sudo systemctl status mysql

# View MySQL logs
sudo tail -f /var/log/mysql/error.log
```

#### 3. Queue Worker Issues
```bash
# Check failed jobs
php artisan queue:failed

# Clear failed jobs
php artisan queue:flush

# Restart queue workers
sudo supervisorctl restart chatapp-worker:*
```

### Common Flutter Issues

#### 1. API Connection Issues
```bash
# Check network connectivity from device
adb shell ping your-server-ip

# View Flutter logs
flutter logs

# Debug network requests
flutter run --verbose
```

#### 2. Build Issues
```bash
# Clean build cache
flutter clean
flutter pub get

# Clear Android build cache
cd android && ./gradlew clean && cd ..

# Restart Android Studio and rebuild
```

#### 3. Device/Emulator Issues
```bash
# List available devices
flutter devices

# Restart ADB
adb kill-server
adb start-server

# Cold boot Android emulator
emulator -avd Your_AVD_Name -cold-boot
```

### Performance Optimization

#### Backend Optimization
```bash
# Enable OPcache
sudo nano /etc/php/8.1/fpm/php.ini
# Uncomment and configure:
# opcache.enable=1
# opcache.memory_consumption=128
# opcache.max_accelerated_files=4000

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

#### Database Optimization
```sql
-- Optimize tables
OPTIMIZE TABLE users, messages, channels, organizations;

-- Add indexes for better performance
CREATE INDEX idx_messages_channel_created ON messages(channel_id, created_at);
CREATE INDEX idx_users_organization ON users(organization_id);
```

## 📚 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | User login |
| POST | `/api/auth/register` | User registration |
| POST | `/api/auth/logout` | User logout |
| POST | `/api/auth/forgot-password` | Request password reset |

### Channel Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/channels` | List user channels |
| POST | `/api/channels` | Create new channel |
| GET | `/api/channels/{id}` | Get channel details |
| PATCH | `/api/channels/{id}` | Update channel |
| DELETE | `/api/channels/{id}` | Delete channel |

### Message Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/channels/{id}/messages` | Get channel messages |
| POST | `/api/channels/{id}/messages` | Send message |
| PATCH | `/api/messages/{id}` | Edit message |
| DELETE | `/api/messages/{id}` | Delete message |

### File Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/attachments` | Upload file |
| GET | `/api/attachments/{id}/download` | Download file |
| DELETE | `/api/attachments/{id}` | Delete file |

For complete API documentation, visit: `http://your-domain.com/api/documentation`

## 🔧 Configuration Files

### Important Laravel Configuration Files

- **`.env`** - Environment variables
- **`config/app.php`** - Application configuration
- **`config/database.php`** - Database connections
- **`config/broadcasting.php`** - WebSocket configuration
- **`config/queue.php`** - Background job configuration

### Important Flutter Configuration Files

- **`pubspec.yaml`** - Dependencies and assets
- **`lib/core/constants/api_constants.dart`** - API endpoints
- **`android/app/build.gradle`** - Android build configuration
- **`ios/Runner/Info.plist`** - iOS configuration

## 📞 Support

For issues and support:

1. **Check the troubleshooting section** above
2. **Review application logs** in `storage/logs/laravel.log`
3. **Check Flutter logs** with `flutter logs`
4. **Create an issue** in the project repository
5. **Contact the development team**

## 📄 License

This project is licensed under the MIT License. See the LICENSE file for details.

---

**Quick Commands Reference:**

```bash
# Start backend development
cd "backend web" && php artisan serve

# Start mobile development
cd "app mobile - flutter/chat_app" && flutter run

# Run tests
php artisan test && flutter test

# Deploy to production
git pull && composer install --no-dev && php artisan migrate && npm run production
```

Happy coding! 🚀