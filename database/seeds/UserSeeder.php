<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'first_name' => 'Admin',
            'last_name'  => '',
            'email'      => 'admin@admin-ui.cn',
            'password'   => 'admin',
            'active'     => true,
            'resent'     => 0
        ]);

        User::create([
            'first_name' => 'Developer',
            'last_name'  => '',
            'email'      => 'developer@admin-ui.cn',
            'password'   => 'user',
            'active'     => true,
            'resent'     => 0
        ]);
    }
}
