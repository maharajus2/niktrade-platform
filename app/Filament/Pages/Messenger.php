<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messenger\MessengerAccessService;
use App\Services\Messenger\MessengerService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Throwable;

class Messenger extends Page
{
    use WithFileUploads;

    protected static string $routePath = '/messenger';

    protected string $view = 'filament.pages.messenger';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Мессенджер';

    protected static ?string $navigationLabel = 'Мессенджер';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public string $search = '';

    public string $filter = 'all';

    #[Url]
    public ?int $selectedConversationId = null;

    public string $messageBody = '';

    public mixed $attachmentUpload = null;

    public bool $showDirectForm = false;

    public bool $showGroupForm = false;

    public ?int $directUserId = null;

    public string $directFirstMessage = '';

    public string $groupTitle = '';

    public string $groupDescription = '';

    public array $groupParticipantIds = [];

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Мессенджер';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function mount(MessengerAccessService $access): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $access->canViewMessenger($user), 403);

        $this->selectedConversationId = $this->selectedConversationId ?: $this->conversationQuery($user)->value('id');

        if ($this->selectedConversationId) {
            $this->markSelectedRead();
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return [
            'messengerPage' => $this->messengerPageData(),
        ];
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'direct', 'group', 'department', 'unread'], true)) {
            return;
        }

        $this->filter = $filter;
    }

    public function selectConversation(int $conversationId, MessengerAccessService $access, MessengerService $messenger): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $conversation = Conversation::query()->with('participants')->find($conversationId);

        if (! $conversation instanceof Conversation || ! $access->canViewConversation($conversation, $user)) {
            Notification::make()->title('Чат недоступен')->danger()->send();

            return;
        }

        $this->selectedConversationId = $conversation->id;
        $messenger->markRead($conversation, $user);
    }

    public function openDirectForm(): void
    {
        $this->showDirectForm = true;
        $this->showGroupForm = false;
    }

    public function openGroupForm(): void
    {
        $this->showGroupForm = true;
        $this->showDirectForm = false;
    }

    public function closeForms(): void
    {
        $this->showDirectForm = false;
        $this->showGroupForm = false;
    }

    public function createDirect(MessengerService $messenger): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            Notification::make()->title('Нет прав на создание чата')->danger()->send();

            return;
        }

        $data = Validator::make([
            'directUserId' => $this->directUserId,
            'directFirstMessage' => $this->directFirstMessage,
        ], [
            'directUserId' => ['required', 'integer', 'exists:users,id'],
            'directFirstMessage' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        try {
            $recipient = User::query()->findOrFail($data['directUserId']);
            $conversation = $messenger->findOrCreateDirect($actor, $recipient, $data['directFirstMessage']);
            $this->selectedConversationId = $conversation->id;
            $this->directUserId = null;
            $this->directFirstMessage = '';
            $this->showDirectForm = false;

            Notification::make()->title('Чат открыт')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Не удалось открыть чат')->body($exception->getMessage())->danger()->send();
        }
    }

    public function createGroup(MessengerService $messenger): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User || ! $actor->can('messenger.create_group')) {
            Notification::make()->title('Нет прав на групповой чат')->danger()->send();

            return;
        }

        $data = Validator::make([
            'title' => $this->groupTitle,
            'description' => $this->groupDescription,
            'participant_ids' => $this->groupParticipantIds,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ])->validate();

        try {
            $conversation = $messenger->createGroup($actor, $data['title'], $data['participant_ids'], $data['description'] ?: null);
            $this->selectedConversationId = $conversation->id;
            $this->groupTitle = '';
            $this->groupDescription = '';
            $this->groupParticipantIds = [];
            $this->showGroupForm = false;

            Notification::make()->title('Группа создана')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Не удалось создать группу')->body($exception->getMessage())->danger()->send();
        }
    }

    public function sendMessage(MessengerAccessService $access, MessengerService $messenger): void
    {
        $user = auth()->user();
        $conversation = $this->selectedConversation();

        if (! $user instanceof User || ! $conversation instanceof Conversation) {
            return;
        }

        if (! $access->canSendToConversation($conversation, $user)) {
            Notification::make()->title('Нет прав на отправку')->danger()->send();

            return;
        }

        try {
            $this->validate([
                'messageBody' => ['nullable', 'string', 'max:10000'],
                'attachmentUpload' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp,txt,zip'],
            ]);

            $messenger->sendMessage($conversation, $user, $this->messageBody, $this->attachmentUpload);
            $this->messageBody = '';
            $this->attachmentUpload = null;
            $this->dispatch('$refresh');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Не удалось отправить')->body($exception->getMessage())->danger()->send();
        }
    }

    public function togglePin(MessengerService $messenger): void
    {
        $user = auth()->user();
        $conversation = $this->selectedConversation();

        if ($user instanceof User && $conversation instanceof Conversation) {
            $messenger->togglePin($conversation, $user);
        }
    }

    public function archiveSelected(MessengerService $messenger): void
    {
        $user = auth()->user();
        $conversation = $this->selectedConversation();

        if ($user instanceof User && $conversation instanceof Conversation) {
            $messenger->archiveForUser($conversation, $user);
            $this->selectedConversationId = null;
        }
    }

    public function markSelectedRead(): void
    {
        $user = auth()->user();
        $conversation = $this->selectedConversation();

        if ($user instanceof User && $conversation instanceof Conversation) {
            app(MessengerService::class)->markRead($conversation, $user);
        }
    }

    private function messengerPageData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $conversations = $this->conversationQuery($user)
            ->with(['participants.user.department', 'users.department', 'lastMessage.attachments'])
            ->get()
            ->filter(fn (Conversation $conversation): bool => $this->filter !== 'unread' || $conversation->unreadCountFor($user) > 0)
            ->values();

        $selected = $this->selectedConversation()?->load([
            'participants.user.department',
            'users.department',
            'messages.sender',
            'messages.attachments',
        ]);

        return [
            'user' => $user,
            'conversations' => $conversations,
            'selectedConversation' => $selected,
            'messages' => $selected?->messages()->with(['sender', 'attachments'])->oldest()->get() ?? collect(),
            'employees' => $this->employeeOptions($user),
            'totalUnread' => $conversations->sum(fn (Conversation $conversation): int => $conversation->unreadCountFor($user)),
            'canCreateDirect' => true,
            'canCreateGroup' => $user->can('messenger.create_group'),
            'canAttachFiles' => true,
            'filters' => [
                'all' => 'Все',
                'direct' => 'Личные',
                'group' => 'Группы',
                'department' => 'Отделы',
                'unread' => 'Непрочитанные',
            ],
        ];
    }

    private function conversationQuery(User $user): Builder
    {
        return Conversation::query()
            ->visibleTo($user)
            ->whereHas('participants', fn (Builder $query): Builder => $query
                ->where('user_id', $user->id)
                ->where('is_archived', false))
            ->when(
                in_array($this->filter, ['direct', 'group', 'department'], true),
                fn (Builder $query): Builder => $query->where('type', $this->filter),
            )
            ->when(trim($this->search) !== '', function (Builder $query): Builder {
                $search = trim($this->search);

                return $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'ilike', "%{$search}%")
                        ->orWhereHas('users', fn (Builder $query): Builder => $query
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%"));
                });
            })
            ->orderByDesc(
                \App\Models\ConversationParticipant::query()
                    ->select('is_pinned')
                    ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                    ->where('user_id', $user->id)
                    ->limit(1),
            )
            ->orderByDesc('last_message_at')
            ->latest();
    }

    private function selectedConversation(): ?Conversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return Conversation::query()
            ->visibleTo($user)
            ->find($this->selectedConversationId);
    }

    /**
     * @return Collection<int, User>
     */
    private function employeeOptions(User $user): Collection
    {
        return User::query()
            ->with('department')
            ->whereNull('archived_at')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(120)
            ->get();
    }
}
