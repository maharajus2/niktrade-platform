<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\Messenger\MessengerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_chat_is_reused_and_unread_count_clears_on_read(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $service = app(MessengerService::class);

        $conversation = $service->findOrCreateDirect($sender, $recipient, 'Hello');
        $sameConversation = $service->findOrCreateDirect($recipient, $sender);

        $this->assertTrue($conversation->is($sameConversation));
        $this->assertSame(1, Conversation::query()->count());
        $this->assertSame(1, $conversation->fresh(['participants'])->unreadCountFor($recipient));

        $service->markRead($conversation, $recipient);

        $this->assertSame(0, $conversation->fresh(['participants'])->unreadCountFor($recipient));
    }

    public function test_non_participant_cannot_download_attachment(): void
    {
        Storage::fake('messenger_attachments');

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();

        $conversation = app(MessengerService::class)->findOrCreateDirect($sender, $recipient);
        app(MessengerService::class)->sendMessage(
            $conversation,
            $sender,
            'Document',
            UploadedFile::fake()->create('report.pdf', 64, 'application/pdf'),
        );

        $attachment = MessageAttachment::query()->firstOrFail();

        $this
            ->actingAs($outsider)
            ->get(route('admin.messenger.attachments.download', $attachment))
            ->assertForbidden();

        $this
            ->actingAs($recipient)
            ->get(route('admin.messenger.attachments.download', $attachment))
            ->assertOk();
    }

    public function test_media_attachment_can_be_previewed_by_participant_only(): void
    {
        Storage::fake('messenger_attachments');

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();

        $conversation = app(MessengerService::class)->findOrCreateDirect($sender, $recipient);
        app(MessengerService::class)->sendMessage(
            $conversation,
            $sender,
            'Photo',
            UploadedFile::fake()->create('photo.jpg', 64, 'image/jpeg'),
        );

        $attachment = MessageAttachment::query()->firstOrFail();

        $this->assertTrue($attachment->isImage());
        $this->assertSame('image', $attachment->kind);

        $this
            ->actingAs($outsider)
            ->get(route('admin.messenger.attachments.preview', $attachment))
            ->assertForbidden();

        $this
            ->actingAs($recipient)
            ->get(route('admin.messenger.attachments.preview', $attachment))
            ->assertOk();
    }

    public function test_documents_are_downloadable_but_not_inline_previewable(): void
    {
        Storage::fake('messenger_attachments');

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conversation = app(MessengerService::class)->findOrCreateDirect($sender, $recipient);
        app(MessengerService::class)->sendMessage(
            $conversation,
            $sender,
            'Document',
            UploadedFile::fake()->create('report.pdf', 64, 'application/pdf'),
        );

        $attachment = MessageAttachment::query()->firstOrFail();

        $this->assertTrue($attachment->isPdf());
        $this->assertFalse($attachment->isPreviewable());
        $this->assertSame('pdf', $attachment->kind);

        $this
            ->actingAs($recipient)
            ->get(route('admin.messenger.attachments.preview', $attachment))
            ->assertNotFound();

        $this
            ->actingAs($recipient)
            ->get(route('admin.messenger.attachments.download', $attachment))
            ->assertOk();
    }
}
