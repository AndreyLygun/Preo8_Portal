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
        $this->createDrxAccount("Positive Techonologies", "ptsecurity");
        $this->createDrxAccount("Saint-Gobain", "sgcp");
        $this->createDrxAccount("АО Юникон", "unicon");
        $this->createDrxAccount("ООО МКТ", "marvel");
    }
}
