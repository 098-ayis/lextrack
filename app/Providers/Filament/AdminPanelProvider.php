<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DocumentStats;
use App\Http\Middleware\FilamentAuthenticate;
use App\Models\Document;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;


class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->get('/admin/documents/{document}/file/{filename}', function (
                Document $document,
                string $filename,
            ) {
                $document->loadMissing('latestVersion');

                $version = $document->latestVersion;
                $filePath = $version?->file_path;

                abort_unless($version && $filePath, 404);

                $disk = $version->storageDisk();

                abort_unless($disk->exists($filePath), 404);

                $fileName = basename($filePath);
                $mimeType = $disk->mimeType($filePath)
                    ?: 'application/octet-stream';

                if ($filename !== $fileName) {
                    return redirect()->route('admin.documents.file', [
                        'document' => $document,
                        'filename' => $fileName,
                    ]);
                }

                return $disk->response(
                    $filePath,
                    $fileName,
                    ['Content-Type' => $mimeType],
                    'inline',
                );
            })
            ->name('admin.documents.file');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->topNavigation()
            ->globalSearch(false)
            ->databaseNotifications()

            ->brandLogo(fn () => view('filament.components.brand'))
            ->brandLogoHeight('3rem')

            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages',
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets',
            )
            ->widgets([
                DocumentStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Access Control'),
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profile')
                    ->icon('heroicon-o-user-circle')
                    ->url('/admin/profile'),
            ]);
    }
}
