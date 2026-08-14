<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El cliente pidió sacar la eliminación de cuentas y dejar solo suspensión:
 * borrar deja el email bloqueado por el índice único de customers, así que la
 * persona no puede volver a registrarse ni el admin recrearle la cuenta.
 */
class CustomerSuspensionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'first_name'        => 'Ada',
            'last_name'         => 'Lovelace',
            'email'             => 'ada@test.local',
            'password'          => bcrypt('secret1234'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ], $attributes));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_suspended_customer_cannot_log_in(): void
    {
        $customer = $this->makeCustomer();
        $customer->suspend();

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'ada@test.local',
            'password' => 'secret1234',
        ])->assertStatus(403);
    }

    public function test_suspending_revokes_existing_sessions(): void
    {
        $customer = $this->makeCustomer();
        $token    = $customer->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile')
            ->assertOk();

        $customer->suspend();

        // El guard queda resuelto en el container tras el request anterior; sin
        // esto el segundo request reusaría ese usuario en vez de revalidar el token.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    public function test_lowering_the_active_toggle_also_revokes_sessions(): void
    {
        $customer = $this->makeCustomer();
        $customer->createToken('api');

        $customer->update(['is_active' => false]);

        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_reactivated_customer_can_log_in_again(): void
    {
        $customer = $this->makeCustomer();
        $customer->suspend();
        $customer->update(['is_active' => true]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'ada@test.local',
            'password' => 'secret1234',
        ])->assertOk();
    }

    public function test_suspension_keeps_the_customer_and_its_email_usable(): void
    {
        $customer = $this->makeCustomer();
        $customer->suspend();

        // Suspender no borra: la fila sigue visible sin necesidad de onlyTrashed.
        $this->assertFalse($customer->fresh()->trashed());
        $this->assertDatabaseHas('customers', [
            'email'      => 'ada@test.local',
            'is_active'  => false,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_suspend_and_reactivate_from_the_customers_table(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();

        Livewire::test(ListCustomers::class)
            ->callAction(TestAction::make('suspender')->table($customer));

        $this->assertFalse($customer->fresh()->is_active);

        Livewire::test(ListCustomers::class)
            ->callAction(TestAction::make('reactivar')->table($customer));

        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_delete_actions_are_gone_from_the_admin(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();

        Livewire::test(ListCustomers::class)
            ->assertActionDoesNotExist(TestAction::make('delete')->table($customer))
            ->assertActionDoesNotExist(TestAction::make('forceDelete')->table($customer));

        Livewire::test(EditCustomer::class, ['record' => $customer->id])
            ->assertActionDoesNotExist('delete')
            ->assertActionDoesNotExist('forceDelete')
            ->assertActionExists('suspender');
    }
}
