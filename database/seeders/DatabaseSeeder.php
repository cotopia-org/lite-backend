<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

       /** @var User $admin */
        $admin = User::create([
            'name'       => 'Super Admin User',
            'email'      => 'admin@example.com',
            'username'   => 'admin',
            'password'   => Hash::make('123@123')
        ]);
    //    $admin->roles()->attach(Role::whereName('super-admin')->first());
    }
}
