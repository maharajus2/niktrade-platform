@props([
    'homeUrl' => \App\Filament\Pages\Workplace::getUrl(),
])

<nav class="nik-work-mobile-bottom-bar" aria-label="Быстрая навигация">
    <a href="{{ $homeUrl }}" x-bind:class="{ 'is-active': activeSheet === null }">
        <x-work.icon name="home" />
        <span>Главная</span>
    </a>
    <button type="button" x-bind:class="{ 'is-active': activeSheet === 'messages' }" x-on:click="openSheet('messages')">
        <x-work.icon name="message" />
        <span>Мессенджер</span>
    </button>
    <button type="button" class="nik-work-mobile-bottom-menu" x-bind:class="{ 'is-active': activeSheet === 'menu' }" x-on:click="openSheet('menu')">
        <strong><x-work.icon name="grid" /></strong>
        <span>Меню</span>
    </button>
    <button type="button" x-bind:class="{ 'is-active': activeSheet === 'tasks' }" x-on:click="openSheet('tasks')">
        <x-work.icon name="check-square" />
        <span>Задачи</span>
    </button>
    <button type="button" x-bind:class="{ 'is-active': activeSheet === 'requests' }" x-on:click="openSheet('requests')">
        <x-work.icon name="file" />
        <span>Заявки</span>
    </button>
</nav>
