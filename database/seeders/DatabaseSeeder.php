<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Administrator PKBM',
            'email' => 'admin@pkbm.com',
            'role' => 'admin',
        ]);

        $year = \App\Models\AcademicYear::create([
            'name' => '2024/2025',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_active' => true,
            'status' => 'open',
        ]);

        $level = \App\Models\Level::create([
            'name' => 'Paket C',
            'type' => 'subject_teacher',
        ]);

        \App\Models\Classroom::create([
            'name' => 'Kelas 10-A',
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
        ]);

        $categories = [
            ['name' => 'Tugas', 'weight' => 20],
            ['name' => 'Kuis', 'weight' => 10],
            ['name' => 'UTS', 'weight' => 30],
            ['name' => 'UAS', 'weight' => 40],
        ];

        foreach ($categories as $cat) {
            \App\Models\ScoreCategory::create($cat);
        }

        $students = [
            ['name' => 'Budi Santoso', 'email' => 'budi@pkbm.com'],
            ['name' => 'Siti Aminah', 'email' => 'siti@pkbm.com'],
            ['name' => 'Agus Wahyudi', 'email' => 'agus@pkbm.com'],
        ];

        foreach ($students as $s) {
            $user = User::factory()->create([
                'name' => $s['name'],
                'email' => $s['email'],
                'role' => 'siswa',
            ]);

            $profile = \App\Models\StudentProfile::create([
                'classroom_id' => 1,
            ]);

            $user->profiles()->create([
                'profileable_id' => $profile->id,
                'profileable_type' => \App\Models\StudentProfile::class,
            ]);
        }
    }
}
