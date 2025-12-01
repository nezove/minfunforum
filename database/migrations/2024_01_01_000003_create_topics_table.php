<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('replies_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->integer('views')->default(0);
            $table->boolean('is_closed')->default(false);
            $table->string('tags')->nullable();
            $table->string('pin_type')->default('none');
            $table->timestamp('edited_at')->nullable();
            $table->integer('edit_count')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('last_activity_at');
            $table->index('pin_type');
            $table->index('is_closed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
