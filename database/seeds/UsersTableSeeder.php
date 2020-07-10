<?php

use App\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();

        User::create([
            'name' => 'Shane Poppleton',
            'email' => 'shane@alphasg.com.au',
            'password' => Hash::make('secret')
        ]);

    }
}
