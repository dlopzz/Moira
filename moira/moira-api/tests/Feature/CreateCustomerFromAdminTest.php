<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * customers.password es NOT NULL y el form del admin no tenía campo de
 * contraseña: crear un cliente reventaba con una violación de constraint.
 */
class CreateCustomerFromAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_creates_a_customer_without_typing_a_password(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'first_name' => 'Ada',
                'last_name'  => 'Lovelace',
                'email'      => 'ada@test.local',
                'is_active'  => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::where('email', 'ada@test.local')->firstOrFail();

        // Contraseña aleatoria: existe (la columna es NOT NULL) pero es inusable.
        $this->assertNotEmpty($customer->password);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertTrue($customer->is_active);
    }

    public function test_creates_a_customer_with_an_explicit_password_and_it_can_log_in(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'first_name' => 'Grace',
                'last_name'  => 'Hopper',
                'email'      => 'grace@test.local',
                'password'   => 'Secret12345',
                'is_active'  => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::where('email', 'grace@test.local')->firstOrFail();

        // Hasheada una sola vez: el cast 'hashed' del modelo se encarga.
        $this->assertTrue(Hash::check('Secret12345', $customer->password));

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'grace@test.local',
            'password' => 'Secret12345',
        ])->assertOk();
    }

    public function test_creates_an_admin_user(): void
    {
        // UserResource::canViewAny() exige Super Admin; el factory por defecto
        // crea un Customer, que ni siquiera puede entrar al panel.
        $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Alan',
                'last_name'  => 'Turing',
                'username'   => 'aturing',
                'email'      => 'alan@test.local',
                'role'       => 'admin',
                'password'   => 'Secret12345',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'alan@test.local']);
    }

    public function test_the_selected_role_is_respected(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Edith',
                'last_name'  => 'Clarke',
                'username'   => 'eclarke',
                'email'      => 'edith@test.local',
                'role'       => Role::Editor->value,
                'password'   => 'Secret12345',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(
            Role::Editor,
            User::where('email', 'edith@test.local')->firstOrFail()->role,
        );
    }
}
