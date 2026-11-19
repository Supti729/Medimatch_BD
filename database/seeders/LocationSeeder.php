<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads Bangladesh divisions, districts and upazilas from database/data/bangladesh-locations.json.
 * Safe to run more than once: it skips loading if divisions already exist.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('divisions')->exists()) {
            return;
        }

        $data = json_decode(file_get_contents(database_path('data/bangladesh-locations.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            foreach ($data['divisions'] as $division) {
                $divisionId = DB::table('divisions')->insertGetId([
                    'name' => $division['name'],
                    'bn_name' => $division['bn_name'],
                    'code' => $division['code'],
                ]);

                foreach ($division['districts'] as $district) {
                    $districtId = DB::table('districts')->insertGetId([
                        'division_id' => $divisionId,
                        'name' => $district['name'],
                        'bn_name' => $district['bn_name'],
                        'code' => $district['code'],
                    ]);

                    DB::table('upazilas')->insert(array_map(fn (array $upazila) => [
                        'district_id' => $districtId,
                        'name' => $upazila['name'],
                        'bn_name' => $upazila['bn_name'],
                        'code' => $upazila['code'],
                    ], $district['upazilas']));
                }
            }
        });
    }
}
