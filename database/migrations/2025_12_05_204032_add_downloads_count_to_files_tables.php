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
        Schema::table('topic_files', function (Blueprint $table) {
            $table->unsignedInteger('downloads_count')->default(0)->after('mime_type');
        });

        Schema::table('post_files', function (Blueprint $table) {
            $table->unsignedInteger('downloads_count')->default(0)->after('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_files', function (Blueprint $table) {
            $table->dropColumn('downloads_count');
        });

        Schema::table('post_files', function (Blueprint $table) {
            $table->dropColumn('downloads_count');
        });
    }
};
