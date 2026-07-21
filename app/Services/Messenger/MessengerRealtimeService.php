<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\Message;

class MessengerRealtimeService
{
    public function messageCreated(Conversation $conversation, Message $message): void
    {
        // TODO: Replace Phase 1 polling with Reverb/Echo events when realtime infrastructure is enabled.
    }
}
