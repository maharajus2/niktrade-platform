<x-filament-panels::page>
    @php
        $user = $messengerPage['user'] ?? auth()->user();
        $conversations = $messengerPage['conversations'] ?? collect();
        $selectedConversation = $messengerPage['selectedConversation'] ?? null;
        $messages = $messengerPage['messages'] ?? collect();
        $employees = $messengerPage['employees'] ?? collect();
        $filters = $messengerPage['filters'] ?? [];
    @endphp

    @component('layouts.work', [
        'title' => 'Мессенджер',
        'subtitle' => 'Быстрые внутренние чаты, группы и файлы.',
        'active' => 'messenger',
        'appClass' => 'nik-work-app--messenger nik-messenger '.($selectedConversation ? 'nik-messenger--chat-active' : ''),
        'user' => $user,
    ])
        <style>
            .nik-messenger-page {
                min-height: calc(100vh - 9rem);
                display: grid;
                grid-template-columns: minmax(280px, 360px) minmax(0, 1fr) minmax(240px, 320px);
                gap: 1rem;
                color: #0f172a;
            }

            .nik-messenger-panel {
                min-width: 0;
                border: 1px solid rgba(148, 163, 184, .26);
                border-radius: 18px;
                background: rgba(255, 255, 255, .82);
                box-shadow: 0 18px 48px rgba(15, 23, 42, .08);
                backdrop-filter: blur(18px);
            }

            .nik-messenger-list {
                overflow: hidden;
                display: flex;
                flex-direction: column;
                min-height: calc(100vh - 9rem);
            }

            .nik-messenger-list-head {
                padding: 1rem;
                border-bottom: 1px solid rgba(148, 163, 184, .2);
            }

            .nik-messenger-search,
            .nik-messenger-field input,
            .nik-messenger-field textarea,
            .nik-messenger-field select,
            .nik-messenger-composer textarea {
                width: 100%;
                border: 1px solid rgba(148, 163, 184, .28);
                border-radius: 14px;
                background: rgba(255, 255, 255, .92);
                color: #0f172a;
                outline: none;
            }

            .nik-messenger-search {
                display: flex;
                align-items: center;
                gap: .55rem;
                padding: .7rem .8rem;
            }

            .nik-messenger-search input {
                width: 100%;
                border: 0;
                background: transparent;
                outline: none;
            }

            .nik-messenger-filters {
                display: flex;
                gap: .45rem;
                overflow-x: auto;
                padding: .85rem 0 0;
            }

            .nik-messenger-filters button,
            .nik-messenger-btn,
            .nik-messenger-actions button,
            .nik-messenger-actions a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: .45rem;
                border: 1px solid rgba(148, 163, 184, .28);
                border-radius: 12px;
                background: rgba(255, 255, 255, .9);
                color: #334155;
                font-weight: 800;
                text-decoration: none;
                cursor: pointer;
            }

            .nik-messenger-filters button {
                flex: 0 0 auto;
                padding: .48rem .68rem;
                font-size: .82rem;
            }

            .nik-messenger-filters button.is-active,
            .nik-messenger-btn.is-primary {
                border-color: rgba(37, 99, 235, .34);
                background: linear-gradient(135deg, #2563eb, #0891b2);
                color: #fff;
            }

            .nik-messenger-create {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
                margin-top: .85rem;
            }

            .nik-messenger-btn {
                min-height: 42px;
                padding: .65rem .75rem;
            }

            .nik-messenger-form {
                display: grid;
                gap: .75rem;
                margin-top: .85rem;
                padding: .85rem;
                border-radius: 16px;
                background: rgba(241, 245, 249, .76);
            }

            .nik-messenger-field {
                display: grid;
                gap: .35rem;
                font-size: .82rem;
                font-weight: 800;
                color: #475569;
            }

            .nik-messenger-field input,
            .nik-messenger-field textarea,
            .nik-messenger-field select {
                min-height: 42px;
                padding: .65rem .75rem;
                font-weight: 600;
            }

            .nik-messenger-conversations {
                overflow: auto;
                display: grid;
                gap: .55rem;
                padding: .8rem;
            }

            .nik-messenger-card {
                display: grid;
                grid-template-columns: 44px minmax(0, 1fr) auto;
                gap: .7rem;
                width: 100%;
                border: 1px solid transparent;
                border-radius: 16px;
                background: transparent;
                padding: .7rem;
                text-align: left;
                cursor: pointer;
            }

            .nik-messenger-card:hover,
            .nik-messenger-card.is-active {
                border-color: rgba(37, 99, 235, .24);
                background: rgba(239, 246, 255, .78);
            }

            .nik-messenger-avatar {
                display: inline-flex;
                width: 44px;
                height: 44px;
                align-items: center;
                justify-content: center;
                border-radius: 14px;
                background: linear-gradient(135deg, #dbeafe, #cffafe);
                color: #1d4ed8;
                font-weight: 900;
            }

            .nik-messenger-card strong,
            .nik-messenger-chat-title {
                overflow: hidden;
                display: block;
                color: #0f172a;
                font-weight: 900;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .nik-messenger-card p {
                overflow: hidden;
                margin: .2rem 0 0;
                color: #64748b;
                font-size: .84rem;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .nik-messenger-meta {
                display: grid;
                justify-items: end;
                gap: .35rem;
                color: #94a3b8;
                font-size: .75rem;
            }

            .nik-messenger-unread {
                display: inline-flex;
                min-width: 22px;
                height: 22px;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: #ef4444;
                color: #fff;
                font-size: .75rem;
                font-weight: 900;
            }

            .nik-messenger-chat {
                overflow: hidden;
                display: flex;
                flex-direction: column;
                min-height: calc(100vh - 9rem);
            }

            .nik-messenger-chat-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1rem;
                border-bottom: 1px solid rgba(148, 163, 184, .2);
            }

            .nik-messenger-chat-head small {
                display: block;
                margin-top: .18rem;
                color: #64748b;
                font-weight: 700;
            }

            .nik-messenger-actions {
                display: flex;
                gap: .45rem;
                flex-wrap: wrap;
            }

            .nik-messenger-actions button,
            .nik-messenger-actions a {
                min-height: 38px;
                padding: .55rem .7rem;
                font-size: .8rem;
            }

            .nik-messenger-messages {
                overflow: auto;
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: .65rem;
                padding: 1rem;
            }

            .nik-messenger-bubble {
                max-width: min(74%, 620px);
                align-self: flex-start;
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 16px;
                background: rgba(255, 255, 255, .94);
                padding: .7rem .85rem;
                box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
            }

            .nik-messenger-bubble.is-own {
                align-self: flex-end;
                border-color: rgba(37, 99, 235, .24);
                background: #eaf4ff;
            }

            .nik-messenger-bubble.is-system {
                align-self: center;
                max-width: 82%;
                border: 0;
                background: rgba(226, 232, 240, .72);
                color: #64748b;
                text-align: center;
                box-shadow: none;
            }

            .nik-messenger-bubble header {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                color: #64748b;
                font-size: .76rem;
                font-weight: 800;
            }

            .nik-messenger-bubble p {
                margin: .35rem 0 0;
                white-space: pre-wrap;
                overflow-wrap: anywhere;
            }

            .nik-messenger-attachment {
                display: flex;
                align-items: center;
                gap: .5rem;
                margin-top: .55rem;
                border-radius: 12px;
                background: rgba(255, 255, 255, .76);
                padding: .55rem .65rem;
                color: #1d4ed8;
                font-weight: 800;
                text-decoration: none;
            }

            .nik-messenger-attachment-card {
                display: grid;
                gap: .55rem;
                margin-top: .65rem;
            }

            .nik-messenger-media {
                overflow: hidden;
                width: min(100%, 360px);
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 14px;
                background: rgba(248, 250, 252, .9);
            }

            .nik-messenger-media img,
            .nik-messenger-media video {
                display: block;
                width: 100%;
                max-height: 260px;
                object-fit: contain;
                background: #0f172a;
            }

            .nik-messenger-media audio {
                display: block;
                width: 100%;
                min-width: 260px;
                padding: .5rem;
            }

            .nik-messenger-file-card,
            .nik-messenger-upload-preview {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                width: min(100%, 360px);
                border: 1px solid rgba(148, 163, 184, .24);
                border-radius: 14px;
                background: rgba(255, 255, 255, .86);
                padding: .65rem .75rem;
                text-decoration: none;
            }

            .nik-messenger-file-main {
                display: grid;
                grid-template-columns: 34px minmax(0, 1fr);
                gap: .6rem;
                min-width: 0;
                align-items: center;
                color: #334155;
            }

            .nik-messenger-file-main > span:first-child {
                display: inline-flex;
                width: 34px;
                height: 34px;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: #e0f2fe;
                color: #0369a1;
            }

            .nik-messenger-file-main strong,
            .nik-messenger-file-main small {
                overflow: hidden;
                display: block;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .nik-messenger-file-main strong {
                color: #0f172a;
                font-size: .84rem;
                font-weight: 900;
            }

            .nik-messenger-file-main small {
                color: #64748b;
                font-size: .75rem;
                font-weight: 700;
            }

            .nik-messenger-upload-preview {
                width: 100%;
                margin-top: .1rem;
            }

            .nik-messenger-upload-preview button {
                border: 0;
                background: transparent;
                color: #64748b;
                cursor: pointer;
                font-size: 1.25rem;
                line-height: 1;
            }

            .nik-messenger-composer {
                display: grid;
                gap: .65rem;
                padding: 1rem;
                border-top: 1px solid rgba(148, 163, 184, .2);
                background: rgba(248, 250, 252, .74);
            }

            .nik-messenger-composer textarea {
                min-height: 58px;
                max-height: 150px;
                padding: .75rem .85rem;
                resize: vertical;
            }

            .nik-messenger-composer-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
            }

            .nik-messenger-file {
                display: inline-flex;
                align-items: center;
                gap: .45rem;
                color: #475569;
                font-weight: 800;
                cursor: pointer;
            }

            .nik-messenger-file input {
                max-width: 170px;
                font-size: .78rem;
            }

            .nik-messenger-info {
                min-height: calc(100vh - 9rem);
                padding: 1rem;
            }

            .nik-messenger-info h3 {
                margin: 0 0 .75rem;
                color: #0f172a;
                font-size: .95rem;
                font-weight: 900;
            }

            .nik-messenger-participants,
            .nik-messenger-shared {
                display: grid;
                gap: .55rem;
                margin-bottom: 1rem;
            }

            .nik-messenger-person {
                display: grid;
                grid-template-columns: 36px minmax(0, 1fr);
                gap: .55rem;
                align-items: center;
            }

            .nik-messenger-person span {
                width: 36px;
                height: 36px;
                border-radius: 12px;
                font-size: .8rem;
            }

            .nik-messenger-person strong,
            .nik-messenger-person small {
                overflow: hidden;
                display: block;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .nik-messenger-person small,
            .nik-messenger-muted {
                color: #64748b;
                font-size: .78rem;
            }

            .nik-messenger-empty {
                display: grid;
                place-items: center;
                min-height: calc(100vh - 9rem);
                padding: 2rem;
                text-align: center;
                color: #64748b;
            }

            .nik-messenger-empty strong {
                display: block;
                margin-bottom: .4rem;
                color: #0f172a;
                font-size: 1.05rem;
            }

            .nik-messenger-mobile-back {
                display: none;
            }

            @media (max-width: 1100px) {
                .nik-messenger-page {
                    grid-template-columns: 320px minmax(0, 1fr);
                }

                .nik-messenger-info {
                    display: none;
                }
            }

            @media (max-width: 760px) {
                .nik-work-app.nik-messenger {
                    margin: 0 !important;
                }

                .nik-work-app.nik-messenger .nik-work-shell {
                    padding: 0 !important;
                }

                .nik-work-app.nik-messenger .nik-work-layout {
                    display: block !important;
                }

                .nik-work-app.nik-messenger .nik-work-sidebar {
                    display: none !important;
                }

                .nik-work-app.nik-messenger.nik-messenger--chat-active .nik-work-main > .nik-work-header {
                    display: none !important;
                }

                .nik-work-app.nik-messenger .nik-work-main {
                    padding-bottom: 0;
                }

                .nik-messenger-page {
                    min-height: calc(100vh - 6rem);
                    display: block;
                }

                .nik-messenger-list,
                .nik-messenger-chat {
                    min-height: calc(100vh - 8rem);
                    border-radius: 0;
                    box-shadow: none;
                }

                .nik-messenger-page.has-selected .nik-messenger-list {
                    display: none;
                }

                .nik-messenger-page:not(.has-selected) .nik-messenger-chat {
                    display: none;
                }

                .nik-messenger-mobile-back {
                    display: inline-flex;
                }

                .nik-messenger-chat-head {
                    position: sticky;
                    top: 0;
                    z-index: 2;
                    background: rgba(255, 255, 255, .94);
                    backdrop-filter: blur(16px);
                }

                .nik-messenger-bubble {
                    max-width: 88%;
                }

                .nik-messenger-media,
                .nik-messenger-file-card {
                    width: 100%;
                }

                .nik-messenger-composer {
                    position: sticky;
                    bottom: 0;
                    padding-bottom: max(1rem, env(safe-area-inset-bottom));
                }

                .nik-messenger-page.has-selected ~ .nik-work-mobile-bottom-bar,
                .nik-work-app.nik-messenger:has(.nik-messenger-page.has-selected) .nik-work-mobile-bottom-bar {
                    display: none;
                }
            }
        </style>

        <div
            class="nik-messenger-page {{ $selectedConversation ? 'has-selected' : '' }}"
            wire:poll.7s
            x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
            x-init="$wire.setBrowserTimezone(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC')"
            x-on:keydown.escape.window="closeSheet()"
        >
            <aside class="nik-messenger-panel nik-messenger-list">
                <div class="nik-messenger-list-head">
                    <label class="nik-messenger-search">
                        <x-work.icon name="search" />
                        <input type="search" placeholder="Поиск чатов..." wire:model.live.debounce.350ms="search">
                    </label>

                    <div class="nik-messenger-filters">
                        @foreach ($filters as $key => $label)
                            <button type="button" class="{{ $this->filter === $key ? 'is-active' : '' }}" wire:click="setFilter('{{ $key }}')">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="nik-messenger-create">
                        @if ($messengerPage['canCreateDirect'] ?? false)
                            <button type="button" class="nik-messenger-btn is-primary" wire:click="openDirectForm"><x-work.icon name="plus" /> Новый</button>
                        @endif
                        @if ($messengerPage['canCreateGroup'] ?? false)
                            <button type="button" class="nik-messenger-btn" wire:click="openGroupForm"><x-work.icon name="users" /> Группа</button>
                        @endif
                    </div>

                    @if ($this->showDirectForm)
                        <form class="nik-messenger-form" wire:submit.prevent="createDirect">
                            <label class="nik-messenger-field">
                                <span>Сотрудник</span>
                                <select wire:model="directUserId">
                                    <option value="">Выберите сотрудника</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }} @if($employee->department) · {{ $employee->department->name }} @endif</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="nik-messenger-field">
                                <span>Первое сообщение</span>
                                <textarea rows="2" wire:model="directFirstMessage"></textarea>
                            </label>
                            <div class="nik-messenger-actions">
                                <button type="submit">Открыть</button>
                                <button type="button" wire:click="closeForms">Отмена</button>
                            </div>
                        </form>
                    @endif

                    @if ($this->showGroupForm)
                        <form class="nik-messenger-form" wire:submit.prevent="createGroup">
                            <label class="nik-messenger-field">
                                <span>Название *</span>
                                <input type="text" wire:model="groupTitle">
                            </label>
                            <label class="nik-messenger-field">
                                <span>Участники</span>
                                <select multiple wire:model="groupParticipantIds">
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }} @if($employee->department) · {{ $employee->department->name }} @endif</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="nik-messenger-field">
                                <span>Описание</span>
                                <textarea rows="2" wire:model="groupDescription"></textarea>
                            </label>
                            <div class="nik-messenger-actions">
                                <button type="submit">Создать</button>
                                <button type="button" wire:click="closeForms">Отмена</button>
                            </div>
                        </form>
                    @endif
                </div>

                <div class="nik-messenger-conversations">
                    @forelse ($conversations as $conversation)
                        @php
                            $unread = $conversation->unreadCountFor($user);
                            $lastMessage = $conversation->lastMessage;
                            $title = $conversation->displayTitleFor($user);
                            $isPinned = (bool) $conversation->participants->firstWhere('user_id', $user->id)?->is_pinned;
                        @endphp
                        <button
                            type="button"
                            class="nik-messenger-card {{ (int) $this->selectedConversationId === (int) $conversation->id ? 'is-active' : '' }}"
                            wire:click="selectConversation({{ $conversation->id }})"
                        >
                            <span class="nik-messenger-avatar">{{ mb_substr($title, 0, 1) }}</span>
                            <span>
                                <strong>{{ $isPinned ? 'Pin · ' : '' }}{{ $title }}</strong>
                                <p>{{ $lastMessage?->body ?: ($lastMessage?->attachments?->first()?->original_filename ?? 'Пока нет сообщений') }}</p>
                            </span>
                            <span class="nik-messenger-meta">
                                <span>{{ $this->formatMessengerTime($conversation->last_message_at, 'H:i') }}</span>
                                @if ($unread > 0)
                                    <em class="nik-messenger-unread">{{ $unread }}</em>
                                @endif
                            </span>
                        </button>
                    @empty
                        <div class="nik-messenger-empty">
                            <div>
                                <strong>Чатов пока нет</strong>
                                <span>Начните личное общение или создайте группу.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </aside>

            <main class="nik-messenger-panel nik-messenger-chat">
                @if ($selectedConversation)
                    <header class="nik-messenger-chat-head">
                        <div>
                            <button type="button" class="nik-messenger-btn nik-messenger-mobile-back" wire:click="$set('selectedConversationId', null)">
                                <x-work.icon name="chevron-left" />
                            </button>
                            <strong class="nik-messenger-chat-title">{{ $selectedConversation->displayTitleFor($user) }}</strong>
                            <small>{{ $selectedConversation->participants->count() }} участник(а) · {{ $selectedConversation->type }}</small>
                        </div>
                        <div class="nik-messenger-actions">
                            <button type="button" wire:click="togglePin"><x-work.icon name="check-circle" /> Pin</button>
                            <button type="button" wire:click="archiveSelected"><x-work.icon name="archive" /> Архив</button>
                        </div>
                    </header>

                    <section class="nik-messenger-messages">
                        @forelse ($messages as $message)
                            <article class="nik-messenger-bubble {{ (int) $message->sender_id === (int) $user->id ? 'is-own' : '' }} {{ $message->isSystem() ? 'is-system' : '' }}">
                                <header>
                                    <span>{{ $message->isSystem() ? 'Система' : ($message->sender?->name ?? 'Сотрудник') }}</span>
                                    <time>{{ $this->formatMessengerTime($message->created_at, 'd.m.Y H:i') }}</time>
                                </header>
                                <p>{{ $message->isDeleted() ? 'Сообщение удалено' : $message->body }}</p>
                                @foreach ($message->attachments as $attachment)
                                    @php
                                        $attachmentName = $attachment->original_filename ?: $attachment->title ?: 'Файл';
                                        $downloadUrl = route('admin.messenger.attachments.download', $attachment);
                                        $previewUrl = $attachment->isPreviewable() ? route('admin.messenger.attachments.preview', $attachment) : null;
                                        $attachmentLabel = match ($attachment->kind) {
                                            'image' => 'Фотография',
                                            'audio' => 'Музыка',
                                            'video' => 'Видео',
                                            'pdf' => 'PDF',
                                            default => 'Документ',
                                        };
                                    @endphp
                                    <div class="nik-messenger-attachment-card">
                                        @if ($attachment->isImage() && $previewUrl)
                                            <a class="nik-messenger-media" href="{{ $downloadUrl }}" title="Скачать {{ $attachmentName }}">
                                                <img src="{{ $previewUrl }}" alt="{{ $attachmentName }}" loading="lazy">
                                            </a>
                                        @elseif ($attachment->isVideo() && $previewUrl)
                                            <div class="nik-messenger-media">
                                                <video src="{{ $previewUrl }}" controls preload="metadata"></video>
                                            </div>
                                        @elseif ($attachment->isAudio() && $previewUrl)
                                            <div class="nik-messenger-media">
                                                <audio src="{{ $previewUrl }}" controls preload="metadata"></audio>
                                            </div>
                                        @endif

                                        <a class="nik-messenger-file-card" href="{{ $downloadUrl }}">
                                            <span class="nik-messenger-file-main">
                                                <span><x-work.icon :name="$attachment->iconName()" /></span>
                                                <span>
                                                    <strong>{{ $attachmentName }}</strong>
                                                    <small>{{ $attachmentLabel }} @if($attachment->display_size) · {{ $attachment->display_size }} @endif</small>
                                                </span>
                                            </span>
                                            <x-work.icon name="download" />
                                        </a>
                                    </div>
                                @endforeach
                            </article>
                        @empty
                            <div class="nik-messenger-empty">
                                <div>
                                    <strong>Напишите первое сообщение</strong>
                                    <span>Все участники этого чата увидят новые сообщения.</span>
                                </div>
                            </div>
                        @endforelse
                    </section>

                    <form
                        class="nik-messenger-composer"
                        wire:key="messenger-composer-{{ $selectedConversation->id }}-{{ $messages->count() }}"
                        wire:submit.prevent="sendMessage"
                        x-data="{
                            filePreview: null,
                            clearFilePreview() {
                                const previewUrl = this.filePreview?.url;
                                this.filePreview = null;
                                $refs.attachmentInput.value = '';
                                $wire.set('attachmentUpload', null);
                                if (previewUrl) this.$nextTick(() => URL.revokeObjectURL(previewUrl));
                            },
                            hideFilePreview() {
                                const previewUrl = this.filePreview?.url;
                                this.filePreview = null;
                                if (previewUrl) this.$nextTick(() => URL.revokeObjectURL(previewUrl));
                            },
                            updateFilePreview(event) {
                                const file = event.target.files?.[0];

                                const previewUrl = this.filePreview?.url;
                                this.filePreview = null;
                                if (previewUrl) this.$nextTick(() => URL.revokeObjectURL(previewUrl));

                                if (! file) return;

                                const type = file.type || 'application/octet-stream';
                                const kind = type.startsWith('image/')
                                    ? 'image'
                                    : type.startsWith('audio/')
                                        ? 'audio'
                                        : type.startsWith('video/')
                                            ? 'video'
                                            : type === 'application/pdf'
                                                ? 'pdf'
                                                : 'document';

                                this.filePreview = {
                                    name: file.name,
                                    size: new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 1 }).format(file.size / 1024 / 1024) + ' MB',
                                    type,
                                    kind,
                                    label: ({ image: 'Фотография', audio: 'Музыка', video: 'Видео', pdf: 'PDF', document: 'Документ' })[kind],
                                    url: ['image', 'audio', 'video'].includes(kind) ? URL.createObjectURL(file) : null,
                                };
                            },
                        }"
                        x-on:messenger-file-sent.window="clearFilePreview()"
                    >
                        <textarea
                            rows="2"
                            wire:model="messageBody"
                            placeholder="Написать сообщение..."
                            x-on:keydown.enter.exact.prevent="$wire.sendMessage()"
                        ></textarea>
                        @error('messageBody') <span class="nik-messenger-muted">{{ $message }}</span> @enderror
                        @error('attachmentUpload') <span class="nik-messenger-muted">{{ $message }}</span> @enderror
                        <template x-if="filePreview">
                            <div class="nik-messenger-upload-preview">
                                <span class="nik-messenger-file-main">
                                    <span>
                                        <template x-if="filePreview.kind === 'image'"><x-work.icon name="image" /></template>
                                        <template x-if="filePreview.kind === 'audio'"><x-work.icon name="music" /></template>
                                        <template x-if="filePreview.kind === 'video'"><x-work.icon name="video" /></template>
                                        <template x-if="filePreview.kind === 'pdf'"><x-work.icon name="file-text" /></template>
                                        <template x-if="filePreview.kind === 'document'"><x-work.icon name="file" /></template>
                                    </span>
                                    <span>
                                        <strong x-text="filePreview.name"></strong>
                                        <small x-text="filePreview.label + ' · ' + filePreview.size"></small>
                                    </span>
                                </span>
                                <button type="button" x-on:click="clearFilePreview()" title="Убрать файл">&times;</button>
                            </div>
                        </template>
                        <template x-if="filePreview?.kind === 'image'">
                            <div class="nik-messenger-media"><img x-bind:src="filePreview.url" alt=""></div>
                        </template>
                        <template x-if="filePreview?.kind === 'audio'">
                            <div class="nik-messenger-media"><audio x-bind:src="filePreview.url" controls></audio></div>
                        </template>
                        <template x-if="filePreview?.kind === 'video'">
                            <div class="nik-messenger-media"><video x-bind:src="filePreview.url" controls></video></div>
                        </template>
                        <div class="nik-messenger-composer-row">
                            <div class="nik-messenger-actions">
                                @if ($messengerPage['canAttachFiles'] ?? false)
                                    <label class="nik-messenger-file">
                                        <x-work.icon name="file" />
                                        <input
                                            type="file"
                                            wire:model="attachmentUpload"
                                            x-ref="attachmentInput"
                                            x-on:change="updateFilePreview($event)"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.gif,.txt,.zip,.mp3,.wav,.ogg,.m4a,.mp4,.mov,.webm,image/*,audio/*,video/*,application/pdf"
                                        >
                                    </label>
                                @endif
                                <button type="button" disabled title="Скоро">
                                    <x-work.icon name="link" /> Из системы · Скоро
                                </button>
                            </div>
                            <button
                                type="submit"
                                class="nik-messenger-btn is-primary"
                                wire:loading.attr="disabled"
                                wire:target="attachmentUpload,sendMessage"
                                x-on:click="setTimeout(() => hideFilePreview(), 500)"
                            >
                                <x-work.icon name="message" /> Отправить
                            </button>
                        </div>
                    </form>
                @else
                    <div class="nik-messenger-empty">
                        <div>
                            <strong>Выберите чат или начните новое общение.</strong>
                            <span>Личные и групповые чаты будут здесь.</span>
                        </div>
                    </div>
                @endif
            </main>

            <aside class="nik-messenger-panel nik-messenger-info">
                @if ($selectedConversation)
                    <h3>Участники</h3>
                    <div class="nik-messenger-participants">
                        @foreach ($selectedConversation->participants as $participant)
                            <div class="nik-messenger-person">
                                <span class="nik-messenger-avatar">{{ $participant->user?->initials ?: 'N' }}</span>
                                <div>
                                    <strong>{{ $participant->user?->name }}</strong>
                                    <small>{{ $participant->role }} @if($participant->user?->department) · {{ $participant->user->department->name }} @endif</small>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h3>Файлы</h3>
                    <div class="nik-messenger-shared">
                        @php
                            $attachments = $messages->flatMap(fn ($message) => $message->attachments);
                        @endphp
                        @forelse ($attachments as $attachment)
                            <a class="nik-messenger-attachment" href="{{ route('admin.messenger.attachments.download', $attachment) }}">
                                <x-work.icon name="file" /> {{ $attachment->original_filename ?: 'Файл' }}
                            </a>
                        @empty
                            <span class="nik-messenger-muted">Общих файлов пока нет.</span>
                        @endforelse
                    </div>

                    <h3>Связанный объект</h3>
                    <p class="nik-messenger-muted">Чаты задач, заказов и документов будут подключены позже.</p>
                @endif
            </aside>
        </div>

        <div
            x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
            x-on:keydown.escape.window="closeSheet()"
        >
            <x-work.mobile-bottom-sheets />
            @unless ($selectedConversation)
                <x-work.mobile-bottom-nav active="messenger" />
            @endunless
        </div>
    @endcomponent
</x-filament-panels::page>
