<section class="processing-trend" aria-label="Daily document processing trend">
    <div class="processing-trend-heading">
        <span class="processing-trend-period">Last 14 days</span>
    </div>
    @php
        $ceiling = max(10, (int) ceil($trend['maximum'] / 5) * 5);
        $points = collect($trend['days'])->map(fn ($day, $index) => [
            'x' => 36 + $index * (728 / max(1, count($trend['days']) - 1)),
            'y' => 96 - $day['count'] / $ceiling * 80,
            'date' => $day['date'], 'count' => $day['count'],
        ]);
    @endphp
    <svg class="processing-trend-line" viewBox="0 0 800 120" role="img" aria-labelledby="processing-line-title">
        <title id="processing-line-title">Unique papers processed each day: {{ $points->map(fn ($point) => $point['date'].': '.$point['count'])->implode(', ') }}</title>
        <defs>
            <marker id="processing-arrow" viewBox="0 0 10 10" markerWidth="9" markerHeight="9" refX="8" refY="5" markerUnits="userSpaceOnUse" orient="auto"><path d="M3 1.5 L8 5 L3 8.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></marker>
        </defs>
        @for($value = 0; $value <= $ceiling; $value += 5)
            @php($y = 96 - $value / $ceiling * 80)
            <text x="26" y="{{ $y + 3 }}" text-anchor="end" class="processing-trend-label">{{ $value }}</text>
            <path d="M36 {{ $y }} H764" class="processing-trend-grid" />
        @endfor
        <polyline points="{{ $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" marker-end="url(#processing-arrow)" />
        @foreach($points as $point)
            <g><title>{{ $point['date'] }}: {{ $point['count'] }} papers</title>
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6" fill="transparent" />
                <text x="{{ $point['x'] }}" y="116" text-anchor="middle" class="processing-trend-label">{{ $point['date'] }}</text>
            </g>
        @endforeach
    </svg>
</section>
