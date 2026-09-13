<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Document downloads

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

## Monthly report PDF downloads

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

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Calendar holidays

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
