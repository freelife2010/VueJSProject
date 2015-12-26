<?php

use Illuminate\Database\Seeder;

class ScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $scopes = [
            'Account Management' => 'users',
            'Billing Management' => 'billing',
            'PBX Management'     => 'pbx',
            'SMS Management'     => 'sms'
        ];

        foreach ($scopes as $name => $scope) {
            DB::table('oauth_scopes')->insert(
                [
                    'id'          => $scope,
                    'description' => $name,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s')
                ]);
        }
    }
}
