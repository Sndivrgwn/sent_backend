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
        $table->unsignedBigInteger('receiver_id')->nullable()->change(); // Make receiver_id nullable
    });
}

public function down()
{
    Schema::table('chat_messages', function (Blueprint $table) {
        $table->unsignedBigInteger('receiver_id')->nullable(false)->change(); // Restore if needed
    });
}

};
