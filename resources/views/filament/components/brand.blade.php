@php
    $systemSettings = app(\App\Services\SystemSettingService::class);
@endphp

<div class="lextrack-brand">
    <img
        src="{{ asset('images/lextrack-logo.png.png') }}"
        alt="LexTrack Bicol University Legal Office"
        class="lextrack-brand-logo"
    >

    <div class="lextrack-brand-text">
        <div class="lextrack-brand-title">{{ $systemSettings->systemName() }}</div>

        <div class="lextrack-brand-subtitle">
            <span class="lextrack-brand-b">B</span><span class="lextrack-brand-u">U</span>
            <span class="lextrack-brand-office">Legal Affairs Office</span>
        </div>
    </div>
</div>
