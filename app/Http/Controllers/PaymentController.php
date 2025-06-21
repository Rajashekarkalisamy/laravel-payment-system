<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function pay(Request $request)
    {
        $methods = Config::get('payment.methods');

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'payment_method' => ['required', 'string', Rule::in($methods)],
        ]);

        return DB::transaction(function () use ($request) {
            $product = Product::where('id', $request->product_id)->lockForUpdate()->first();

            // Validate ownership
            if ($product->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized: Not the product owner'], 403);
            }

            // Validate availability
            if ($product->quantity <= 0) {
                return response()->json(['error' => 'Product out of stock'], 400);
            }

            $product->quantity -= 1;
            $product->save();

            return response()->json([
                'message' => 'Payment initiated',
                'payment_method' => $request->payment_method
            ]);
        });
    }
}
