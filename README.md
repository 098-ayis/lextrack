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

Monthly report PDFs use Playwright's Chromium browser in `/opt/playwright`.
If an older running container reports `Executable doesn't exist`, install the
browser matching the project's installed Playwright version:

```bash
docker compose exec -T --user root -e PLAYWRIGHT_BROWSERS_PATH=/opt/playwright laravel.test npx playwright install --with-deps chromium
docker compose exec -T --user sail laravel.test php artisan test --filter=MonthlyReportPdfTest
```

This repairs the current container. To include the browser when the container is
recreated, rebuild using the existing browser installation step in the Dockerfile:

```bash
docker compose build laravel.test
docker compose up -d --no-deps laravel.test
```

After updating Playwright, install its matching browser again and update the
Playwright version in `docker/8.5/Dockerfile` before rebuilding.

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
