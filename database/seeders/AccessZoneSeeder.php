<?php

namespace Database\Seeders;

use App\Models\AccessZone;
use Illuminate\Database\Seeder;

class AccessZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['code' => 'club',   'name' => 'Club',      'is_active' => true],
            ['code' => 'pileta', 'name' => 'Pileta',    'is_active' => true],
            ['code' => 'gym',    'name' => 'Gimnasio',  'is_active' => true],
        ];

        foreach ($zones as $zone) {
            AccessZone::updateOrCreate(
                ['code' => $zone['code']],
                $zone
            );
        }
    }
}
