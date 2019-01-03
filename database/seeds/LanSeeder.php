<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Zeropingheroes\Lanager\Lan;
use Zeropingheroes\Lanager\Venue;

class LanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // don't seed if table is not empty
        if (Lan::count()) {
            return;
        }

        Lan::create([
            'venue_id' => Venue::first()->id,
            'name' => __('seeder.example-lan'),
            'start' => Carbon::parse('next Friday')->addHours(18),
            'end' => Carbon::parse('next Sunday')->addHours(18),
            'published' => true,
        ]);
    }
}
