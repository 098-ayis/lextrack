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