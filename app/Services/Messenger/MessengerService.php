<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MessengerService
{
    public function findOrCreateDirect(User $actor, User $recipient, ?string $firstMessage = null): Conversation
    {
        if ((int) $actor->id === (int) $recipient->id) {
            throw ValidationException::withMessages(['directUserId' => 'Нельзя создать чат с самим собой.']);
        }

        $existing = Conversation::query()
            ->where('type', Conversation::TYPE_DIRECT)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $actor->id))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $recipient->id))
            ->withCount('participants')
            ->get()
            ->first(fn (Conversation $conversation): bool => (int) $conversation->participants_count === 2);

        if ($existing instanceof Conversation) {
            if (filled($firstMessage)) {
                $this->sendMessage($existing, $actor, $firstMessage);
            }

            return $existing;
        }

        return DB::transaction(function () use ($actor, $recipient, $firstMessage): Conversation {
            $conversation = Conversation::query()->create([
                'type' => Conversation::TYPE_DIRECT,
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'last_message_at' => now(),
            ]);

            $this->addParticipant($conversation, $actor, ConversationParticipant::ROLE_OWNER, $actor);
            $this->addParticipant($conversation, $recipient, ConversationParticipant::ROLE_MEMBER, $actor);

            if (filled($firstMessage)) {
                $this->sendMessage($conversation, $actor, $firstMessage);
            }

            return $conversation;
        });
    }

    /**
     * @param list<int> $participantIds
     */
    public function createGroup(User $actor, string $title, array $participantIds, ?string $description = null): Conversation
    {
        $participantIds = collect($participantIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->push($actor->id)
            ->unique()
            ->values();

        if ($participantIds->count() < 2) {
            throw ValidationException::withMessages(['groupParticipantIds' => 'Добавьте хотя бы одного участника.']);
        }

        return DB::transaction(function () use ($actor, $title, $participantIds, $description): Conversation {
            $conversation = Conversation::query()->create([
                'type' => Conversation::TYPE_GROUP,
                'title' => $title,
                'description' => $description,
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'last_message_at' => now(),
            ]);

            User::query()
                ->whereIn('id', $participantIds)
                ->get()
                ->each(function (User $user) use ($conversation, $actor): void {
                    $this->addParticipant(
                        $conversation,
                        $user,
                        (int) $user->id === (int) $actor->id ? ConversationParticipant::ROLE_OWNER : ConversationParticipant::ROLE_MEMBER,
                        $actor,
                    );
                });

            $this->systemMessage($conversation, 'group_created', "{$actor->name} создал(а) групповой чат.");
            $this->markRead($conversation, $actor);

            return $conversation;
        });
    }

    public function sendMessage(Conversation $conversation, User $sender, ?string $body, mixed $upload = null): Message
    {
        $body = trim((string) $body);

        if ($body === '' && ! $upload) {
            throw ValidationException::withMessages(['messageBody' => 'Напишите сообщение или прикрепите файл.']);
        }

        return DB::transaction(function () use ($conversation, $sender, $body, $upload): Message {
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'body' => $body !== '' ? $body : null,
                'type' => $upload ? Message::TYPE_ATTACHMENT : Message::TYPE_TEXT,
            ]);

            if ($upload) {
                $this->storeAttachment($message, $sender, $upload);
            }

            $conversation->forceFill(['last_message_at' => now()])->save();

            $conversation->participants()
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => now(), 'is_archived' => false]);

            return $message;
        });
    }

    public function markRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    public function togglePin(Conversation $conversation, User $user): void
    {
        $participant = $conversation->participants()->where('user_id', $user->id)->firstOrFail();
        $participant->update(['is_pinned' => ! $participant->is_pinned]);
    }

    public function archiveForUser(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['is_archived' => true]);
    }

    private function addParticipant(Conversation $conversation, User $user, string $role, User $actor): ConversationParticipant
    {
        return $conversation->participants()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'role' => $role,
                'joined_at' => now(),
                'last_read_at' => (int) $user->id === (int) $actor->id ? now() : null,
                'added_by' => $actor->id,
            ],
        );
    }

    private function systemMessage(Conversation $conversation, string $event, string $body): Message
    {
        $message = $conversation->messages()->create([
            'type' => Message::TYPE_SYSTEM,
            'system_event' => $event,
            'body' => $body,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    private function storeAttachment(Message $message, User $sender, mixed $upload): MessageAttachment
    {
        if (! $upload instanceof UploadedFile && ! $upload instanceof TemporaryUploadedFile) {
            throw ValidationException::withMessages(['attachmentUpload' => 'Некорректный файл.']);
        }

        $path = $upload->store(date('Y/m'), 'messenger_attachments');

        return $message->attachments()->create([
            'uploaded_by' => $sender->id,
            'source' => MessageAttachment::SOURCE_UPLOAD,
            'file_path' => $path,
            'original_filename' => $upload->getClientOriginalName(),
            'mime_type' => $upload->getMimeType(),
            'size_bytes' => $upload->getSize(),
            'title' => $upload->getClientOriginalName(),
        ]);
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function latestUnreadFor(User $user, int $limit = 3): Collection
    {
        return Conversation::query()
            ->visibleTo($user)
            ->with(['participants.user.department', 'users.department', 'lastMessage', 'messages'])
            ->whereHas('messages', fn ($query) => $query->where('sender_id', '!=', $user->id))
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get()
            ->filter(fn (Conversation $conversation): bool => $conversation->unreadCountFor($user) > 0)
            ->values();
    }
}
