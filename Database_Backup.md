# 📦 Laravel Database Backup to Google Drive

Automate your MySQL database backups and securely upload them to **Google Drive** using a simple **Laravel API** with cron job integration.
This solution ensures your backups remain safe, off-server, and recoverable anytime.

---

## 🧠 Overview

**Problem → Solution**

| Problem                                  | Solution                                              |
| ---------------------------------------- | ----------------------------------------------------- |
| ❌ cPanel backups stay on the same server | ✅ Uploads to Google Drive (safe even if server fails) |
| ❌ Manual backups waste time              | ✅ Automated every 5 hours via cron job                |
| ❌ Backups consume local storage          | ✅ Automatically compressed & cleaned                  |
| ❌ Hard to access older backups           | ✅ Drive keeps versions organized & accessible         |

**Real-World Use Case**
If your platform (e.g., LMS or e-commerce) updates data frequently — this system automatically:

* Takes database backup every 5 hours
* Compresses and uploads to Google Drive
* Deletes local copies safely
* Lets you restore from any backup point

---

## ✨ Features

### Core

* 🔄 **Automated Backups** – via cron job every 5 hours
* ☁️ **Google Drive Integration** – secured with OAuth 2.0
* 🗈️ **Compression** – up to 90% smaller backups
* 🔐 **API Security** – access via `X-API-Key` header
* 📊 **Backup History API** – view stored backups
* 🧹 **Auto Cleanup** – removes old backups locally
* 🕝️ **Detailed Logs** – each action recorded

### Advanced

* Chunked uploads for large DBs (>100MB)
* Token auto-refresh
* Organized Google Drive folders
* Automatic retry on failures
* Multi-user Drive support

---

## 🎗️ Architecture

```
Cron Job (cPanel / PHP) → Laravel API (/api/backup/create)
→ Middleware (API Key Validation)
→ BackupController
   ├─ DatabaseBackupService (creates & compresses SQL)
   ├─ GoogleDriveService (uploads to Drive)
   └─ Cleanup + JSON Response
→ Log File + Google Drive Folder
```

---

## ⚙️ Requirements

| Component      | Version | Purpose             |
| -------------- | ------- | ------------------- |
| PHP            | 7.4+    | Laravel Runtime     |
| Laravel        | 5.8+    | Framework           |
| MySQL          | 5.7+    | Database            |
| Composer       | 2.x     | Dependency Manager  |
| cURL + OpenSSL | any     | Required Extensions |

Run to verify:

```bash
php -v
composer -V
mysql --version
php -m | grep -E 'curl|json|openssl|zip'
```

---

## 🚀 Installation

1. **Install Google Client Library**

   ```bash
   composer require google/apiclient:"^2.0"
   ```

2. **Setup Directories**

   ```bash
   mkdir -p storage/app/backups storage/logs storage/app/google
   chmod 755 storage/app/backups storage/app/google
   ```

3. **Add Required Service Files**

   * `app/Services/DatabaseBackupService.php`
   * `app/Services/GoogleDriveService.php`
   * `app/Http/Controllers/Api/BackupController.php`
   * `app/Http/Middleware/ApiKeyMiddleware.php`

4. **Register Middleware**
   Add to `app/Http/Kernel.php`

   ```php
   'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
   ```

5. **Define Routes** in `routes/api.php`

   ```php
   Route::prefix('backup')->middleware('api.key')->group(function () {
       Route::post('/create', [BackupController::class, 'createAndUpload']);
       Route::get('/history', [BackupController::class, 'history']);
   });
   ```

6. **Cache Config**

   ```bash
   php artisan config:clear && php artisan config:cache
   ```

---

## 🔑 Configuration

Update your `.env` file:

```dotenv
GOOGLE_CLIENT_ID=xxxxxxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxxxxxxxxx
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback
BACKUP_API_KEY=your-secure-random-api-key
```

Generate secure API key:

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

## 🔌 Connect Google Account

Create OAuth app in [Google Cloud Console](https://console.cloud.google.com):

1. Enable **Google Drive API**
2. Create OAuth Client ID (Web App)
3. Add redirect URI:

   ```
   https://yourdomain.com/google/callback
   ```
4. Save Client ID + Secret → put in `.env`

Then test connection:

```php
Route::get('/connect-google', function (GoogleClientService $googleClient) {
    return redirect($googleClient->getAuthUrl());
});
```

---

## 🧪 Usage

### 1️⃣ Manual Backup (API / cURL)

```bash
curl -X POST https://yourdomain.com/api/backup/create \
  -H "X-API-Key: your-secret-key" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"compress":true,"delete_local":true}'
```

**Expected Response**

```json
{
  "success": true,
  "message": "Backup completed and uploaded successfully",
  "data": {
    "backup_file": "backup_2025-11-13_14-30-45.sql.gz",
    "backup_size": "15.5 MB",
    "drive_link": "https://drive.google.com/file/d/xyz/view"
  }
}
```

### 2️⃣ Fetch Backup History

```bash
curl -X GET "https://yourdomain.com/api/backup/history?user_id=1" \
  -H "X-API-Key: your-secret-key"
```

---

## 🕐 Cron Job Setup

Run every 5 hours:

```bash
0 */5 * * * /usr/bin/php /home/username/backup-cron.php >> /home/username/backup-cron.log 2>&1
```

**backup-cron.php**

```php
$ch = curl_init('https://yourdomain.com/api/backup/create');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode(['user_id'=>1,'compress'=>true,'delete_local'=>true]),
  CURLOPT_HTTPHEADER => [
    'X-API-Key: your-secret-key',
    'Content-Type: application/json'
  ]
]);
echo curl_exec($ch);
curl_close($ch);
```

---

## 🥳 Troubleshooting

| Issue                 | Cause            | Solution                            |
| --------------------- | ---------------- | ----------------------------------- |
| `Unauthorized`        | Wrong API key    | Check `.env` and cron script key    |
| `No Google Drive`     | Token missing    | Reconnect via `/connect-google`     |
| `mysqldump not found` | Path issue       | Run `which mysqldump` & update path |
| `Permission denied`   | File permissions | `chmod +x backup-cron.php`          |
| `Memory exhausted`    | Large DB         | Use `mysqldump` + compression       |

---

## 🧠 Security Tips

* Keep `BACKUP_API_KEY` secret
* Limit API access by IP
* Store Drive tokens in database (not files)
* Use HTTPS for all API calls
* Restrict Google OAuth to trusted domains

---

## 📈 Performance Optimizations

* Enable gzip compression
* Run cron during low-traffic hours
* Keep max 7 local backups
* Monitor Drive storage periodically

---

## ❤️ Credits

Developed by **Altamash** & Contributors
Built on **Laravel 5.8 + PHP 7.4**
