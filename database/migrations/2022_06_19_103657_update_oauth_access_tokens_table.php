<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $table = "oauth_access_tokens";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (!Schema::hasColumn($this->table, 'revoked_at')) {
                    $table->timestamp('revoked_at')->nullable();
                }
                if (!Schema::hasColumn('fcm_token', 'device_type')) {
                    $table->string('fcm_token')->nullable();
                    $table->enum('device_type', ['android', 'ios', 'web'])->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (Schema::hasColumn($this->table, 'revoked_at')) {
                    $table->dropColumn('revoked_at');
                }
                if (Schema::hasColumn('fcm_token', 'device_type')) {
                    $table->dropColumn('fcm_token');
                    $table->dropColumn('device_type');
                }
            });
        }
    }
};
