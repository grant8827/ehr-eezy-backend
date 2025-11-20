<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use Illuminate\Database\Seeder;

class PharmacySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacies = [
            [
                'name' => 'CVS Pharmacy - Main Street',
                'license_number' => 'CVS-001-2024',
                'email' => 'mainstreet@cvs.com',
                'phone' => '(555) 123-4567',
                'fax' => '(555) 123-4568',
                'address' => '123 Main Street',
                'city' => 'Springfield',
                'state' => 'IL',
                'zip_code' => '62701',
                'latitude' => 39.7817,
                'longitude' => -89.6501,
                'operating_hours' => [
                    'monday' => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                    'tuesday' => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                    'wednesday' => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                    'thursday' => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                    'friday' => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'sunday' => ['open' => '10:00', 'close' => '18:00', 'closed' => false],
                ],
                'pharmacist_in_charge' => 'Dr. Sarah Johnson',
                'pharmacist_license' => 'RPH-IL-45678',
                'accepts_electronic_prescriptions' => true,
                'delivers' => true,
                'delivery_notes' => 'Same-day delivery available for orders before 2 PM within 5 miles',
                'accepted_insurances' => ['Blue Cross', 'Aetna', 'United Healthcare', 'Cigna', 'Medicare'],
                'status' => 'active',
            ],
            [
                'name' => 'Walgreens - Oak Avenue',
                'license_number' => 'WAG-002-2024',
                'email' => 'oakave@walgreens.com',
                'phone' => '(555) 234-5678',
                'fax' => '(555) 234-5679',
                'address' => '456 Oak Avenue',
                'city' => 'Springfield',
                'state' => 'IL',
                'zip_code' => '62702',
                'latitude' => 39.7897,
                'longitude' => -89.6543,
                'operating_hours' => [
                    'monday' => ['open' => '07:00', 'close' => '23:00', 'closed' => false],
                    'tuesday' => ['open' => '07:00', 'close' => '23:00', 'closed' => false],
                    'wednesday' => ['open' => '07:00', 'close' => '23:00', 'closed' => false],
                    'thursday' => ['open' => '07:00', 'close' => '23:00', 'closed' => false],
                    'friday' => ['open' => '07:00', 'close' => '23:00', 'closed' => false],
                    'saturday' => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                    'sunday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                ],
                'pharmacist_in_charge' => 'Dr. Michael Chen',
                'pharmacist_license' => 'RPH-IL-56789',
                'accepts_electronic_prescriptions' => true,
                'delivers' => false,
                'accepted_insurances' => ['Blue Cross', 'Aetna', 'United Healthcare', 'Humana'],
                'status' => 'active',
            ],
            [
                'name' => 'Rite Aid - Center Street',
                'license_number' => 'RIT-003-2024',
                'email' => 'center@riteaid.com',
                'phone' => '(555) 345-6789',
                'fax' => '(555) 345-6790',
                'address' => '789 Center Street',
                'city' => 'Springfield',
                'state' => 'IL',
                'zip_code' => '62703',
                'latitude' => 39.7950,
                'longitude' => -89.6600,
                'operating_hours' => [
                    'monday' => ['open' => '08:00', 'close' => '21:00', 'closed' => false],
                    'tuesday' => ['open' => '08:00', 'close' => '21:00', 'closed' => false],
                    'wednesday' => ['open' => '08:00', 'close' => '21:00', 'closed' => false],
                    'thursday' => ['open' => '08:00', 'close' => '21:00', 'closed' => false],
                    'friday' => ['open' => '08:00', 'close' => '21:00', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '19:00', 'closed' => false],
                    'sunday' => ['open' => '10:00', 'close' => '17:00', 'closed' => false],
                ],
                'pharmacist_in_charge' => 'Dr. Emily Rodriguez',
                'pharmacist_license' => 'RPH-IL-67890',
                'accepts_electronic_prescriptions' => true,
                'delivers' => true,
                'delivery_notes' => 'Free delivery for orders over $50',
                'accepted_insurances' => ['Medicare', 'Medicaid', 'Blue Cross', 'Cigna'],
                'status' => 'active',
            ],
            [
                'name' => 'Independent Pharmacy - Elm Street',
                'license_number' => 'IND-004-2024',
                'email' => 'elm@indepharmacy.com',
                'phone' => '(555) 456-7890',
                'fax' => '(555) 456-7891',
                'address' => '321 Elm Street',
                'city' => 'Springfield',
                'state' => 'IL',
                'zip_code' => '62704',
                'latitude' => 39.8000,
                'longitude' => -89.6450,
                'operating_hours' => [
                    'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '14:00', 'closed' => false],
                    'sunday' => ['closed' => true],
                ],
                'pharmacist_in_charge' => 'Dr. David Wilson',
                'pharmacist_license' => 'RPH-IL-78901',
                'accepts_electronic_prescriptions' => true,
                'delivers' => true,
                'delivery_notes' => 'Personalized service, compounding available',
                'accepted_insurances' => ['Most major insurances accepted'],
                'status' => 'active',
            ],
            [
                'name' => 'HealthMart Pharmacy',
                'license_number' => 'HM-005-2024',
                'email' => 'info@healthmart.com',
                'phone' => '(555) 567-8901',
                'fax' => '(555) 567-8902',
                'address' => '654 Park Boulevard',
                'city' => 'Springfield',
                'state' => 'IL',
                'zip_code' => '62705',
                'latitude' => 39.7750,
                'longitude' => -89.6700,
                'operating_hours' => [
                    'monday' => ['open' => '08:30', 'close' => '20:00', 'closed' => false],
                    'tuesday' => ['open' => '08:30', 'close' => '20:00', 'closed' => false],
                    'wednesday' => ['open' => '08:30', 'close' => '20:00', 'closed' => false],
                    'thursday' => ['open' => '08:30', 'close' => '20:00', 'closed' => false],
                    'friday' => ['open' => '08:30', 'close' => '20:00', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'sunday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
                ],
                'pharmacist_in_charge' => 'Dr. Lisa Anderson',
                'pharmacist_license' => 'RPH-IL-89012',
                'accepts_electronic_prescriptions' => true,
                'delivers' => false,
                'accepted_insurances' => ['Blue Cross', 'Aetna', 'United Healthcare', 'Medicare', 'Medicaid'],
                'status' => 'active',
            ],
        ];

        foreach ($pharmacies as $pharmacy) {
            Pharmacy::create($pharmacy);
        }

        $this->command->info('Sample pharmacies created successfully!');
    }
}
