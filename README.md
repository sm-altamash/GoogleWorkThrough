# 🚀 WorkspaceBridge 

> A powerful **Laravel-based Google Workspace Integration Platform** that connects Google Calendar, Drive, Classroom, Forms, Gmail, YouTube, and more — all in one unified admin panel.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)](https://php.net)
[![Google APIs](https://img.shields.io/badge/Google%20APIs-Workspace-green?logo=google)](https://developers.google.com)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

---

## 📋 Table of Contents

- [About the Project](#about-the-project)
- [Features](#features)
- [Architecture Overview](#architecture-overview)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Google API Setup](#google-api-setup)
- [Configuration](#configuration)
- [Modules](#modules)
- [API Endpoints](#api-endpoints)
- [Database Structure](#database-structure)
- [Security](#security)
- [Contributing](#contributing)

---

## 🎯 About the Project

**WorkspaceBridge** is a comprehensive **Laravel admin panel** built for institutions (like universities) that need deep integration with Google Workspace. It was built for **Lahore Leads University (leads.edu.pk)** but is designed to be reusable for any organization.

The system follows clean architecture principles — every Google service has its own dedicated **Controller** + **Service** layer, making it easy to understand, extend, and maintain.

### Why This Project Exists

Managing Google Workspace manually is time-consuming. This platform allows administrators to:
- Create institutional email accounts at scale
- Manage Google Classroom courses and assignments
- Send emails, upload videos, manage calendar events
- Summarize website content or documents using AI
- Import and manage NADRA (identity) records with Excel
- Back up the database automatically to Google Drive

---

## ✨ Features

### 🔐 Authentication & Security
- **Laravel Breeze** authentication (login/register)
- **Two-Factor Authentication (2FA)** — TOTP-based
- **Role-Based Access Control (RBAC)** using Spatie Permissions
- **Google OAuth 2.0** — connect/disconnect Google accounts per user
- **Webhook signature validation** (HMAC-SHA256) for secure integrations

### 📅 Google Calendar
- View, create, update, and delete calendar events
- **Google Meet link** auto-generation for events
- Timezone support (UTC, Asia/Karachi, New York, London, Dubai, Tokyo)
- Token auto-refresh on expiry

### 📁 Google Drive
- List all files in Google Drive
- Upload files directly from the admin panel
- **Chunked upload** support for large backup files (1MB chunks)
- Auto-create dedicated backup folders in Drive

### 🎓 Google Classroom
- Full **CRUD** for courses (create, read, update, archive)
- Manage **coursework** (assignments, quizzes) with due dates
- Enroll/remove **students** and **teachers**
- View and grade **student submissions**
- Send **course invitations** via email
- Dashboard with recent coursework overview

### 📝 Google Forms
- Create Google Forms with title and description
- Add **text questions** and **multiple-choice questions**
- Fetch and display form **responses**
- Update form info (title, description)

### 📧 Gmail
- List inbox emails with subject, sender, date
- **Send emails** with HTML body and multiple attachments
- **Create drafts** with attachments
- Full MIME email construction with base64 encoding

### 🎬 YouTube
- Upload videos with title, description, tags, privacy settings
- Upload **custom thumbnails** (min 640×360px)
- **Create and manage playlists**
- Add videos to playlists automatically after upload
- View video **analytics** (views, likes, comments)
- **Bulk actions** — delete, change privacy, add to playlist
- Get full channel info and statistics

### 🤖 AI Agent
- **Summarize any website URL** using AI
- **Summarize YouTube videos** by URL
- **Summarize uploaded files** (PDF, TXT, XLSX, XLS)
- Ask **questions** about any URL or uploaded file
- API health check endpoint
- Supports file sizes up to 10MB

### 🏛️ Google Admin / Workspace (Institutional Emails)
- Create Google Workspace accounts for users (`@leads.edu.pk`)
- **Batch email creation** from LAMS (student management system)
- **Webhook endpoint** for auto-creation when users are added elsewhere
- Check email status and sync with Google
- Impersonate admin user via service account

### 📊 NADRA Records Management
- Import records from **Excel/CSV files** (XLSX, XLS, CSV)
- **DataTables** integration for fast server-side searching/sorting
- CNIC format validation (`12345-1234567-1`)
- Duplicate CNIC detection within same file upload
- CRUD operations with validation
- File upload history with record counts

### 💬 WhatsApp Integration
- Send WhatsApp messages via **Twilio API**
- Receive incoming messages via **webhook**
- View full conversation history by phone number
- Auto-reply on incoming messages

### 🗄️ Database Backup
- **Method 1:** `mysqldump` — fast, production-grade
- **Method 2:** Laravel DB Facade — works without shell access (shared hosting)
- **Method 3:** Compressed backup using `gzip`
- Upload backups directly to **Google Drive**
- Auto-clean old backups (configurable retention days)

---

## 🏗️ Architecture Overview

```
GoogleWorkThrough/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # One controller per feature
│   │   │   ├── GoogleAuthController.php
│   │   │   ├── GoogleCalendarController.php
│   │   │   ├── GoogleClassroomController.php
│   │   │   ├── GoogleDriveController.php
│   │   │   ├── GoogleFormsController.php
│   │   │   ├── GoogleGmailController.php
│   │   │   ├── YoutubeController.php
│   │   │   ├── WhatsAppController.php
│   │   │   ├── AIAgentController.php
│   │   │   ├── NadraController.php
│   │   │   ├── RoleController.php
│   │   │   ├── PermissionController.php
│   │   │   └── UserController.php
│   │   └── Requests/             # Form Request validation classes
│   ├── Services/                 # Business logic layer
│   │   ├── GoogleClientService.php    # Core OAuth token management
│   │   ├── GoogleCalendarService.php
│   │   ├── GoogleClassroomService.php
│   │   ├── GoogleDriveService.php
│   │   ├── GoogleFormsService.php
│   │   ├── GoogleGmailService.php
│   │   ├── GoogleAdminService.php
│   │   ├── WorkspaveApiService.php
│   │   ├── YouTubeService.php
│   │   ├── AIAgentService.php
│   │   └── DatabaseBackupService.php
│   ├── Models/                   # Eloquent Models
│   └── Imports/                  # Maatwebsite Excel Imports
├── routes/
│   └── web.php                   # All routes organized by feature
└── resources/views/admin/        # Blade views per module
```

### Design Pattern: Controller → Service

Every module follows the same clean pattern:

```
HTTP Request
    ↓
Controller (validates input, handles HTTP)
    ↓
Service (business logic, Google API calls)
    ↓
Response (JSON or redirect)
```

This means controllers are thin and services are reusable — you can call `GoogleCalendarService` from anywhere in the app.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11.x |
| PHP | 8.2+ |
| Database | MySQL 8.0+ |
| Authentication | Laravel Breeze + 2FA |
| Authorization | Spatie Laravel-Permission |
| Google APIs | `google/apiclient` |
| WhatsApp | Twilio SDK |
| Excel Import | Maatwebsite/Laravel-Excel |
| DataTables | Yajra DataTables |
| HTTP Client | Laravel Http Facade (Guzzle) |
| Date/Time | Carbon |
| Frontend | Bootstrap 5, HTML, CSS, JS |

---

## 📦 Prerequisites

Before installing, make sure you have:

- PHP >= 8.2 with extensions: `ext-json`, `ext-gd`, `ext-zip`, `ext-curl`
- Composer
- MySQL 8.0+
- Node.js & NPM
- A **Google Cloud Project** with APIs enabled (see [Google API Setup](#google-api-setup))
- A **Twilio Account** (for WhatsApp, optional)
- `mysqldump` accessible from command line (for backup feature)

---

## 🚀 Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/sm-altamash/GoogleWorkThrough.git
cd GoogleWorkThrough
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install Node Dependencies

```bash
npm install && npm run build
```

### Step 4: Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### Step 5: Configure Database

Open `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=google_workthrough
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 6: Run Migrations

```bash
php artisan migrate
```

### Step 7: Seed Initial Roles (Optional)

```bash
php artisan db:seed
```

### Step 8: Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

---

## 🔑 Google API Setup

This is the most important configuration step. Follow carefully.

### Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Click **New Project** → give it a name → **Create**
3. Select your new project

### Step 2: Enable Required APIs

Navigate to **APIs & Services → Library** and enable:

| API | Required For |
|-----|-------------|
| Google Calendar API | Calendar events |
| Google Drive API | Drive file management |
| Google Classroom API | Classroom management |
| Google Forms API | Forms creation |
| Gmail API | Email sending |
| YouTube Data API v3 | Video upload |
| Admin SDK Directory API | Institutional emails |

### Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → OAuth 2.0 Client IDs**
3. Application type: **Web Application**
4. Add Authorized Redirect URI:
   ```
   http://localhost:8000/auth/google/callback
   ```
   (use your production URL in production)
5. Download the JSON file — save it as `google-credentials.json`

### Step 4: Create Service Account (for Admin SDK)

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → Service Account**
3. Give it a name, click **Done**
4. Click on the service account → **Keys** tab → **Add Key → JSON**
5. Save the file as `google-service-account.json`
6. **Enable Domain-Wide Delegation** on the service account
7. In Google Admin Console, authorize the service account with required scopes

### Step 5: Configure OAuth Scopes

In Google Cloud Console → **OAuth consent screen**, add scopes:

```
https://www.googleapis.com/auth/calendar
https://www.googleapis.com/auth/drive
https://www.googleapis.com/auth/classroom.courses
https://www.googleapis.com/auth/classroom.coursework.students
https://www.googleapis.com/auth/classroom.rosters
https://www.googleapis.com/auth/forms.body
https://www.googleapis.com/auth/gmail.send
https://www.googleapis.com/auth/youtube
https://www.googleapis.com/auth/admin.directory.user
```

---

## ⚙️ Configuration

### `.env` File — All Settings

```env
# App
APP_NAME="GoogleWorkThrough"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_DATABASE=google_workthrough
DB_USERNAME=root
DB_PASSWORD=

# Google OAuth (from OAuth 2.0 credentials JSON)
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
GOOGLE_APPLICATION_NAME="GoogleWorkThrough"
GOOGLE_DOMAIN=leads.edu.pk
GOOGLE_ADMIN_EMAIL=admin@leads.edu.pk

# Google Service Account (path to JSON key file)
GOOGLE_SERVICE_ACCOUNT_PATH=/path/to/google-service-account.json

# Twilio (WhatsApp)
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-auth-token
TWILIO_WHATSAPP_NUMBER=+14155238886

# AI Agent API (your external AI service)
AI_AGENT_BASE_URL=http://your-ai-api.com
AI_AGENT_API_KEY=your-api-key
AI_AGENT_TIMEOUT=30

# Webhook Security
WEBHOOK_SECRET=your-random-secret-key
```

### `config/google.php`

Create this file for Google-specific settings:

```php
return [
    'client_id'            => env('GOOGLE_CLIENT_ID'),
    'client_secret'        => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri'         => env('GOOGLE_REDIRECT_URI'),
    'application_name'     => env('GOOGLE_APPLICATION_NAME', 'GoogleWorkThrough'),
    'domain'               => env('GOOGLE_DOMAIN', 'leads.edu.pk'),
    'admin_email'          => env('GOOGLE_ADMIN_EMAIL'),
    'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH'),
    'default_org_unit'     => '/Students',
    'force_password_change' => true,
    'default_password_length' => 12,
    'retry_attempts'       => 3,
    'scopes' => [
        \Google\Service\Calendar::CALENDAR,
        \Google\Service\Drive::DRIVE,
        \Google\Service\Gmail::GMAIL_SEND,
        \Google\Service\YouTube::YOUTUBE,
        \Google\Service\Classroom::CLASSROOM_COURSES,
        \Google\Service\Forms::FORMS_BODY,
    ],
];
```

---

## 📚 Modules

### 1. Google Authentication Flow

```
User clicks "Connect Google"
    → GoogleAuthController::redirect()
    → GoogleClientService::getAuthUrl()
    → Redirect to Google OAuth page
    → User grants permissions
    → Google redirects to /auth/google/callback
    → GoogleAuthController::callback()
    → GoogleClientService::storeToken()  ← saves access_token + refresh_token to DB
    → User is now connected
```

Tokens are stored per-user in the `google_tokens` table. When a token expires, `GoogleClientService::refreshToken()` automatically gets a new one using the `refresh_token`.

---

### 2. Google Calendar

**Routes:**
```
GET  /calendar/view       → Show calendar UI
GET  /calendar            → List events (JSON)
POST /calendar            → Create event (JSON)
PUT  /calendar/{eventId}  → Update event (JSON)
DEL  /calendar/{eventId}  → Delete event (JSON)
GET  /calendar/{eventId}  → Get single event (JSON)
```

**Creating an event with Google Meet:**
```javascript
fetch('/calendar', {
    method: 'POST',
    body: JSON.stringify({
        title: 'Team Meeting',
        start_time: '2025-12-01T10:00',
        end_time: '2025-12-01T11:00',
        timezone: 'Asia/Karachi',
        description: 'Weekly sync',
        create_meet: true  // ← generates Google Meet link
    })
})
```

---

### 3. Google Classroom

Full resource management:

```
GET  /classroom/courses              → List all courses
POST /classroom/courses              → Create course
GET  /classroom/courses/{id}         → View course
PUT  /classroom/courses/{id}         → Update course
DEL  /classroom/courses/{id}         → Archive course

GET  /classroom/courses/{id}/students         → List students
POST /classroom/courses/{id}/students         → Add student
DEL  /classroom/courses/{id}/students/{sid}   → Remove student

POST /classroom/courses/{id}/coursework/{cid}/submissions/{sub}/grade → Grade submission
```

---

### 4. YouTube Upload

The upload process handles large files using **chunked uploading** (1MB per chunk):

```
1. Validate file (max 128MB, mp4/mov/avi/wmv/mpeg/webm)
2. Validate thumbnail (max 2MB, min 640×360px, jpg/png)
3. Create VideoSnippet (title, description, tags)
4. Create VideoStatus (public/unlisted/private)
5. Initiate chunked upload to YouTube API
6. Read video file in 1MB chunks → upload each chunk
7. Upload thumbnail separately
8. Add to playlist if selected
9. Return success with video ID
```

---

### 5. NADRA Records

Import Excel files with identity records:

```
1. Upload Excel file (max 10MB)
2. Select category for this batch
3. FileUpload record created in DB
4. NadraImport class processes each row
5. CNIC format validated (12345-1234567-1)
6. Duplicate detection within same file
7. Records saved to nadra_records table
8. Update total_records count on FileUpload
```

Supports partial success — if some rows fail validation, valid rows are still imported.

---

### 6. Database Backup

Three backup methods available:

```php
// Method 1: Fast (requires mysqldump in PATH)
$backupService->createBackupUsingMysqldump();

// Method 2: Pure PHP (shared hosting friendly)
$backupService->createBackupUsingLaravel();

// Method 3: Compressed (saves 70-90% disk space)
$backupService->createCompressedBackup();

// Upload to Google Drive
$driveService->uploadBackupFile($user, $backupPath, $fileName, $folderId);
```

---

## 🌐 API Endpoints

### AI Agent API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/ai-agent/summarize/url` | Summarize website or YouTube URL |
| POST | `/api/ai-agent/question/url` | Ask question about URL content |
| POST | `/api/ai-agent/summarize/file` | Summarize uploaded file |
| POST | `/api/ai-agent/question/file` | Ask question about uploaded file |
| GET  | `/ai-agent/health` | Check AI API health status |

### Admin Management API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/admin-management/create-email` | Create single institutional email |
| POST | `/admin-management/create-from-module` | Batch create from LAMS system |
| POST | `/admin-management/webhook/create-email` | Webhook auto-creation |
| GET  | `/admin-management/email-status/{userId}` | Get email status |

### WhatsApp

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/whatsapp/webhook` | Receive incoming messages (public) |
| POST | `/whatsapp/send` | Send a WhatsApp message |
| GET  | `/whatsapp` | List all conversations |
| GET  | `/whatsapp/{number}` | Get conversation with a number |

---

## 🗃️ Database Structure

Key tables used by this application:

```sql
-- Google OAuth tokens per user
google_tokens
  - user_id (FK → users)
  - access_token
  - refresh_token
  - expires_at

-- Institutional email records
institutional_emails
  - user_id
  - username
  - email
  - first_name, last_name
  - department
  - google_user_id
  - status (active/suspended)
  - password (temporary)
  - google_response (JSON)
  - email_created_at
  - last_synced_at

-- NADRA identity records
nadra_records
  - full_name, father_name
  - gender, date_of_birth
  - cnic_number (unique per file)
  - family_id
  - addresses, province, district
  - file_upload_id (FK)

-- Excel file upload batches
file_uploads
  - original_filename
  - stored_filename
  - category
  - total_records
  - uploaded_at

-- WhatsApp messages
messages
  - user_id (nullable)
  - from, to (phone numbers)
  - body
  - direction (inbound/outbound)
```

---

## 🔒 Security

This project implements multiple security layers:

| Security Feature | Implementation |
|----------------|----------------|
| Authentication | Laravel Auth + 2FA TOTP |
| Authorization | Spatie RBAC (roles + permissions) |
| Google Token | Per-user, stored encrypted, auto-refreshed |
| Webhook Security | HMAC-SHA256 signature validation |
| File Upload | Strict MIME type + size validation |
| SQL Injection | Eloquent ORM + parameterized queries |
| CSRF | Laravel CSRF tokens on all forms |
| Rate Limiting | Google API retry with exponential backoff |
| Input Validation | Form Requests + Validator facade |

### Webhook Signature Validation

```php
// Incoming webhook requests are verified with:
$computedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
hash_equals($computedSignature, $signature);  // timing-safe comparison
```

### Google API Retry Logic

API calls to Google automatically retry on:
- `429` Rate Limit Exceeded
- `500`, `502`, `503`, `504` Server Errors  
- Network timeout/connection errors

With **exponential backoff**: waits 1s, 2s, 4s between retries.

---

## 📁 File Storage

Uploaded files and backups are stored in Laravel's `storage/app/` directory:

```
storage/
└── app/
    ├── backups/          ← Database backup files (.sql, .sql.gz)
    └── public/           ← Public files (if any)
```

Run `php artisan storage:link` to create the public symlink.

---

## 🧪 Testing the Setup

After installation, verify everything works:

```bash
# 1. Check if the app loads
php artisan serve
# Open http://localhost:8000

# 2. Test Google connection
# → Log in → Go to /auth/google → Connect your account

# 3. Test Calendar
# → Go to /calendar/view → Should see calendar UI

# 4. Test AI Agent health
# → GET /ai-agent/health → Should return { healthy: true }

# 5. Test NADRA import
# → Go to /nadra → Upload a sample Excel file
```

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/your-feature`
3. Follow the existing pattern: Controller → Service
4. Add validation using Form Requests
5. Commit your changes: `git commit -m 'Add some feature'`
6. Push to the branch: `git push origin feature/your-feature`
7. Open a Pull Request

### Code Style

- Follow **PSR-12** PHP coding standards
- Use **type declarations** on method parameters and return types
- Always wrap service calls in **try-catch**
- Log all important actions using `Log::info()` and errors with `Log::error()`
- Use **DB transactions** (`DB::beginTransaction()`) for multi-step database operations

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## 👨‍💻 Author

**SM Altamash**  
GitHub: [@sm-altamash](https://github.com/sm-altamash)  
Repository: [WorkspaceBridge](https://github.com/sm-altamash/WorkspaceBridge)

---

## 🙏 Acknowledgments

- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Maatwebsite Laravel Excel](https://laravel-excel.com)
- [Yajra DataTables](https://yajrabox.com/docs/laravel-datatables)
- [Twilio PHP SDK](https://github.com/twilio/twilio-php)
- [Laravel Framework](https://laravel.com)

---

> **Built with ❤️ using Laravel + Google Workspace APIs**
