<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Sunday Worship Service',
                'event_date' => Carbon::now()->addDays(1),
                'location' => 'Main Sanctuary',
                'event_type' => 'worship',
            ],
            [
                'title' => 'Youth Fellowship',
                'event_date' => Carbon::now()->addDays(7),
                'location' => 'Youth Hall',
                'event_type' => 'fellowship',
            ],
            [
                'title' => 'Bible Study',
                'event_date' => Carbon::now()->addDays(3),
                'location' => 'Room 101',
                'event_type' => 'study',
            ],
            [
                'title' => 'Prayer Meeting',
                'event_date' => Carbon::now()->addDays(5),
                'location' => 'Prayer Room',
                'event_type' => 'prayer',
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
}
