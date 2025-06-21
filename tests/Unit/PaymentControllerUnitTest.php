<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Product;

class PaymentControllerUnitTest extends TestCase
{
    public function test_user_can_pay_for_own_product()
    {
        Auth::shouldReceive('id')->once()->andReturn(1);
        Config::shouldReceive('get')->once()->with('payment.methods')->andReturn(['card']);

        $product = new Product(['id' => 1, 'user_id' => 1, 'quantity' => 2]);

        $productMock = \Mockery::mock('overload:' . Product::class);
        $productMock->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $productMock->shouldReceive('lockForUpdate')->andReturnSelf();
        $productMock->shouldReceive('first')->andReturn($product);
        $productMock->shouldReceive('save')->once();

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($cb) { return $cb(); });

        $request = new Request(['product_id' => 1, 'payment_method' => 'card']);
        $controller = new PaymentController();
        $response = $controller->pay($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_payment_fails_for_wrong_owner()
    {
        Auth::shouldReceive('id')->once()->andReturn(2);
        Config::shouldReceive('get')->once()->andReturn(['card']);

        $product = new Product(['id' => 1, 'user_id' => 1, 'quantity' => 2]);

        $productMock = \Mockery::mock('overload:' . Product::class);
        $productMock->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $productMock->shouldReceive('lockForUpdate')->andReturnSelf();
        $productMock->shouldReceive('first')->andReturn($product);

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($cb) { return $cb(); });

        $request = new Request(['product_id' => 1, 'payment_method' => 'card']);
        $controller = new PaymentController();
        $response = $controller->pay($request);

        $this->assertEquals(403, $response->getStatusCode());
    }
}
