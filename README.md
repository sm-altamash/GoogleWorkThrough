*Connect once. Automate everything. — A unified Laravel admin panel that brings Google Calendar, Drive, Classroom, Forms, Gmail, and YouTube under one institutional control plane.*

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Google APIs](https://img.shields.io/badge/Google%20APIs-Workspace-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://developers.google.com)
[![Twilio](https://img.shields.io/badge/Twilio-WhatsApp-F22F46?style=for-the-badge&logo=twilio&logoColor=white)](https://twilio.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

[![GitHub last commit](https://img.shields.io/github/last-commit/sm-altamash/WorkspaceBridge?color=6366F1)](https://github.com/sm-altamash/WorkspaceBridge) [![GitHub repo size](https://img.shields.io/github/repo-size/sm-altamash/WorkspaceBridge?color=8B5CF6)](https://github.com/sm-altamash/WorkspaceBridge) [![GitHub stars](https://img.shields.io/github/stars/sm-altamash/WorkspaceBridge?color=facc15)](https://github.com/sm-altamash/WorkspaceBridge) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen)](https://github.com/sm-altamash/WorkspaceBridge)

**[Features](#features)** · **[Architecture](#architecture)** · **[Installation](#installation)** · **[Contact Me](#support)**

## 📑 Table of Contents

- [Project Description](#project-description)
- [Screenshots](#screenshots)
- [Features](#features)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Database Design](#database-design)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Google API Setup](#google-api-setup)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [Automated Backups & Cron Setup](#automated-backups--cron-setup)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)
- [Why This Project Exists](#why-this-project-exists)
- [Future Improvements](#future-improvements)
- [Contributing](#contributing)
- [Support](#support)
- [Buy Me a Coffee](#buy-me-a-coffee)
- [License](#license)

## Project Description

**WorkspaceBridge** is a full-stack Laravel admin panel — think a mission control tower for an entire Google Workspace domain — built to show how a real institution automates the busywork of running Calendar, Classroom, Drive, Gmail, and YouTube from one place instead of ten browser tabs.

It's a complete integration platform: administrators create institutional email accounts at scale, run Google Classroom courses and grade submissions, schedule calendar events with auto-generated Meet links, send Gmail with attachments, and publish YouTube videos with thumbnails and playlists — all wrapped in a role-gated admin UI backed by a Laravel 11 application built around a strict **Controller → Service** separation, per-user encrypted OAuth tokens, and HMAC-signed webhooks instead of loose, ad-hoc integrations.

> **Built for:** institutions (originally Lahore Leads University) that need deep, auditable Google Workspace automation — and for anyone evaluating this repo who wants to see how I structure a multi-service integration codebase in Laravel.

## Features

**🔐 Authentication & Security**
- Laravel Breeze login/register with **Two-Factor Authentication** (TOTP)
- **Role-Based Access Control** via Spatie Permissions
- Per-user **Google OAuth 2.0** connect/disconnect
- **HMAC-SHA256** webhook signature validation

**📅 Google Calendar**
- Create, update, delete, and view events with auto-refreshing tokens
- One-click **Google Meet** link generation
- Multi-timezone support (Karachi, UTC, New York, London, Dubai, Tokyo)

**📁 Google Drive**
- Browse and upload files straight from the admin panel
- **Chunked upload** (1MB chunks) for large backup files
- Auto-created, dedicated backup folders

**🎓 Google Classroom**
- Full course CRUD plus coursework, assignments, and due dates
- Enroll or remove students and teachers, send invitations
- Review and **grade student submissions** from a dashboard

**📝 Google Forms & 📧 Gmail**
- Build forms with text and multiple-choice questions, then pull responses
- Send HTML email with multiple attachments, or save as drafts
- Full MIME message construction with base64 encoding

**🎬 YouTube**
- Upload videos with custom thumbnails, tags, and privacy settings
- Manage **playlists** and bulk actions (delete, re-privacy, add-to-playlist)
- Pull channel stats and per-video analytics

**🤖 AI Agent**
- Summarize any website, YouTube video, or uploaded file (PDF/TXT/XLSX)
- Ask free-form questions about a URL or document's contents

**🏛️ Institutional Emails & 📊 NADRA Records**
- Batch-create `@domain` Workspace accounts from an external student system
- Webhook-driven auto-creation with status sync
- Import identity records from Excel/CSV with CNIC validation and duplicate detection

**💬 WhatsApp (Twilio)**
- Two-way WhatsApp messaging via the **Twilio API** — send from the admin panel, receive via webhook
- Full conversation history per phone number, with **auto-reply** on incoming messages
- Inbound messages verified and logged before being written to the conversation thread

**🗄️ Automated Database Backups**
- Three backup strategies — `mysqldump` (fastest), pure Laravel DB Facade (shared-hosting friendly), and gzip-compressed (up to 90% smaller)
- **Cron-driven**, running automatically every 5 hours with no manual trigger
- Auto-uploads to a dedicated **Google Drive** folder, then cleans up local copies
- Secured with an `X-API-Key` header, independent of the main admin session
- Backup history endpoint to browse and verify past runs

## Architecture

WorkspaceBridge follows a strict **Controller → Service** convention: every Google product gets its own controller for HTTP concerns and its own service class for the actual API calls, so business logic never leaks into a route file and every service is independently reusable and testable.

```
flowchart TB
    Browser["🖥️ Admin Browser"]

    subgraph Laravel["Laravel 11 Application"]
        direction TB
        Router["Router"]
        MW["Middleware<br/>auth · 2FA · RBAC · CSRF"]
        Ctrl["Controllers<br/>(Calendar, Classroom, Drive, Gmail, YouTube...)"]
        FR["Form Requests<br/>(validation)"]
        Svc["Services<br/>(GoogleClientService + per-product services)"]
        Models["Eloquent Models"]
    end

    subgraph External["External APIs"]
        direction TB
        Google["Google Workspace APIs"]
        Twilio["Twilio WhatsApp"]
        AI["AI Agent API"]
    end

    subgraph Data["Persistence"]
        direction TB
        DB[("MySQL")]
        Drive[("Google Drive<br/>(backups)")]
    end

    Browser -->|HTTP request| Router --> MW --> Ctrl
    Ctrl --> FR
    Ctrl --> Svc
    Svc --> Google
    Svc --> Twilio
    Svc --> AI
    Svc --> Models --> DB
    Svc -->|encrypted tokens| DB
    Svc -->|backup upload| Drive
    Ctrl -->|JSON / redirect| Browser
```

    Loading

### Key Components

| Component | Responsibility |
|-----------|-----------------|
| `GoogleClientService` | Core OAuth token issuance, refresh, and storage |
| `GoogleCalendarService` | Event CRUD + Google Meet link generation |
| `GoogleClassroomService` | Courses, coursework, rosters, grading |
| `GoogleDriveService` | File listing, upload, chunked backup upload |
| `GoogleGmailService` | MIME message construction, send, drafts |
| `YouTubeService` | Chunked video upload, thumbnails, playlists, analytics |
| `GoogleAdminService` | Institutional email creation via Admin SDK |
| `AIAgentService` | Summarization and Q&A over URLs/files |
| `DatabaseBackupService` | Three backup strategies + Drive upload |

### Security Considerations

- **Per-user encrypted Google tokens**, refreshed automatically on expiry
- **HMAC-SHA256** signature verification on every incoming webhook, compared with `hash_equals` (timing-safe)
- **RBAC** via Spatie — every route gated by role/permission, not just auth
- **Strict file upload validation** — MIME type and size checked before anything touches storage or the Google API
- **SQL injection protection** via Eloquent's parameterized queries
- **Exponential backoff retries** (1s → 2s → 4s) on Google API `429`/`5xx`/timeout errors

## Tech Stack

| Layer | Technology | Why This Choice |
|-------|-----------|-------------------|
| **Backend** | Laravel 11, PHP 8.2+ | Mature ecosystem, expressive ORM, first-class validation and queueing for long-running API calls |
| **Authentication** | Laravel Breeze + TOTP 2FA | Minimal scaffold plus a real second factor, since this panel touches institutional data |
| **Authorization** | Spatie Laravel-Permission | Battle-tested RBAC without hand-rolling role/permission tables |
| **Google Integration** | `google/apiclient` | Official SDK — handles OAuth, refresh, and typed service objects for every Workspace API |
| **Messaging** | Twilio SDK | Reliable WhatsApp delivery and webhook receipt without managing the Business API directly |
| **Excel Import** | Maatwebsite/Laravel-Excel | Streaming import for large NADRA record files without memory blowups |
| **Data Tables** | Yajra DataTables | Server-side searching/sorting for large record sets without shipping the whole table to the browser |
| **Database** | MySQL 8.0+ | Relational integrity for tokens, records, and audit-style data |
| **Frontend** | Bootstrap 5 | Fast, consistent admin UI without building a component system from scratch |

## Database Design

```
erDiagram
    USERS ||--o{ GOOGLE_TOKENS : authorizes
    USERS ||--o{ INSTITUTIONAL_EMAILS : owns
    FILE_UPLOADS ||--o{ NADRA_RECORDS : contains
    USERS ||--o{ MESSAGES : "sends/receives"

    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        timestamp created_at
    }

    GOOGLE_TOKENS {
        bigint id PK
        bigint user_id FK
        text access_token
        text refresh_token
        timestamp expires_at
    }

    INSTITUTIONAL_EMAILS {
        bigint id PK
        bigint user_id FK
        string username
        string email
        string first_name
        string last_name
        string department
        string google_user_id
        string status
        json google_response
        timestamp email_created_at
        timestamp last_synced_at
    }

    NADRA_RECORDS {
        bigint id PK
        string full_name
        string father_name
        string gender
        date date_of_birth
        string cnic_number UK
        string family_id
        string province
        string district
        bigint file_upload_id FK
    }

    FILE_UPLOADS {
        bigint id PK
        string original_filename
        string stored_filename
        string category
        int total_records
        timestamp uploaded_at
    }

    MESSAGES {
        bigint id PK
        bigint user_id FK
        string from_number
        string to_number
        text body
        string direction
        timestamp created_at
    }
```

    Loading

**A note on the schema** — `google_tokens` is intentionally one-to-one-per-user rather than embedded on the `users` table, so a token refresh or revocation never touches the user record itself, and future providers (beyond Google) can hang off the same pattern without a migration on `users`.

## Project Structure

```
WorkspaceBridge/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # One controller per Google product
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
│   │   │   └── UserController.php
│   │   └── Requests/              # Form Request validation classes
│   ├── Services/                  # Business logic + Google API calls
│   │   ├── GoogleClientService.php
│   │   ├── GoogleCalendarService.php
│   │   ├── GoogleClassroomService.php
│   │   ├── GoogleDriveService.php
│   │   ├── GoogleGmailService.php
│   │   ├── GoogleAdminService.php
│   │   ├── YouTubeService.php
│   │   ├── AIAgentService.php
│   │   └── DatabaseBackupService.php
│   ├── Models/
│   └── Imports/                   # Maatwebsite Excel imports
│
├── routes/
│   └── web.php
│
├── resources/views/admin/         # Blade views per module
│
└── config/
    └── google.php
```

## Installation

### Prerequisites

- PHP 8.2+ with `ext-json`, `ext-gd`, `ext-zip`, `ext-curl`
- Composer, Node.js & npm
- MySQL 8.0+
- A Google Cloud Project (see [Google API Setup](#google-api-setup))
- A Twilio account (optional, for WhatsApp)
- `mysqldump` on PATH (optional, for the fast backup method)

### Setup

```
# 1. Clone the repository
git clone https://github.com/sm-altamash/WorkspaceBridge.git
cd WorkspaceBridge

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install && npm run build

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Set your database credentials in .env, then run migrations
php artisan migrate

# 6. (Optional) seed initial roles
php artisan db:seed

# 7. Serve the application
php artisan serve
```

Visit `http://localhost:8000` — you're in.

### Production Deployment

```
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## Google API Setup

1. Create a project in the [Google Cloud Console](https://console.cloud.google.com)
2. Enable: **Calendar API, Drive API, Classroom API, Forms API, Gmail API, YouTube Data API v3, Admin SDK Directory API**
3. Create an **OAuth 2.0 Client ID** (Web Application) with redirect URI `http://localhost:8000/auth/google/callback`
4. Create a **Service Account** for Admin SDK, enable **Domain-Wide Delegation**, and authorize it in the Google Admin Console
5. Add the required scopes on the OAuth consent screen (Calendar, Drive, Classroom, Forms, Gmail send, YouTube, Admin directory)

## Configuration

Key `.env` values:

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
GOOGLE_DOMAIN=yourdomain.edu
GOOGLE_ADMIN_EMAIL=admin@yourdomain.edu
GOOGLE_SERVICE_ACCOUNT_PATH=/path/to/google-service-account.json

TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-auth-token
TWILIO_WHATSAPP_NUMBER=+14155238886

AI_AGENT_BASE_URL=http://your-ai-api.com
AI_AGENT_API_KEY=your-api-key

WEBHOOK_SECRET=your-random-secret-key
```

## API Reference

**AI Agent**

| Method | Route | Description |
|--------|-------|--------------|
| POST | `/api/ai-agent/summarize/url` | Summarize a website or YouTube URL |
| POST | `/api/ai-agent/question/url` | Ask a question about URL content |
| POST | `/api/ai-agent/summarize/file` | Summarize an uploaded file |
| GET | `/ai-agent/health` | AI API health check |

**Institutional Emails**

| Method | Route | Description |
|--------|-------|--------------|
| POST | `/admin-management/create-email` | Create a single institutional email |
| POST | `/admin-management/create-from-module` | Batch-create from an external system |
| POST | `/admin-management/webhook/create-email` | Webhook auto-creation |
| GET | `/admin-management/email-status/{userId}` | Get email status |

**WhatsApp**

| Method | Route | Description |
|--------|-------|--------------|
| POST | `/whatsapp/webhook` | Receive incoming messages (public), triggers auto-reply |
| POST | `/whatsapp/send` | Send a message |
| GET | `/whatsapp` | List all conversations |
| GET | `/whatsapp/{number}` | Get conversation history with a number |

## Automated Backups & Cron Setup

Database backups run independently of the main admin session, secured by their own API key rather than a logged-in user — so a cron job on cPanel (or anywhere else) can trigger them without a browser in the loop.

**Flow**

```
Cron Job (cPanel / PHP) → POST /api/backup/create
→ ApiKeyMiddleware (validates X-API-Key)
→ BackupController
   ├─ DatabaseBackupService (creates & compresses the SQL dump)
   ├─ GoogleDriveService (uploads to Drive, chunked if >100MB)
   └─ Cleanup (removes local copy) + JSON response
→ Log file + Google Drive folder
```

**Backup routes**

| Method | Route | Description |
|--------|-------|--------------|
| POST | `/api/backup/create` | Create a backup and upload it to Drive |
| GET | `/api/backup/history` | List stored backups for a user |

**Trigger a backup manually**

```bash
curl -X POST https://yourdomain.com/api/backup/create \
  -H "X-API-Key: your-secret-key" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"compress":true,"delete_local":true}'
```

```json
{
  "success": true,
  "message": "Backup completed and uploaded successfully",
  "data": {
    "backup_file": "backup_2026-07-13_14-30-45.sql.gz",
    "backup_size": "15.5 MB",
    "drive_link": "https://drive.google.com/file/d/xyz/view"
  }
}
```

**Cron entry — runs every 5 hours**

```bash
0 */5 * * * /usr/bin/php /home/username/backup-cron.php >> /home/username/backup-cron.log 2>&1
```

```php
// backup-cron.php
$ch = curl_init('https://yourdomain.com/api/backup/create');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['user_id' => 1, 'compress' => true, 'delete_local' => true]),
    CURLOPT_HTTPHEADER => [
        'X-API-Key: your-secret-key',
        'Content-Type: application/json',
    ],
]);
echo curl_exec($ch);
curl_close($ch);
```

**Backup security & performance notes**

- Keep `BACKUP_API_KEY` out of version control and rotate it periodically
- Restrict the backup endpoint by IP where possible, and always call it over HTTPS
- Store Drive tokens in the database (encrypted), never in flat files
- Keep a rolling window of local backups (e.g. last 7) and let Drive hold the long-term history
- Schedule cron runs during low-traffic hours to avoid competing with live queries

## Troubleshooting

| Issue | Likely Cause | Fix |
|-------|---------------|-----|
| `Unauthorized` on backup endpoint | Wrong or missing API key | Check `.env` `BACKUP_API_KEY` matches the cron script's header |
| Backup uploads fail with no Drive file | Google token missing or expired | Reconnect via `/auth/google`, confirm refresh token is stored |
| `mysqldump not found` | Binary not on PATH | Run `which mysqldump` and update the path in `DatabaseBackupService` |
| `Permission denied` running cron script | File not executable | `chmod +x backup-cron.php` |
| `Memory exhausted` on large databases | Backup loaded into memory instead of streamed | Use the `mysqldump` strategy with compression instead of the pure-PHP method |
| WhatsApp messages not arriving | Webhook URL not verified in Twilio console | Re-check the webhook URL and signature validation logic |

## Roadmap

- [x] Google OAuth + per-user token storage
- [x] Calendar, Classroom, Drive, Gmail, YouTube integrations
- [x] Institutional email batch creation + webhooks
- [x] NADRA Excel import with validation
- [x] WhatsApp messaging via Twilio
- [x] Triple-strategy database backup to Drive, running on a cron schedule
- [ ] Admin activity audit log UI
- [ ] Public REST API with API-key auth
- [ ] Bulk Classroom roster import from CSV

## Why This Project Exists

Managing Google Workspace by hand doesn't scale past a handful of accounts. WorkspaceBridge exists so an institution can create accounts, run classrooms, and back up data from one authenticated admin panel instead of juggling the Admin Console, Classroom UI, and Drive separately.

## Future Improvements

- **Queue long-running jobs** (YouTube uploads, bulk email creation) instead of blocking the request
- **Redis-backed caching** for Classroom rosters and Drive file listings
- **Dockerized setup** for a one-command local environment
- **Public API layer** so LAMS-style systems can integrate without a webhook round-trip

## Contributing

```
# 1. Fork the repository, then:
git checkout -b feature/your-feature
# 2. Follow the existing Controller → Service pattern
# 3. Add validation via Form Requests
git commit -m "Add: your feature"
git push origin feature/your-feature
# 4. Open a Pull Request
```

Follow PSR-12, use type declarations, wrap service calls in try-catch, and log important actions with `Log::info()` / `Log::error()`.

## Support

Found a bug or have a feature request? [Open an issue](https://github.com/sm-altamash/WorkspaceBridge/issues).

[![GitHub](https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/sm-altamash)

## Buy Me a Coffee

If WorkspaceBridge helped you, consider supporting more projects like it:

[![Buy Me a Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-FFDD00?style=for-the-badge&logo=buymeacoffee&logoColor=black)](https://buymeacoffee.com/sm.altamash)

## License

This project is licensed under the [MIT License](LICENSE) — free to use, modify, and distribute.

---

**⭐ If this project helped you, consider starring the repo.**

Built with Laravel by [**SM Altamash**](https://github.com/sm-altamash)
