<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('broadcast_id')->nullable()->after('id');
            $table->foreign('broadcast_id')->references('id')->on('broadcasts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['broadcast_id']);
            $table->dropColumn('broadcast_id');
        });
    }
};
