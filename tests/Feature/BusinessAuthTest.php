<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Customer;
use App\Rules\ValidInn;
use App\Rules\ValidKpp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BusinessAuthTest extends TestCase
{
    use RefreshDatabase;

    private const COMPANY_INN = '7707083893';

    // Checksum-valid example used for the two-digit individual INN checksum.
    private const SOLE_PROPRIETOR_INN = '500100732259';

    private string $viewDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        // Portal templates are developed separately. Only these temporary test views are rendered.
        $this->viewDirectory = sys_get_temp_dir().'/niktrade-business-views-'.bin2hex(random_bytes(8));
        mkdir($this->viewDirectory.'/business', 0700, true);
        file_put_contents($this->viewDirectory.'/business/home.blade.php', 'Business home');
        file_put_contents($this->viewDirectory.'/business/account.blade.php', '{{ $contractor->company_name }}');
        app('view')->getFinder()->prependLocation($this->viewDirectory);
    }

    protected function tearDown(): void
    {
        try {
            unlink($this->viewDirectory.'/business/home.blade.php');
            unlink($this->viewDirectory.'/business/account.blade.php');
            rmdir($this->viewDirectory.'/business');
            rmdir($this->viewDirectory);
        } finally {
            parent::tearDown();
        }
    }

    private function registration(array $overrides = []): array
    {
        return [
            'company_name' => 'Тестовая компания',
            'inn' => self::COMPANY_INN,
            'kpp' => '770701001',
            'contact_name' => 'Тестовый представитель',
            'phone' => '+7 900 000-00-00',
            'email' => 'business@example.test',
            'password' => 'Business-test-4321',
            'password_confirmation' => 'Business-test-4321',
            ...$overrides,
        ];
    }

    private function contractor(array $overrides = []): Contractor
    {
        $data = $this->registration($overrides);
        unset($data['password_confirmation']);

        return Contractor::create($data);
    }

    public function test_company_registration_is_active_and_uses_only_the_business_guard(): void
    {
        $this->post('/business/register', $this->registration(['discount_percent' => 99, 'is_active' => false]))
            ->assertRedirect(route('business.account'));

        $contractor = Contractor::sole();
        $this->assertAuthenticatedAs($contractor, 'business');
        $this->assertGuest('customer');
        $this->assertGuest('web');
        $this->assertTrue($contractor->is_active);
        $this->assertSame('0.00', $contractor->discount_percent);
        $this->assertTrue(Hash::check('Business-test-4321', $contractor->password));
        $this->assertFalse($contractor->isSoleProprietor());
        $this->assertArrayNotHasKey('password', $contractor->toArray());
    }

    public function test_individual_registration_has_no_kpp_even_when_one_is_supplied(): void
    {
        $this->post('/business/register', $this->registration([
            'inn' => self::SOLE_PROPRIETOR_INN, 'kpp' => 'ignored-invalid-value',
        ]))->assertRedirect(route('business.account'));

        $this->assertNull(Contractor::sole()->kpp);
        $this->assertTrue(Contractor::sole()->isSoleProprietor());
    }

    public function test_individual_registration_without_kpp(): void
    {
        $data = $this->registration(['inn' => self::SOLE_PROPRIETOR_INN]);
        unset($data['kpp']);
        $this->post('/business/register', $data)->assertRedirect(route('business.account'));
        $this->assertNull(Contractor::sole()->kpp);
    }

    public function test_identity_and_email_are_normalized_before_validation(): void
    {
        $this->post('/business/register', $this->registration([
            'inn' => ' 77 070 838 93 ', 'kpp' => ' 7707 ab 001 ', 'email' => ' BUSINESS@EXAMPLE.TEST ',
        ]))->assertRedirect(route('business.account'));
        $this->assertDatabaseHas('contractors', ['inn' => self::COMPANY_INN, 'kpp' => '7707AB001', 'email' => 'business@example.test']);
    }

    public function test_incorrect_inn_checksum_is_rejected_in_the_register_bag(): void
    {
        $this->from('/business')->post('/business/register', $this->registration(['inn' => '7707083894']))
            ->assertSessionHasErrors(['inn' => 'Некорректный ИНН'], null, 'register');
        $this->assertDatabaseCount('contractors', 0);
    }

    public function test_both_individual_inn_check_digits_are_validated(): void
    {
        foreach (['500100732249', '500100732258', '000000000000', '0000000000'] as $inn) {
            $this->assertTrue(Validator::make(['inn' => $inn], ['inn' => new ValidInn])->fails());
        }
    }

    public function test_kpp_format_is_rejected_and_company_kpp_is_required(): void
    {
        $this->post('/business/register', $this->registration(['kpp' => '1234ABC12']))
            ->assertSessionHasErrors(['kpp' => 'Некорректный КПП'], null, 'register');
        $this->post('/business/register', $this->registration(['kpp' => null]))
            ->assertSessionHasErrors(['kpp'], null, 'register');
        $this->assertTrue(Validator::make(['kpp' => '7707AB001'], ['kpp' => new ValidKpp])->passes());
    }

    public function test_duplicate_company_identity_is_rejected(): void
    {
        $this->contractor();
        $this->post('/business/register', $this->registration(['email' => 'other@example.test']))
            ->assertSessionHasErrors(['inn' => 'Компания с такими ИНН и КПП уже зарегистрирована'], null, 'register');
        $this->assertDatabaseCount('contractors', 1);
    }

    public function test_duplicate_individual_identity_with_null_kpp_is_rejected(): void
    {
        $this->contractor(['inn' => self::SOLE_PROPRIETOR_INN, 'kpp' => null]);
        $this->post('/business/register', $this->registration(['inn' => self::SOLE_PROPRIETOR_INN, 'email' => 'other@example.test']))
            ->assertSessionHasErrors(['inn'], null, 'register');
        $this->assertDatabaseCount('contractors', 1);
    }

    public function test_same_company_inn_with_a_different_kpp_is_allowed(): void
    {
        $this->contractor();
        $this->post('/business/register', $this->registration(['kpp' => '770701002', 'email' => 'branch@example.test']))
            ->assertRedirect(route('business.account'));
        $this->assertDatabaseCount('contractors', 2);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->contractor();
        $this->post('/business/register', $this->registration(['kpp' => '770701002']))
            ->assertSessionHasErrors(['email'], null, 'register');
    }

    public function test_password_confirmation_and_minimum_length_are_required(): void
    {
        $this->post('/business/register', $this->registration(['password_confirmation' => 'different']))
            ->assertSessionHasErrors(['password'], null, 'register');
        $this->post('/business/register', $this->registration(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors(['password'], null, 'register');
    }

    public function test_login_sets_last_login_at_and_supports_remember(): void
    {
        $contractor = $this->contractor();
        $this->post('/business/login', ['email' => $contractor->email, 'password' => 'Business-test-4321', 'remember' => '1'])
            ->assertRedirect(route('business.account'));
        $this->assertAuthenticatedAs($contractor, 'business');
        $this->assertNotNull($contractor->fresh()->last_login_at);
        $this->assertNotNull($contractor->fresh()->remember_token);
        $this->assertGuest('customer');
    }

    public function test_login_redirects_to_the_intended_business_page(): void
    {
        $this->contractor();
        $this->withSession(['url.intended' => route('business.account')])->post('/business/login', [
            'email' => 'business@example.test', 'password' => 'Business-test-4321',
        ])->assertRedirect(route('business.account'));
    }

    public function test_inactive_contractor_cannot_log_in(): void
    {
        $this->contractor(['is_active' => false]);
        $this->post('/business/login', ['email' => 'business@example.test', 'password' => 'Business-test-4321'])
            ->assertSessionHasErrors(['email' => 'Учётная запись отключена, обратитесь к менеджеру'], null, 'login');
        $this->assertGuest('business');
    }

    public function test_wrong_password_is_rejected_in_the_login_bag(): void
    {
        $this->contractor();
        $this->post('/business/login', ['email' => 'business@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Неверный email или пароль'], null, 'login');
        $this->assertGuest('business');
    }

    public function test_guest_redirect_and_business_views(): void
    {
        $this->get('/business/account')->assertRedirect(route('business.home'));
        $this->get('/business')->assertOk()->assertViewIs('business.home');
        $contractor = $this->contractor();
        $this->actingAs($contractor, 'business')->get('/business')->assertRedirect(route('business.account'));
        $this->get('/business/account')->assertOk()->assertViewIs('business.account')->assertViewHas('contractor', fn ($value) => $value->is($contractor));
    }

    public function test_logout_clears_business_authentication(): void
    {
        $this->actingAs($this->contractor(), 'business')->post('/business/logout')->assertRedirect(route('business.home'));
        $this->assertGuest('business');
        $this->get('/business/account')->assertRedirect(route('business.home'));
    }

    public function test_login_is_throttled_after_five_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/business/login', ['email' => 'missing@example.test', 'password' => 'wrong-password'])->assertRedirect();
        }
        $this->post('/business/login', ['email' => 'missing@example.test', 'password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_retail_authentication_does_not_grant_access_to_business_account(): void
    {
        $customer = Customer::create(['first_name' => 'Retail', 'email' => 'retail@example.test', 'phone' => '9000000000', 'password' => 'Retail-test-4321']);
        $this->actingAs($customer, 'customer')->get('/business/account')->assertRedirect(route('business.home'));
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('business');
    }
}
