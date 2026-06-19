<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dlopez@moira.test'],
            [
                'first_name' => 'Daniel',
                'last_name'  => 'López',
                'username'   => 'dlopez',
                'password'   => bcrypt('123123Qwe'),
                'role'       => Role::SuperAdmin,
            ]
        );
    }
}
