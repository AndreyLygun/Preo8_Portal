<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drx_accounts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('Name')->comment("Название компании");
            $table->string('DRX_Login')->comment("Логин в DRX")->unique();
            $table->string('DRX_Password')->comment("Пароль в DRX");
            $table->longText('emails')->comment("Список допустимых e-mail пользователей")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drx_accounts');
    }
};
