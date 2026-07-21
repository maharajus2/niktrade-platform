<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MessengerAccessService
{
    public function canViewMessenger(User $user): bool
    {
        return true;
    }

    public function canViewConversation(Conversation $conversation, User $user): bool
    {
        if ($user->can('messenger.view_all')) {
            return true;
        }

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canSendToConversation(Conversation $conversation, User $user): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('role', '!=', 'observer')
            ->exists();
    }

    public function canDownloadAttachment(MessageAttachment $attachment, User $user): bool
    {
        $attachment->loadMissing('message.conversation');

        return $attachment->message !== null
            && $attachment->message->conversation !== null
            && $this->canViewConversation($attachment->message->conversation, $user);
    }

    public function scopeVisibleConversations(Builder $query, User $user): Builder
    {
        return $query->visibleTo($user);
    }
}
