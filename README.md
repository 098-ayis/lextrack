# LexTrack

A web-based document tracking and management system for the
Bicol University Legal Affairs Office, developed as a BSIT
Capstone Project at Bicol University.

## Features

- Role-based access control (client and admin)
- Document submission, validation, and status tracking
- Secure messaging and calendar/scheduling
- Notifications, monthly reports, and chatbot assistance

## Tech Stack

Laravel, Vue, Tailwind CSS + daisyUI, MySQL, Docker (Laravel Sail),
Google Authentication

## Setup

**Requirements:** Git and Docker Desktop.

1. Clone the repository and switch to `develop`:

   ```bash
   git clone https://github.com/098-ayis/lextrack.git
   cd lextrack
   git checkout develop
   ```

2. Copy the environment file, then open `.env` and set your database,
   mail, and Google OAuth values:

   ```bash
   cp .env.example .env
   ```

3. Start the containers:

   ```bash
   docker compose up -d --build
   ```

4. Install PHP dependencies, generate the app key, and run the migrations:

   ```bash
   docker compose exec --user sail laravel.test composer install
   docker compose exec --user sail laravel.test php artisan key:generate
   docker compose exec --user sail laravel.test php artisan migrate
   ```

5. Install and build the frontend:

   ```bash
   docker compose exec --user sail laravel.test npm ci
   docker compose exec --user sail laravel.test npm run build
   ```

6. Open the app at http://localhost.

## Temporary Render deployment

The root `Dockerfile` builds Vite assets and includes the PHP extensions,
LibreOffice Writer, Ghostscript, ImageMagick, Node, and Playwright Chromium
used by document previews, QR-stamped downloads, thumbnails, and reports.

For a temporary Render deployment:

1. Create a Render Web Service with **Language: Docker**, using the root
   `Dockerfile`. The image listens on Render's `PORT` value and binds to
   `0.0.0.0`.
2. Use an Aiven MySQL database with `DB_CONNECTION=mysql`. Add the Aiven CA
   certificate as a Render Secret File and set `MYSQL_ATTR_SSL_CA` to its
   absolute path and `MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true`.
3. Create a private Backblaze B2 bucket and a bucket-scoped application key.
   Set `DOCUMENT_FILESYSTEM_DISK=s3` plus the `AWS_*` values shown in
   `.env.example`, including the regional HTTPS S3 endpoint.
4. Set `APP_KEY`, `APP_URL`, Google OAuth callback values, and production
   mail/Turnstile values in Render. Keep `APP_DEBUG=false`, set
   `SESSION_SECURE_COOKIE=true`, and use `LOG_STACK=stderr` so errors appear
   in Render logs.
5. Run `php artisan migrate --force` once from a controlled machine that has
   the Aiven connection values and CA file, or use a paid Render pre-deploy or
   one-off job. Render Free web services do not provide a dashboard shell. Do
   not run `migrate:fresh` against Aiven.

The production environment should include these non-secret settings:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-render-host>
DB_CONNECTION=mysql
MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true
FILESYSTEM_DISK=local
DOCUMENT_FILESYSTEM_DISK=s3
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_STACK=stderr
LOG_LEVEL=info
AWS_DEFAULT_REGION=<b2-region>
AWS_ENDPOINT=https://s3.<b2-region>.backblazeb2.com
AWS_USE_PATH_STYLE_ENDPOINT=true
GOOGLE_REDIRECT_URI=https://<your-render-host>/auth/google/callback
```

Add the remaining database, B2, Google, Turnstile, and `APP_KEY` values as
Render secret environment variables. Keep the B2 bucket private; LexTrack
serves authorized files through authenticated application routes rather than
publishing object URLs.

Render's free web service has an ephemeral local filesystem. Permanent
documents and message attachments therefore use B2; local storage is only for
temporary processing, caches, and logs. If no separate queue worker is
available, use `QUEUE_CONNECTION=sync` for this capstone deployment and run
scheduled reminder commands externally; otherwise database-queued jobs will
remain pending.

## Branching

- `main`: tested, stable version
- `develop`: shared integration branch
- `feature/*`: one short-lived branch per module or task, merged into
  `develop` through a reviewed pull request

## Team
- Kathleen Ann Borromeo
- Hershey Hestiada, Leader
- Chariesse Lobarbio
- Rhona Eloisa Lumbes

## Developer Notes

### Document downloads

DOCX downloads keep their Word format and include a clickable QR image above the document content. The original upload is unchanged. PDF downloads remain PDF. Legacy `.doc` downloads require LibreOffice Writer to convert the document to PDF before adding its QR code. The Sail image includes `libreoffice-writer` and `fonts-liberation` in `docker/8.5/Dockerfile`.

If downloads fail with `exec: libreoffice: not found`, rebuild the application image and recreate the application container from the project directory:

```bash
docker compose build laravel.test
docker compose up -d --no-deps laravel.test
docker compose exec -T laravel.test libreoffice --version
```

Verify PDF conversion and QR stamping with:

```bash
docker compose exec -T --user sail laravel.test php artisan test --filter=DocumentDownloadTest
```

DOCX previews render the original Word data directly in the browser, without PDF conversion. DOCX downloads retain their Word format with the QR code. Only legacy `.doc` previews require LibreOffice Writer.

### Monthly report PDF downloads

Monthly report PDFs automatically install the Chromium version required by the
installed Playwright package. `npm ci` prepares it, container startup checks it,
and PDF rendering checks again if dependencies changed in a running container.
Browsers persist in the `report-browsers` Docker volume across container recreation.
The first installation requires internet access; cached browsers work offline.

After pulling dependency updates, use the normal setup commands:

```bash
docker compose up -d --build
docker compose exec -T --user sail laravel.test npm ci
docker compose exec -T --user sail laravel.test npm run build
```

No separate Playwright browser installation or Dockerfile version update is needed.
Verify PDF downloads with:

```bash
docker compose exec -T --user sail laravel.test php artisan test --filter=MonthlyReportPdfTest
```

### Calendar holidays

The calendar adds Philippine holidays automatically as read-only events. Staff
choose a category from the calendar legend for their own events; document deadlines
are categorized automatically. Automatic holidays are not saved as staff events
and do not generate staff reminder emails.

Verified 2026 nationwide dates are bundled in `config/holidays.php`, including
Proclamations 1006, 1189, and 1264. Other years use the public Google Philippine
holiday calendar, checked once daily with the last successful result cached for
outages. Availability and later date corrections depend on the published feed.
Local holidays can be added by staff. `PH_HOLIDAY_FEED_URL` can override the feed.

Calendar legends start with Holidays, Meetings, and Deadlines. Staff can use the
plus button in the Add/Edit Event category selector to save a new category and
its color. Added categories appear in the shared calendar legend and selector.
