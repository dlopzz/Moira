<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $status): Order
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Cliente',
            'email'      => 'test'.uniqid().'@moira.test',
            'password'   => bcrypt('secret1234'),
            'is_active'  => true,
        ]);

        return Order::create([
            'customer_id'      => $customer->id,
            'shipping_address' => ['calle' => 'Falsa 123'],
            'subtotal'         => 1000,
            'total'            => 1000,
            'status'           => $status,
        ]);
    }

    public function test_can_revert_processing_to_paid(): void
    {
        $order = $this->makeOrder('processing');

        $order->transitionStatus('paid');

        $this->assertSame('paid', $order->status);
    }

    public function test_can_revert_delivered_back_to_processing(): void
    {
        $order = $this->makeOrder('delivered');

        $order->transitionStatus('processing');

        $this->assertSame('processing', $order->status);
    }

    public function test_forward_transition_still_works(): void
    {
        $order = $this->makeOrder('paid');

        $order->transitionStatus('shipped');

        $this->assertSame('shipped', $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_cancelled_is_terminal(): void
    {
        $order = $this->makeOrder('cancelled');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('No se puede pasar de cancelled a paid.');

        $order->transitionStatus('paid');
    }
}
