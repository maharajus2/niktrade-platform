<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index(['type', 'last_message_at']);
        });

        Schema::create('conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('muted_until')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'is_archived', 'is_pinned']);
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('type')->default('text');
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('system_event')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('created_at');
            $table->index(['sender_id', 'created_at']);
        });

        Schema::create('message_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('upload');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index(['source', 'related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
