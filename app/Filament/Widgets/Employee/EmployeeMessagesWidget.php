<?php

namespace App\Filament\Widgets\Employee;

use App\Models\User;
use App\Services\Messenger\MessengerService;
use Filament\Widgets\Widget;

class EmployeeMessagesWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.messages-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [
                'unreadConversations' => collect(),
                'unreadCount' => 0,
            ];
        }

        $conversations = app(MessengerService::class)->latestUnreadFor($user, 3);

        return [
            'unreadConversations' => $conversations,
            'unreadCount' => $conversations->sum(fn ($conversation): int => $conversation->unreadCountFor($user)),
        ];
    }
}
