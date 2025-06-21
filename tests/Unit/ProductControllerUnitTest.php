<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductControllerUnitTest extends TestCase
{
    public function test_authenticated_user_can_create_product()
    {
        Auth::shouldReceive('id')->once()->andReturn(1);

        $productMock = \Mockery::mock('alias:' . Product::class);
        $productMock->shouldReceive('create')
            ->once()
            ->with(['name' => 'Book', 'quantity' => 10, 'user_id' => 1])
            ->andReturn(new Product(['name' => 'Book', 'quantity' => 10]));

        $request = new Request(['name' => 'Book', 'quantity' => 10]);

        $controller = new ProductController();
        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_only_owner_can_update_product()
    {
        Auth::shouldReceive('id')->once()->andReturn(1);

        $product = new Product(['id' => 5, 'user_id' => 1, 'name' => 'Old Name', 'quantity' => 5]);

        $productMock = \Mockery::mock('alias:' . Product::class);
        $productMock->shouldReceive('find')->with(5)->once()->andReturn($product);

        $request = new Request(['name' => 'New Name', 'quantity' => 10]);

        $controller = new ProductController();
        $response = $controller->update($request, 5);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('New Name', $response->getData()->name);
    }
}
