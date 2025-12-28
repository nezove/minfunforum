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
        Schema::create('wall_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // автор поста
            $table->foreignId('wall_owner_id')->constrained('users')->onDelete('cascade'); // владелец стены
            $table->text('content');
            $table->timestamp('edited_at')->nullable();
            $table->unsignedInteger('edit_count')->default(0);
            $table->timestamps();

            $table->index('wall_owner_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wall_posts');
    }
};
