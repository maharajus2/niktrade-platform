<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\User;
use RuntimeException;

class AttachSystemFileService
{
    public function attach(Conversation $conversation, User $actor, string $source, int|string $sourceId): never
    {
        throw new RuntimeException('System file attachments are reserved for a later Communication Hub phase.');
    }

    public function canExposeSystemFileToConversation(User $actor, Conversation $conversation, string $source, int|string $sourceId): bool
    {
        return false;
    }
}
