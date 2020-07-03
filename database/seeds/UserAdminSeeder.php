<?php

use Illuminate\Database\Seeder;
use App\User;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$pass = \Hash::make('admindevelop');

    	User::create([
    		'username' => 'jhayrebua',
    		'password' => $pass,
    		'type' => 'admin',
        'department' => 'it',
    		'npd_access' => 1,
    		'pmms_access' => 1,
    		'cposms_access' => 1,
    		'pjoms_access' => 1,
    		'cims_access' => 1,
    		'wims_access' => 1,
    		'psms_access' => 1,
        'firstname' => 'Jesson Jei',
        'middleinitial' => 'Mendoza',
        'lastname' => 'Rebua',
        'gender' => 'Male',
    		'email' => 'jhayrebua123@gmail.com',
    		'position' => 'Web developer',
    		'signature' => null,
    	]);
    }
  }
