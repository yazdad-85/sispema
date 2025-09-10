<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('type')->orderBy('name')->paginate(15);
        return view('financial.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('financial.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:pemasukan,pengeluaran',
            'is_active' => 'boolean'
        ]);

        Category::create([
            'name' => $request->name,
            'type' => $request->type,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function show(Category $category)
    {
        return view('financial.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        return view('financial.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:pemasukan,pengeluaran',
            'is_active' => 'boolean'
        ]);

        $category->update([
            'name' => $request->name,
            'type' => $request->type,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        // Check if category is used in activity plans
        if ($category->activityPlans()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Tidak dapat menghapus kategori yang sedang digunakan dalam rencana kegiatan.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
