<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\User;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::create([
            'name' => 'RunRight Test Company',
            'slug' => 'runright-test',
            'plan' => 'founder',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@runright.dev',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);
    }
}
