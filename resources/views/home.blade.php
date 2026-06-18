@extends('layouts.main')
    
@section('header')
    <nav>
        <ul>
            <li><a href="{{ route('login') }}">Login</a></li>
        </ul>
    </nav>
@endsection

@section('maincontent')
<img src="{{ asset('images/logo.png') }}" alt="logo">
<img src="{{ asset('images/lao.jpg') }}" alt="lao">

<h1>LexTrack</h1>
   
<h1>Ensuring Legal Strength for Bicol University.</h1>
<p>A smart, secure, and client‑friendly system that empowers the Legal Affairs Office to manage documents efficiently while offering instant support through AI‑driven chat.</p>
<button onclick="window.location.href='{{ route('testpage') }}'">Submit document</button>
<button onclick="window.location.href='{{ route('testpage') }}'">Track document</button>
@endsection

@section('footer')
<p>&copy; 2024 LexTrack. All rights reserved.</p>
@endsection
