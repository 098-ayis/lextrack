<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DocumentStats;
use App\Http\Middleware\EnsureLegalStaff;
use App\Http\Middleware\FilamentAuthenticate;
use App\Http\Middleware\IdleTimeout;
use App\Livewire\DatabaseNotifications;
use App\Models\Document;
use App\Models\DocumentRequest;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
        Route::middleware(['web', 'auth', 'admin',  EnsureLegalStaff::class])
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

                $fileName = $document->document_name ?: basename($filePath);
                $copy = \Illuminate\Support\Facades\DB::table('cabinet_copies')
                    ->where('document_id', $document->document_id)->where('display_name', $filename)->first();
                if ($copy) { $fileName = $copy->display_name; }
                $mimeType = $disk->mimeType($filePath)
                    ?: 'application/octet-stream';

                if ($filename !== $fileName) {
                    return redirect()->route('admin.documents.file', [
                        'document' => $document,
                        'filename' => $fileName,
                    ]);
                }

                if ($mimeType === 'application/pdf') {
                    try {
                        $pdf = new \setasign\Fpdi\Fpdi;
                        $count = $pdf->setSourceFile($disk->path($filePath));
                        $pdf->SetTitle($fileName, true);
                        for ($page = 1; $page <= $count; $page++) {
                            $template = $pdf->importPage($page);
                            $size = $pdf->getTemplateSize($template);
                            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $pdf->useTemplate($template);
                        }
                        return response($pdf->Output('S'), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition('inline', $fileName, \Illuminate\Support\Str::ascii($fileName)),
                            'Cache-Control' => 'private, no-store',
                        ]);
                    } catch (\setasign\Fpdi\PdfParser\PdfParserException $exception) {
                        // PDFs unsupported by the importer remain available in their original form.
                    }
                }

                return $disk->response(
                    $filePath,
                    $fileName,
                    ['Content-Type' => $mimeType],
                    'inline',
                );
            })
            ->name('admin.documents.file');

        Route::middleware(['web', 'auth', 'admin', EnsureLegalStaff::class])
            ->get('/admin/navigation-counts', function () {
                return response()->json([
                    'documents' => Document::query()
                        ->where('status', 'pending')
                        ->count(),
                    'requests' => DocumentRequest::query()
                        ->where('status', 'pending')
                        ->count(),
                    'notifications' => auth()->user()
                        ->unreadNotifications()
                        ->where('data->format', 'filament')
                        ->count(),
                ], headers: [
                    'Cache-Control' => 'no-store, private',
                ]);
            })
            ->name('admin.navigation.counts');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->spa()
            ->spaUrlExceptions([
                '*/admin/documents/*/download',
                '*/admin/documents/*/versions/*/download',
                '*/admin/documents/*/transmittal-download',
            ])
            ->maxContentWidth(\Filament\Support\Enums\Width::Full)
            ->sidebarWidth('15rem')
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4rem')
            ->collapsibleNavigationGroups(false)
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.admin.sidebar-default-state'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.admin.windows-scale'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn () => view('filament.admin.sidebar-badge-poll'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn () => view('filament.admin.sidebar-collapse-button'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn () => view('filament.admin.sidebar-expand-empty-space'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.admin.sidebar-logout'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                function () {
                    $route = request()->route();
                    $documentIdentifier = $route?->parameter('document');

                    if (request()->routeIs('filament.admin.pages.documents.*')) {
                        $document = $documentIdentifier instanceof Document
                            ? $documentIdentifier
                            : ((is_string($documentIdentifier) || is_int($documentIdentifier))
                                ? Document::findForRoute($documentIdentifier)
                                : null);

                        if ($document) {
                            return view('filament.admin.document-page-title', [
                                'document' => $document,
                            ]);
                        }
                    }

                    return view('filament.admin.page-title');
                },
            )
            ->globalSearch(false)
            ->databaseNotifications(true, DatabaseNotifications::class)
            ->databaseNotificationsPolling('5s')

            ->brandLogo(fn () => view('filament.components.brand'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/lextrack-logo.png.png'))

            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->navigationGroups([
                'MANAGEMENT',
                'OPERATIONS',
                'ADMINISTRATION',
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )
            ->discoverClusters(
                in: app_path('Filament/Clusters'),
                for: 'App\\Filament\\Clusters',
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

                IdleTimeout::class,

                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('ADMINISTRATION'),
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
