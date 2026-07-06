@extends('layouts.main')

@section('header')
<nav>
    <ul>
        <li><a href="{{ route('login') }}">Login</a></li>
    </ul>
</nav>
@endsection

@section('maincontent')

<div id="app"></div>

@endsection

@section('footer')
<p>&copy; 2024 LexTrack. All rights reserved.</p>
@endsection