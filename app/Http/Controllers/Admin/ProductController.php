<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->orderBy('name');
        if ($request->filled('search')) {
            $term = '%' . $request->get('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('kind', 'like', $term)
                    ->orWhere('type', 'like', $term);
            });
        }
        $products = $query->paginate(20)->withQueryString();
        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'kind' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $product = new Product();
        $product->name = $validated['name'];
        $product->description = $validated['description'] ?? null;
        $product->kind = $validated['kind'] ?? null;
        $product->type = $validated['type'] ?? null;
        $product->estimated_cost = $validated['estimated_cost'] ?? null;
        if ($request->hasFile('photo')) {
            $product->photo_path = $request->file('photo')->store('products', 'public');
        }
        $product->save();
        return redirect()->route('admin.products.index')->with('success', 'Товар создан.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'kind' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);
        $product->update($validated);
        return redirect()->route('admin.products.index')->with('success', 'Товар обновлён.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Товар удалён.');
    }

    public function uploadPhoto(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }
        $path = $request->file('photo')->store('products', 'public');
        $product->update(['photo_path' => $path]);
        return back()->with('success', 'Фото загружено.');
    }

    public function deletePhoto(Product $product): RedirectResponse
    {
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
            $product->update(['photo_path' => null]);
        }
        return back()->with('success', 'Фото удалено.');
    }
}
