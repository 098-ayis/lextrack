@extends('layouts.main')

@section('header')
    <nav>
        <ul>
        </ul>
    </nav>
@endsection

@section('maincontent')

    @php
        $isWindowsClient = preg_match('/Windows/i', (string) request()->userAgent()) === 1;
    @endphp

    <div
        id="public-app"
        class="{{ $isWindowsClient ? 'is-windows' : '' }}"
        style="{{ $isWindowsClient ? 'zoom: 0.78;' : '' }}"
    ></div>

    @if($isWindowsClient)
        <style id="windows-public-layout">
            html:has(#public-app.is-windows .hero-section),
            body:has(#public-app.is-windows .hero-section),
            body:has(#public-app.is-windows .hero-section) main {
                background: #0F172A !important;
            }

            body:has(#public-app.is-windows .hero-section) main {
                min-height: calc(100vh / 0.78);
            }

            #public-app.is-windows:has(.hero-section) {
                display: block;
                min-height: calc(100vh / 0.78);
                background: #0F172A;
            }

            #public-app.is-windows:has(.hero-section) .hero-section {
                height: calc(100vh / 0.78) !important;
                min-height: calc(100vh / 0.78) !important;
                box-sizing: border-box;
            }

            #public-app.is-windows:has(.public-login-layout) .public-login-layout {
                height: calc(100vh / 0.78) !important;
                min-height: calc(100vh / 0.78) !important;
                background: #f5f5f7 !important;
            }
        </style>

        <script>
            (() => {
                const publicRoot = document.getElementById('public-app');
                const zoomFactor = 0.78;

                const fitWindowsHero = () => {
                    const hero = publicRoot?.querySelector('.hero-section');

                    if (!hero) {
                        return;
                    }

                    const viewportHeight = window.visualViewport?.height || window.innerHeight;
                    const heroHeight = `${viewportHeight / zoomFactor}px`;

                    hero.style.setProperty('height', heroHeight, 'important');
                    hero.style.setProperty('min-height', heroHeight, 'important');
                    hero.style.setProperty('box-sizing', 'border-box');
                };

                const fitWindowsLogin = () => {
                    const loginLayout = publicRoot?.querySelector('.public-login-layout');

                    if (!loginLayout) {
                        return;
                    }

                    const viewportHeight = window.visualViewport?.height || window.innerHeight;
                    const layoutHeight = `${viewportHeight / zoomFactor}px`;

                    loginLayout.style.setProperty('height', layoutHeight, 'important');
                    loginLayout.style.setProperty('min-height', layoutHeight, 'important');
                    loginLayout.style.setProperty('background-color', '#f5f5f7', 'important');
                };

                const fitWindowsLayouts = () => {
                    fitWindowsHero();
                    fitWindowsLogin();
                };

                new MutationObserver(fitWindowsLayouts).observe(publicRoot, {
                    childList: true,
                    subtree: true,
                });

                window.addEventListener('resize', fitWindowsLayouts);
                fitWindowsLayouts();
            })();
        </script>
    @endif

    <script>
        window.LexTrack = {
            flashStatus: @json(session('status')),
            turnstileSiteKey: @json(config('services.turnstile.key')),
        };
    </script>

@endsection
