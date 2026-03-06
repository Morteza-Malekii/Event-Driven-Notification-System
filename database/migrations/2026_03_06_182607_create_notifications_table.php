<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_id')->nullable()->constrained('notification_batches')->cascadeOnDelete();
            $table->string('channel');
            $table->string('priority')->default('normal');
            $table->string('status')->default('pending');
            $table->string('recipient');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->string('idempotency_key')->nullable()->index();
            $table->tinyInteger('max_attempts')->default(3);
            $table->tinyInteger('attempt_count')->default(0);
            $table->string('correlation_id')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'channel', 'priority', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
