<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@endure-net.local')],
            [
                'nom'       => env('ADMIN_NOM',    'Administrateur'),
                'prenom'    => env('ADMIN_PRENOM', ''),
                'password'  => Hash::make(env('ADMIN_PASSWORD', 'changeme123')),
                'role'      => 'super_admin',
                'type_user' => 'admin',
                'active'    => 1,
            ]
        );
    }
}
