<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipCategory;
use Illuminate\Http\Request;

class ScholarshipCategoryController extends Controller
{
    public function index()
    {
        $categories = ScholarshipCategory::orderBy('name')->get();
        return view('scholarship-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('scholarship-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:scholarship_categories',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        ScholarshipCategory::create($data);

        return redirect()->route('scholarship-categories.index')
            ->with('success', 'Kategori beasiswa berhasil ditambahkan');
    }

    public function show(ScholarshipCategory $scholarshipCategory)
    {
        return view('scholarship-categories.show', compact('scholarshipCategory'));
    }

    public function edit(ScholarshipCategory $scholarshipCategory)
    {
        return view('scholarship-categories.edit', compact('scholarshipCategory'));
    }

    public function update(Request $request, ScholarshipCategory $scholarshipCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:scholarship_categories,name,' . $scholarshipCategory->id,
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        $scholarshipCategory->update($data);

        return redirect()->route('scholarship-categories.index')
            ->with('success', 'Kategori beasiswa berhasil diperbarui');
    }

    public function destroy(ScholarshipCategory $scholarshipCategory)
    {
        // Cek apakah ada siswa yang menggunakan kategori ini
        if ($scholarshipCategory->students()->count() > 0) {
            return redirect()->route('scholarship-categories.index')
                ->with('error', 'Kategori beasiswa tidak dapat dihapus karena masih digunakan oleh siswa');
        }

        $scholarshipCategory->delete();

        return redirect()->route('scholarship-categories.index')
            ->with('success', 'Kategori beasiswa berhasil dihapus');
    }
}
