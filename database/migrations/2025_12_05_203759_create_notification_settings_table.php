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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Уведомления о новых ответах
            $table->boolean('notify_reply')->default(true);
            $table->boolean('notify_reply_to_post')->default(true);

            // Уведомления о упоминаниях
            $table->boolean('notify_mention')->default(true);
            $table->boolean('notify_mention_topic')->default(true);

            // Уведомления о лайках
            $table->boolean('notify_like_topic')->default(true);
            $table->boolean('notify_like_post')->default(true);

            // Модераторские уведомления
            $table->boolean('notify_topic_deleted')->default(true);
            $table->boolean('notify_post_deleted')->default(true);
            $table->boolean('notify_topic_moved')->default(true);

            // Уведомления о банах
            $table->boolean('notify_bans')->default(true);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
