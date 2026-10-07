<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Tests\TestCase;

class UserPanelAccessTest extends TestCase
{
    public function test_active_user_can_access_filament_panel(): void
    {
        $user = new User(['employment_status' => User::STATUS_WORKING]);

        $this->assertInstanceOf(FilamentUser::class, $user);
        $this->assertTrue($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_archived_user_cannot_access_filament_panel(): void
    {
        $user = new User(['employment_status' => User::STATUS_ARCHIVED]);

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }
}
