<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = \App\Models\User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1234567890',
            'date_of_birth' => '1980-01-01',
            'gender' => 'male',
        ]);

        // Create Doctor
        $doctor = \App\Models\User::create([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'doctor@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'doctor',
            'phone' => '+1234567891',
            'date_of_birth' => '1975-05-15',
            'gender' => 'male',
        ]);

        \App\Models\Doctor::create([
            'user_id' => $doctor->id,
            'license_number' => 'DOC000001',
            'specialization' => 'Internal Medicine',
            'qualifications' => 'MD from Harvard Medical School',
            'years_of_experience' => 15,
            'bio' => 'Experienced internal medicine physician with 15 years of practice.',
            'availability_hours' => [
                'monday' => ['09:00', '17:00'],
                'tuesday' => ['09:00', '17:00'],
                'wednesday' => ['09:00', '17:00'],
                'thursday' => ['09:00', '17:00'],
                'friday' => ['09:00', '17:00'],
            ],
            'consultation_fee' => 150.00,
            'telehealth_enabled' => true,
        ]);

        // Create Patient
        $patient = \App\Models\User::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'patient@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'patient',
            'phone' => '+1234567892',
            'date_of_birth' => '1990-10-20',
            'gender' => 'female',
        ]);

        \App\Models\Patient::create([
            'user_id' => $patient->id,
            'patient_id' => 'PAT000001',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '+1234567893',
            'insurance_provider' => 'Blue Cross Blue Shield',
            'insurance_policy_number' => 'BCBS123456789',
            'medical_history' => 'No significant past medical history.',
            'allergies' => 'Penicillin',
            'current_medications' => 'Multivitamin daily',
            'blood_type' => 'O+',
            'height' => 165.00,
            'weight' => 65.00,
        ]);

        // Create Nurse
        $nurse = \App\Models\User::create([
            'first_name' => 'Mary',
            'last_name' => 'Johnson',
            'email' => 'nurse@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'nurse',
            'phone' => '+1234567894',
            'date_of_birth' => '1985-03-12',
            'gender' => 'female',
        ]);

        // Create Receptionist
        $receptionist = \App\Models\User::create([
            'first_name' => 'Sarah',
            'last_name' => 'Wilson',
            'email' => 'receptionist@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'receptionist',
            'phone' => '+1234567895',
            'date_of_birth' => '1992-07-08',
            'gender' => 'female',
        ]);
    }
}
