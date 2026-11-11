<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminLocaleTest extends TestCase
{
    public function test_admin_login_is_russian_even_if_the_application_locale_was_english(): void
    {
        config(['app.locale' => 'en']);
        app()->setLocale('en');
        Carbon::setLocale('en');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('lang="ru"', false)
            ->assertSee('Вход')
            ->assertSee('E-mail')
            ->assertSee('Пароль')
            ->assertSee('Запомнить меня')
            ->assertDontSee('Sign in');

        $this->assertSame('среда, 11 ноября 2026', Carbon::parse('2026-11-11')->translatedFormat('l, d F Y'));
        $this->assertSame('На странице', __('filament::components/pagination.fields.records_per_page.label'));
    }
}
