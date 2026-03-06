<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use DatabaseMigrations;

    public function test_all_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('notification_batches'));
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasTable('delivery_attempts'));
        $this->assertTrue(Schema::hasTable('idempotency_keys'));
    }

    public function test_delivery_attempts_has_no_updated_at(): void
    {
        $this->assertFalse(Schema::hasColumn('delivery_attempts', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('delivery_attempts', 'created_at'));
    }

    public function test_notifications_has_required_lifecycle_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('notifications', [
            'queued_at', 'processing_at', 'sent_at',
            'failed_at', 'canceled_at', 'scheduled_at',
        ]));
    }

    public function test_idempotency_key_is_unique(): void
    {
        DB::table('idempotency_keys')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'key' => 'test-key', 'request_hash' => 'abc',
            'expires_at' => now()->addDay(),
        ]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('idempotency_keys')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'key' => 'test-key', 'request_hash' => 'xyz',
            'expires_at' => now()->addDay(),
        ]);
    }
}
