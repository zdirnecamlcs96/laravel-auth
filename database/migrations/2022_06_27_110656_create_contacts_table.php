<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('ownerable');
            $table->bigInteger('country_id')->default(0);
            $table->string('number');
            $table->bigInteger('verify_code');
            $table->string('ref')->nullable();
            $table->longText('response')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
};
