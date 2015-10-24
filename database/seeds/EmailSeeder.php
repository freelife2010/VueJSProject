<?php

use App\Models\Email;
use Illuminate\Database\Seeder;

class EmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Email::create([
            'type'    => 'authorization',
            'subject' => 'Authorization request from AdminUI',
            'content' => view('emails.activation')
        ]);

        Email::create([
            'type'    => 'confirmation',
            'subject' => 'Confirmation from AdminUI',
            'content' => view('emails.confirmation')
        ]);
    }
}
