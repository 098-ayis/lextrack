<section class="processing-trend" aria-label="Daily document processing trend">
    <div class="processing-trend-heading">
        <span class="processing-trend-period">Last 14 days</span>
    </div>
    @php
        $ceiling = max(25, (int) ceil($trend['maximum'] / 5) * 5);
        $chartTop = 20;
        $chartBottom = 180;
        $chartHeight = $chartBottom - $chartTop;
        $points = collect($trend['days'])->map(fn ($day, $index) => [
            'x' => 36 + $index * (728 / max(1, count($trend['days']) - 1)),
            'y' => $chartBottom - $day['count'] / $ceiling * $chartHeight,
            'date' => $day['date'], 'count' => $day['count'],
        ]);
        $linePoints = $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
        $firstPoint = $points->first();
        $lastPoint = $points->last();
        $areaPath = 'M'.$firstPoint['x'].' '.$chartBottom.' L'.$linePoints.' L'.$lastPoint['x'].' '.$chartBottom.' Z';
    @endphp
    <svg class="processing-trend-line" viewBox="0 0 800 220" role="img" aria-labelledby="processing-line-title">
        <title id="processing-line-title">Unique papers processed each day: {{ $points->map(fn ($point) => $point['date'].': '.$point['count'])->implode(', ') }}</title>
        <defs>
            <linearGradient id="processing-area" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" class="processing-trend-area-start" />
                <stop offset="100%" class="processing-trend-area-end" />
            </linearGradient>
        </defs>
        @for($value = 0; $value <= $ceiling; $value += 5)
            @php($y = $chartBottom - $value / $ceiling * $chartHeight)
            <text x="26" y="{{ $y + 3 }}" text-anchor="end" class="processing-trend-label">{{ $value }}</text>
            @if($value > 0 && $value < $ceiling)
                <path d="M36 {{ $y }} H764" class="processing-trend-grid" />
            @endif
        @endfor
        <path d="M36 {{ $chartTop }} V{{ $chartBottom }} H764" class="processing-trend-axis" />
        <path d="{{ $areaPath }}" class="processing-trend-area" />
        <polyline points="{{ $linePoints }}" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
        @foreach($points as $index => $point)
            <g><title>{{ $point['date'] }}: {{ $point['count'] }} papers</title>
                @if($point['count'] > 0 || $index === $points->count() - 1)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4.5" class="processing-trend-point" />
                @endif
                @if($index % 2 === 0 || $index === $points->count() - 1)
                    <text x="{{ $point['x'] }}" y="210" text-anchor="middle" class="processing-trend-label">{{ $point['date'] }}</text>
                @endif
            </g>
        @endforeach
    </svg>
</section>
