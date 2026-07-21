<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section id="employee-messages" class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">в—‹</span>
                    <span>Сообщения</span>
                </div>
            </div>

            <a class="nt-pill" href="{{ \App\Filament\Pages\Messenger::getUrl() }}">
                {{ $unreadCount > 0 ? $unreadCount : 'Открыть' }}
            </a>
        </div>

        <div class="nt-hr-card__body">
            @forelse ($unreadConversations as $conversation)
                <a href="{{ \App\Filament\Pages\Messenger::getUrl(['selectedConversationId' => $conversation->id]) }}" class="nt-action-row" style="text-decoration: none;">
                    <span>в—‹</span>
                    <div>
                        <strong>{{ $conversation->displayTitleFor(auth()->user()) }}</strong>
                        <small>{{ $conversation->lastMessage?->body ?: 'Новое сообщение' }}</small>
                    </div>
                    <em>{{ $conversation->unreadCountFor(auth()->user()) }}</em>
                </a>
            @empty
                <div class="nt-empty" style="text-align: center;">
                    Непрочитанных сообщений нет.
                </div>
            @endforelse
        </div>
    </section>
</x-filament-widgets::widget>
