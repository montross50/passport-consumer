<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPassportConsumerColumnsToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('passport-consumer.user_table'), function (Blueprint $table) {
            if (config('passport-consumer.passport_location') !== 'local') {
                $remoteUserIdentifier = config('passport-consumer.remote_user_identifier');
                if (!Schema::hasColumn(config('passport-consumer.user_table'), $remoteUserIdentifier)) {
                    $table->string($remoteUserIdentifier)->nullable()->unique();
                }
                $table->string('api_token')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('passport-consumer.user_table'), function (Blueprint $table) {
            if (config('passport-consumer.passport_location') !== 'local' && Schema::hasColumn(config('passport-consumer.user_table'), 'api_token')) {
                $table->dropColumn('api_token');
            }
        });

        Schema::table(config('passport-consumer.user_table'), function (Blueprint $table) {

            if (config('passport-consumer.passport_location') !== 'local') {
                $remoteUserIdentifier = config('passport-consumer.remote_user_identifier');
                if (config('passport-consumer.remove_remote_user_identifier_on_rollback') && Schema::hasColumn(config('passport-consumer.user_table'), $remoteUserIdentifier)) {
                    $table->dropColumn($remoteUserIdentifier);
                }
            }
        });
    }
}
