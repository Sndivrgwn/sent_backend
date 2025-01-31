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
            $table->unsignedBigInteger('group_id')->nullable()->after('id'); // Add group_id column
            $table->foreign('group_id')->references('id')->on('chat_groups')->onDelete('cascade'); // Foreign key relation
        });
    }
    
    public function down()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['group_id']); // Drop the foreign key constraint
            $table->dropColumn('group_id'); // Drop the column
        });
    }
    
};
