<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Livewire\Livewire;
use Tests\TestCase;

class SendPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'first_name' => 'Ada',
            'last_name'  => 'Lovelace',
            'email'      => 'ada@test.local',
            'password'   => bcrypt('secret1234'),
            'is_active'  => true,
        ]);
    }

    public function test_admin_sends_password_reset_email_to_customer(): void
    {
        Notification::fake();
        $customer = $this->makeCustomer();

        Livewire::test(EditCustomer::class, ['record' => $customer->id])
            ->callAction('sendPasswordReset');

        Notification::assertSentTo($customer, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $customer->email]);
    }

    public function test_second_immediate_request_is_throttled(): void
    {
        $customer = $this->makeCustomer();

        $component = Livewire::test(EditCustomer::class, ['record' => $customer->id]);
        $component->callAction('sendPasswordReset');
        $component->callAction('sendPasswordReset');

        // El broker de customers tiene throttle de 60s: el segundo intento no reenvía.
        $this->assertSame(
            1,
            \DB::table('password_reset_tokens')->where('email', $customer->email)->count(),
        );
    }
}
