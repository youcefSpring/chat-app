<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('organization_id');
            $table->index(['organization_id', 'email']);
            $table->index('status');
        });

        // Channels indexes
        Schema::table('channels', function (Blueprint $table) {
            $table->index('organization_id');
            $table->index(['organization_id', 'type']);
        });

        // Channel members indexes
        Schema::table('channel_members', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('channel_id');
        });

        // Messages indexes
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['channel_id', 'created_at']);
            $table->index('sender_id');
            $table->index('reply_to_message_id');
            $table->index('deleted_at');
        });

        // Attachments indexes
        Schema::table('attachments', function (Blueprint $table) {
            $table->index('message_id');
            $table->index('uploader_id');
        });

        // Reactions indexes
        Schema::table('reactions', function (Blueprint $table) {
            $table->index('message_id');
            $table->index('user_id');
        });

        // Calls indexes
        Schema::table('calls', function (Blueprint $table) {
            $table->index('channel_id');
            $table->index('status');
        });

        // Call participants indexes
        Schema::table('call_participants', function (Blueprint $table) {
            $table->index('call_id');
            $table->index('user_id');
        });

        // Audit logs indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });

        // Notifications indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Full-text search indexes (PostgreSQL specific)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX idx_messages_body_fulltext ON messages USING gin(to_tsvector(\'english\', body))');
            DB::statement('CREATE INDEX idx_channels_name_fulltext ON channels USING gin(to_tsvector(\'english\', name))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text search indexes
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_messages_body_fulltext');
            DB::statement('DROP INDEX IF EXISTS idx_channels_name_fulltext');
        }

        // Drop regular indexes (Laravel will handle this automatically when dropping columns)
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['organization_id', 'email']);
            $table->dropIndex(['status']);
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['organization_id', 'type']);
        });

        Schema::table('channel_members', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['channel_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['channel_id', 'created_at']);
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['reply_to_message_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
            $table->dropIndex(['uploader_id']);
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['channel_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('call_participants', function (Blueprint $table) {
            $table->dropIndex(['call_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
            $table->dropIndex(['actor_id', 'created_at']);
            $table->dropIndex(['target_type', 'target_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};