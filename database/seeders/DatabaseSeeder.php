<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\DrxAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    protected function createDrxAccount($name, $drx_login, $password = '31185') {
        $DrxAccount = DrxAccount::FirstOrCreate(['name' => $name], ['drx_login' => $drx_login, 'drx_password' => $password]);
        User::where('email',  'like', "%$drx_login%")->update(['drx_account_id' => $DrxAccount->id]);
        echo "$name created\n";
    }

    public function run(): void
    {
        $this->createDrxAccount("Ricoh Rus", "ricoh");
        $this->createDrxAccount("УК Sawatzky", "sawatzky");
        $this->createDrxAccount("БЦ Прео8", "preo8");
        $this->createDrxAccount("Saint-Gobain", "sgcp");
//
//
//        $DrxAccount = DrxAccount::FirstOrCreate(["name" => "УК Sawatzky"], ["drx_login" => "sawatzky", "drx_password" => "31185"]);
//        User::where(["name" => "gvozdika"])->update(["drx_account_id" => $DrxAccount->id]);
//        echo "УК Sawatzky\n";
//
//        $DrxAccount = DrxAccount::FirstOrCreate(["name" => "БЦ Прео-8"], ["drx_login" => "preo8", "drx_password" => "31185"]);
//        User::where(["name" => ""])->update(["drx_account_id" => $DrxAccount->id]);
//        echo "Преo8\n";
//
//        $DrxAccount = DrxAccount::FirstOrCreate(["name" => "Saint-Gobain"], ["drx_login" => "sgcp", "drx_password" => "31185"]);
//        User::where(["name" => ""])->update(["drx_account_id" => $DrxAccount->id]);
//        echo "Преo8\n";

    }
}
