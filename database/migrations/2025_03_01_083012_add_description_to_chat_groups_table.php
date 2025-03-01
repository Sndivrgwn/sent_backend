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
    Schema::table('chat_groups', function (Blueprint $table) {
        $table->text('description')->nullable()->after('name'); // Menambahkan kolom description
    });
}

public function down()
{
    Schema::table('chat_groups', function (Blueprint $table) {
        $table->dropColumn('description'); // Menghapus kolom description jika rollback
    });
}
};
