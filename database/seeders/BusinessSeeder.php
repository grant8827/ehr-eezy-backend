<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo businesses
        $business1 = \App\Models\Business::create([
            'name' => 'HealthCare Plus Clinic',
            'slug' => 'healthcare-plus-clinic',
            'business_type' => 'clinic',
            'description' => 'A comprehensive healthcare clinic providing family medicine, diagnostics, and specialized care.',
            'email' => 'info@healthcareplus.com',
            'phone' => '+1-555-0123',
            'address' => '123 Health Street, Medical District, New York, NY 10001',
            'website' => 'https://healthcareplus.com',
            'license_number' => 'HCP-2025-001',
            'subscription_plan' => 'premium',
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $business2 = \App\Models\Business::create([
            'name' => 'Wellness Therapy Center',
            'slug' => 'wellness-therapy-center',
            'business_type' => 'therapy_center',
            'description' => 'Specialized therapy center offering physical therapy, occupational therapy, and speech therapy.',
            'email' => 'contact@wellnesstherapy.com',
            'phone' => '+1-555-0456',
            'address' => '456 Wellness Ave, Therapy District, Los Angeles, CA 90210',
            'website' => 'https://wellnesstherapy.com',
            'license_number' => 'WTC-2025-002',
            'subscription_plan' => 'basic',
            'subscription_expires_at' => now()->addMonths(6),
            'is_active' => true,
        ]);

        // Update existing users to belong to the first business
        \App\Models\User::whereNull('business_id')->update([
            'business_id' => $business1->id
        ]);

        // Set the first admin as business owner
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->update(['is_business_owner' => true]);
        }

        // Update existing patients to belong to the business
        \App\Models\Patient::whereNull('business_id')->update([
            'business_id' => $business1->id
        ]);

        // Update existing doctors to belong to the business
        \App\Models\Doctor::whereNull('business_id')->update([
            'business_id' => $business1->id
        ]);

        // Create sample staff for the second business
        $business2Admin = \App\Models\User::create([
            'first_name' => 'Sarah',
            'last_name' => 'Thompson',
            'email' => 'admin@wellnesstherapy.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1-555-0789',
            'date_of_birth' => '1985-03-15',
            'gender' => 'female',
            'business_id' => $business2->id,
            'is_business_owner' => true,
            'is_active' => true,
        ]);

        // Create a therapist for the second business
        $therapist = \App\Models\User::create([
            'first_name' => 'Michael',
            'last_name' => 'Rodriguez',
            'email' => 'therapist@wellnesstherapy.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'therapist',
            'phone' => '+1-555-0321',
            'date_of_birth' => '1988-07-22',
            'gender' => 'male',
            'business_id' => $business2->id,
            'license_number' => 'PT-2025-001',
            'specialization' => 'Physical Therapy',
            'qualifications' => 'DPT from University of Southern California',
            'years_of_experience' => 8,
            'is_active' => true,
        ]);

        // Create therapist profile
        \App\Models\Therapist::create([
            'user_id' => $therapist->id,
            'business_id' => $business2->id,
            'license_number' => 'PT-2025-001',
            'specialization' => 'Physical Therapy & Sports Medicine',
            'qualifications' => 'Doctor of Physical Therapy (DPT) from University of Southern California, Board Certified Orthopedic Clinical Specialist',
            'years_of_experience' => 8,
            'bio' => 'Experienced physical therapist specializing in sports medicine, orthopedic rehabilitation, and manual therapy techniques.',
            'availability_hours' => [
                'monday' => ['08:00', '17:00'],
                'tuesday' => ['08:00', '17:00'],
                'wednesday' => ['08:00', '17:00'],
                'thursday' => ['08:00', '17:00'],
                'friday' => ['08:00', '16:00'],
            ],
            'consultation_fee' => 120.00,
            'telehealth_enabled' => true,
            'therapy_types' => ['Physical Therapy', 'Sports Rehabilitation', 'Manual Therapy', 'Exercise Prescription'],
            'certifications' => 'Orthopedic Clinical Specialist (OCS), Certified Strength & Conditioning Specialist (CSCS)',
        ]);

        echo "✅ Created " . \App\Models\Business::count() . " businesses\n";
        echo "✅ Updated existing users with business relationships\n";
        echo "✅ Created sample therapist and staff\n";
    }
}
