<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\User;

use App\Library\Helper;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
    */
    public function run()
    {
        $user = User::create([
            "username"=>"admin",
            "email"=>"admin@email.com",
            "password"=>bcrypt("admin")
        ]);

        $account_number = $ref = Helper::ref_number("A",20);
        $account = Account::create([
            "user_id"=>$user->id,
            "account_number"=>$account_number,
            "first_name"=>"super",
            "middle_name"=>"",
            "last_name"=>"admin"
        ]);

        $user->addRole("admin");
    }
}
