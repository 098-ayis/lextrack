@extends('layouts.main')

@section('header')
    <nav>
        <ul>
        </ul>
    </nav>
@endsection

@section('maincontent')

    <div id="public-app"></div>

    <script>
        window.LexTrack = {
            flashStatus: @json(session('status')),
            turnstileSiteKey: @json(config('services.turnstile.key')),
        };
    </script>

@endsection
