<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@admin-ui.cn',
            'password' => 'admin',
            'active'   => true,
            'resent'   => 0
        ]);

        User::create([
            'name'     => 'Developer',
            'email'    => 'developer@admin-ui.cn',
            'password' => 'user',
            'active'   => true,
            'resent'   => 0
        ]);
    }
}
