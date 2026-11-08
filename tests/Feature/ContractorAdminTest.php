<?php

namespace Tests\Feature;

use App\Filament\Resources\Contractors\ContractorResource;
use App\Filament\Resources\Contractors\Pages\CreateContractor;
use App\Filament\Resources\Contractors\Pages\EditContractor;
use App\Models\Contractor;
use App\Models\User;
use App\Support\WorkNavigation;
use Database\Seeders\AdminRolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractorAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user, 'web');
    }

    private function data(array $overrides = []): array
    {
        return [
            'company_name' => 'Админская тестовая компания', 'inn' => '7707083893', 'kpp' => '770701001',
            'contact_name' => 'Тестовый контакт', 'phone' => '+7 900 000-00-00', 'email' => 'admin-company@example.test',
            'password' => 'Business-test-4321', 'discount_percent' => 12.5, 'is_active' => true,
            ...$overrides,
        ];
    }

    public function test_admin_can_create_a_contractor_and_blank_password_does_not_change_it(): void
    {
        Livewire::test(CreateContractor::class)->fillForm($this->data())->call('create')->assertHasNoFormErrors();
        $contractor = Contractor::sole();
        $hash = $contractor->password;
        $this->assertTrue(Hash::check('Business-test-4321', $hash));
        $this->assertSame(87.50, $contractor->discountedPrice(100));
        $this->assertSame($contractor->company_name, $contractor->display_name);
        Livewire::test(EditContractor::class, ['record' => $contractor->id])
            ->fillForm(['company_name' => 'Обновлённое имя', 'password' => ''])->call('save')->assertHasNoFormErrors();
        $this->assertSame($hash, $contractor->fresh()->password);
        Livewire::test(EditContractor::class, ['record' => $contractor->id])
            ->fillForm(['password' => 'Replacement-test-4321'])->call('save')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('Replacement-test-4321', $contractor->fresh()->password));
    }

    public function test_admin_rejects_duplicate_identity_and_invalid_discount(): void
    {
        Contractor::create($this->data());
        Livewire::test(CreateContractor::class)->fillForm($this->data(['email' => 'other@example.test', 'discount_percent' => 101]))
            ->call('create')->assertHasFormErrors(['inn', 'discount_percent']);
    }

    public function test_admin_can_create_a_sole_proprietor_and_rejects_null_kpp_duplicates(): void
    {
        Livewire::test(CreateContractor::class)->fillForm($this->data(['inn' => '500100732259', 'kpp' => null]))
            ->call('create')->assertHasNoFormErrors();
        $this->assertNull(Contractor::sole()->kpp);
        Livewire::test(CreateContractor::class)->fillForm($this->data(['inn' => '500100732259', 'kpp' => null, 'email' => 'other@example.test']))
            ->call('create')->assertHasFormErrors(['inn']);
    }

    public function test_permissions_mirror_customer_access_without_granting_delete_to_readers(): void
    {
        $reader = Role::findOrCreate('customer_reader', 'web');
        $reader->givePermissionTo(Permission::findOrCreate('customers.view_any', 'web'));
        $reader->givePermissionTo(Permission::findOrCreate('customers.view', 'web'));
        $migration = require database_path('migrations/2026_11_08_000002_seed_contractor_permissions.php');
        $migration->up();
        $this->assertTrue($reader->fresh()->hasPermissionTo('contractors.view_any'));
        $this->assertFalse($reader->fresh()->hasPermissionTo('contractors.delete'));
        $user = User::factory()->create();
        $user->assignRole($reader);
        $this->actingAs($user, 'web');
        $this->assertTrue(ContractorResource::canViewAny());
        $this->assertFalse(ContractorResource::canCreate());
        $navigation = collect(WorkNavigation::groups('contractors', $user)['Продажи'])->firstWhere('key', 'contractors');
        $this->assertNotNull($navigation);
        $this->assertTrue($navigation['active']);

        $this->seed(AdminRolePermissionSeeder::class);
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('contractors.create'));
        foreach (['order_manager', 'accountant'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $this->assertTrue($role->hasPermissionTo('contractors.view_any'));
            $this->assertFalse($role->hasPermissionTo('contractors.delete'));
        }
    }

    public function test_changing_a_company_to_a_sole_proprietor_clears_the_old_kpp(): void
    {
        $contractor = Contractor::create($this->data());
        Livewire::test(EditContractor::class, ['record' => $contractor->id])
            ->fillForm(['inn' => '500100732259'])->call('save')->assertHasNoFormErrors();
        $this->assertNull($contractor->fresh()->kpp);
    }

    public function test_removing_a_manager_keeps_the_contractor_and_clears_the_relationship(): void
    {
        $manager = User::factory()->create();
        $contractor = Contractor::create($this->data(['manager_id' => $manager->id]));
        $this->assertTrue($contractor->manager->is($manager));
        $manager->delete();
        $this->assertNull($contractor->fresh()->manager_id);
    }
}
