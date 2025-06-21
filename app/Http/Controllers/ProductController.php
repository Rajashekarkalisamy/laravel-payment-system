<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Auth::user()->products()->create($request->only('name', 'quantity'));
        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product) {
        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);

        $product->update($request->only('name', 'quantity'));
        return response()->json($product);
    }
}
