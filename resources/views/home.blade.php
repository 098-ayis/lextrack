@extends('layouts.main')

@section('header')
    <nav>
        <ul>
        </ul>
    </nav>
@endsection

@section('maincontent')

    <div id="app"></div>

    <script>
        window.LexTrack = {
            flashStatus: @json(session('status')),
        };
    </script>

@endsection
