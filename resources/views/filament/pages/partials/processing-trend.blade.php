<section class="processing-trend" aria-label="Document processing activity">
    <div class="dashboard-trend-header">
        <div class="dashboard-trend-summary">
            <span class="dashboard-trend-summary-label">Documents processed</span>
            <div class="dashboard-trend-summary-value">
                <strong>{{ number_format($trend['total']) }}</strong>
                <span class="dashboard-trend-summary-period">{{ $trend['periodLabel'] }}</span>
            </div>
        </div>

        <div class="dashboard-trend-switch" role="group" aria-label="Choose chart time period">
            @foreach(['weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $period => $label)
                <button
                    type="button"
                    class="dashboard-trend-option {{ $trend['period'] === $period ? 'is-active' : '' }}"
                    wire:click="setProcessingTrendPeriod('{{ $period }}')"
                    aria-pressed="{{ $trend['period'] === $period ? 'true' : 'false' }}"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @php
        $chartLeft = 64;
        $chartRight = 798;
        $chartTop = 16;
        $chartBottom = 350;
        $chartHeight = $chartBottom - $chartTop;
        $tickCount = min(4, max(1, $trend['maximum']));
        $ceiling = max(1, (int) ceil($trend['maximum'] / $tickCount) * $tickCount);
        $slotWidth = ($chartRight - $chartLeft) / max(1, count($trend['buckets']));
        $barWidth = min(36, $slotWidth * 0.58);
    @endphp

    <svg
        class="processing-trend-line dashboard-activity-chart"
        viewBox="0 0 800 400"
        preserveAspectRatio="none"
        role="img"
        aria-label="{{ $trend['chartLabel'] }}"
    >
        @for($tick = 0; $tick <= $tickCount; $tick++)
            @php
                $value = (int) round($ceiling * $tick / $tickCount);
                $y = $chartBottom - $chartHeight * $tick / $tickCount;
            @endphp
            <text x="38" y="{{ $y + 3 }}" text-anchor="end" class="processing-trend-label">{{ $value }}</text>
            <line x1="{{ $chartLeft }}" y1="{{ $y }}" x2="{{ $chartRight }}" y2="{{ $y }}" class="processing-trend-grid" />
        @endfor

        <line x1="{{ $chartLeft }}" y1="{{ $chartTop }}" x2="{{ $chartLeft }}" y2="{{ $chartBottom }}" class="processing-trend-axis" />
        <line x1="{{ $chartLeft }}" y1="{{ $chartBottom }}" x2="{{ $chartRight }}" y2="{{ $chartBottom }}" class="processing-trend-axis" />

        @foreach($trend['buckets'] as $index => $bucket)
            @php
                $barHeight = $bucket['count'] / $ceiling * $chartHeight;
                $x = $chartLeft + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
                $y = $chartBottom - $barHeight;
                $labelX = $chartLeft + ($slotWidth * $index) + ($slotWidth / 2);
            @endphp
            <g>
                <title>{{ $bucket['tooltip'] }}: {{ $bucket['count'] }} {{ \Illuminate\Support\Str::plural('document', $bucket['count']) }}</title>
                @if($bucket['count'] > 0)
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ max(1, $barHeight) }}" rx="2" class="processing-trend-bar" />
                @endif
                <text x="{{ $labelX }}" y="381" text-anchor="middle" class="processing-trend-label">{{ $bucket['label'] }}</text>
            </g>
        @endforeach
    </svg>
</section>
