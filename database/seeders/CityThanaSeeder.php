<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds the Dhaka and Chattogram city thanas from database/data/city-thanas.json to the upazilas table,
 * so people in the city can pick their own area instead of a rural upazila.
 * Safe to run more than once: rows are matched on their code, and districts that are not loaded yet are skipped.
 */
class CityThanaSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/city-thanas.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['districts'] as $block) {
            $districtId = DB::table('districts')
                ->join('divisions', 'divisions.id', '=', 'districts.division_id')
                ->where('divisions.name', $block['division'])
                ->where('districts.name', $block['district'])
                ->value('districts.id');

            if ($districtId === null) {
                continue;
            }

            DB::table('upazilas')->insertOrIgnore(array_map(fn (array $thana) => [
                'district_id' => $districtId,
                'name' => $thana['name'],
                'bn_name' => $thana['bn_name'],
                'code' => $thana['code'],
            ], $block['thanas']));
        }
    }
}
