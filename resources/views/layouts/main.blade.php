<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LexTrack</title>

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('images/lextrack-logo.png.png') }}"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @vite('resources/js/public.js')
</head>
<body>

    <header>
        @yield('header')
    </header>

    <main>
        @yield('maincontent')
    </main>

    <footer>
        @yield('footer')
    </footer>

</body>
</html>
