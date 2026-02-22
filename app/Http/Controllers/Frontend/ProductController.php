<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->orderBy('name')->paginate(12);
        return view('frontend.products.index', compact('products'));
    }

    public function show(Product $product): View
    {
        return view('frontend.products.show', compact('product'));
    }
}
