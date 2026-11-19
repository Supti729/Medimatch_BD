<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

/**
 * A small starter catalogue of common medicines in Bangladesh, with brand names.
 *
 * IMPORTANT: this is demonstration data for the prototype. A real catalogue must be built
 * from the DGDA registered medicine list and checked by a pharmacist, including which
 * medicines need a prescription and which must never be listed.
 *
 * Safe to run more than once (updateOrCreate).
 */
class MedicineCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // [generic, strength, form, prescription?, [[brand, manufacturer], ...]]
        $allowed = [
            ['Paracetamol', '500 mg', 'Tablet', false, [['Napa', 'Beximco Pharmaceuticals'], ['Ace', 'Square Pharmaceuticals']]],
            ['Paracetamol', '665 mg', 'Tablet', false, [['Napa Extend', 'Beximco Pharmaceuticals']]],
            ['Paracetamol', '120 mg / 5 ml', 'Syrup', false, [['Napa', 'Beximco Pharmaceuticals'], ['Ace', 'Square Pharmaceuticals']]],
            ['Paracetamol + Caffeine', '500 mg + 65 mg', 'Tablet', false, [['Ace Plus', 'Square Pharmaceuticals'], ['Napa Extra', 'Beximco Pharmaceuticals']]],
            ['Omeprazole', '20 mg', 'Capsule', false, [['Seclo', 'Square Pharmaceuticals'], ['Losectil', 'Eskayef Pharmaceuticals']]],
            ['Omeprazole', '40 mg', 'Capsule', false, [['Seclo', 'Square Pharmaceuticals']]],
            ['Esomeprazole', '20 mg', 'Capsule', false, [['Sergel', 'Healthcare Pharmaceuticals']]],
            ['Esomeprazole', '20 mg', 'Tablet', false, [['Maxpro', 'Renata']]],
            ['Fexofenadine', '120 mg', 'Tablet', false, [['Fexo', 'Square Pharmaceuticals']]],
            ['Cetirizine', '10 mg', 'Tablet', false, [['Alatrol', 'Square Pharmaceuticals']]],
            ['Ketotifen', '1 mg', 'Tablet', false, [['Tofen', 'Beximco Pharmaceuticals']]],
            ['Oral Rehydration Salts', '10.25 g', 'Sachet', false, [['ORSaline-N', 'SMC']]],
            ['Montelukast', '10 mg', 'Tablet', true, [['Monas', 'ACME Laboratories']]],
            ['Amlodipine', '5 mg', 'Tablet', true, [['Amdocal', 'Beximco Pharmaceuticals']]],
            ['Losartan Potassium', '50 mg', 'Tablet', true, [['Osartil', 'Incepta Pharmaceuticals']]],
            ['Metformin', '500 mg', 'Tablet', true, [['Comet', 'Square Pharmaceuticals']]],
            ['Salbutamol', '100 mcg', 'Inhaler', true, [['Azmasol', 'Beximco Pharmaceuticals']]],
            ['Amoxicillin', '500 mg', 'Capsule', true, [['Moxacil', 'Square Pharmaceuticals']]],
        ];

        // Medicines that must never be listed, with the reason shown to users and moderators.
        $restricted = [
            ['Insulin (all types)', 'Any', 'Injection', 'Needs refrigeration (cold chain)'],
            ['Diazepam', '5 mg', 'Tablet', 'Controlled medicine'],
            ['Morphine', '10 mg', 'Tablet', 'Controlled medicine'],
        ];

        foreach ($allowed as [$generic, $strength, $form, $prescription, $brands]) {
            $medicine = Medicine::updateOrCreate(
                ['generic_name' => $generic, 'strength' => $strength, 'dosage_form' => $form],
                ['requires_prescription' => $prescription, 'eligibility' => 'allowed', 'restriction_reason' => null],
            );

            foreach ($brands as [$brand, $manufacturer]) {
                $medicine->aliases()->updateOrCreate(['brand_name' => $brand], ['manufacturer' => $manufacturer]);
            }
        }

        foreach ($restricted as [$generic, $strength, $form, $reason]) {
            Medicine::updateOrCreate(
                ['generic_name' => $generic, 'strength' => $strength, 'dosage_form' => $form],
                ['requires_prescription' => true, 'eligibility' => 'not_allowed', 'restriction_reason' => $reason],
            );
        }
    }
}
