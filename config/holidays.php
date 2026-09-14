<?php

return [
    // Public Philippine holiday calendar; fetched at most once daily.
    'feed_url' => env('PH_HOLIDAY_FEED_URL', 'https://calendar.google.com/calendar/ical/en.philippines%23holiday%40group.v.calendar.google.com/public/basic.ics'),
    // Verified nationwide dates override the feed for this year, also available offline.
    // Proclamation 1006 (2025), Proclamation 1189 (2026), Proclamation 1264 (2026).
    'official' => [
        2026 => [
            '2026-01-01' => "New Year's Day",
            '2026-02-17' => 'Chinese New Year',
            '2026-02-25' => 'EDSA People Power Anniversary (special working day)',
            '2026-03-20' => "Eid'l Fitr",
            '2026-04-02' => 'Maundy Thursday',
            '2026-04-03' => 'Good Friday',
            '2026-04-04' => 'Black Saturday',
            '2026-04-09' => 'Araw ng Kagitingan',
            '2026-05-01' => 'Labor Day',
            '2026-05-27' => "Eid'l Adha",
            '2026-06-12' => 'Independence Day',
            '2026-08-21' => 'Ninoy Aquino Day',
            '2026-08-31' => 'National Heroes Day',
            '2026-11-01' => "All Saints' Day",
            '2026-11-02' => "All Souls' Day",
            '2026-11-30' => 'Bonifacio Day',
            '2026-12-08' => 'Feast of the Immaculate Conception',
            '2026-12-24' => 'Christmas Eve',
            '2026-12-25' => 'Christmas Day',
            '2026-12-30' => 'Rizal Day',
            '2026-12-31' => 'Last Day of the Year',
        ],
    ],
];
