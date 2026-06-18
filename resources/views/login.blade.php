@extends('layouts.main')
    
@section('header')
    <nav>
        <ul>
            <li><a href="{{ route('login') }}">Login</a></li>
        </ul>
    </nav>
@endsection

@section('maincontent')
    <form action="/formsubmitted">
        @csrf
        <label for="email">Email: </label>
        <input type="email" id="email" name="email">
        <label for="password">Password: </label>
        <input type="password" id="password" name="passwprd">
        <br><br>
        <button type="submit">Login</button>
    </form>
@endsection

@section('footer')
<p>&copy; 2024 LexTrack. All rights reserved.</p>
@endsection

