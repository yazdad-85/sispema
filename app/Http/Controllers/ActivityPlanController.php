<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityPlan;
use App\Models\Category;
use App\Models\AcademicYear;

class ActivityPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityPlan::with(['academicYear', 'category', 'institution']);

        // Filter by academic year
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by institution
        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $perPage = $request->get('per_page', 15);
        $perPage = in_array($perPage, [15, 25, 50, 100]) ? $perPage : 15;
        
        $activityPlans = $query->orderBy('start_date', 'desc')->paginate($perPage);
        
        $academicYears = AcademicYear::where('status', 'active')->get();
        $categories = Category::active()->get();

        return view('financial.activity-plans.index', compact('activityPlans', 'academicYears', 'categories'));
    }

    public function create()
    {
        $academicYears = AcademicYear::where('status', 'active')->get();
        $categories = Category::active()->get();
        
        return view('financial.activity-plans.create', compact('academicYears', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'equivalent_1' => 'nullable|numeric|min:0',
            'equivalent_2' => 'nullable|numeric|min:0',
            'equivalent_3' => 'nullable|numeric|min:0',
            'unit_1' => 'nullable|string|max:50',
            'unit_2' => 'nullable|string|max:50',
            'unit_3' => 'nullable|string|max:50'
        ]);

        ActivityPlan::create($request->all());

        return redirect()->route('activity-plans.index')
            ->with('success', 'Rencana kegiatan berhasil ditambahkan.');
    }

    public function show(ActivityPlan $activityPlan)
    {
        $activityPlan->load(['academicYear', 'category', 'realizations']);
        
        return view('financial.activity-plans.show', compact('activityPlan'));
    }

    public function edit(ActivityPlan $activityPlan)
    {
        $academicYears = AcademicYear::where('status', 'active')->get();
        $categories = Category::active()->get();
        
        return view('financial.activity-plans.edit', compact('activityPlan', 'academicYears', 'categories'));
    }

    public function update(Request $request, ActivityPlan $activityPlan)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'equivalent_1' => 'nullable|numeric|min:0',
            'equivalent_2' => 'nullable|numeric|min:0',
            'equivalent_3' => 'nullable|numeric|min:0',
            'unit_1' => 'nullable|string|max:50',
            'unit_2' => 'nullable|string|max:50',
            'unit_3' => 'nullable|string|max:50'
        ]);

        $activityPlan->update($request->all());

        return redirect()->route('activity-plans.index')
            ->with('success', 'Rencana kegiatan berhasil diperbarui.');
    }

    public function destroy(ActivityPlan $activityPlan)
    {
        // Check if plan has realizations
        if ($activityPlan->realizations()->count() > 0) {
            return redirect()->route('activity-plans.index')
                ->with('error', 'Tidak dapat menghapus rencana kegiatan yang sudah memiliki realisasi.');
        }

        $activityPlan->delete();

        return redirect()->route('activity-plans.index')
            ->with('success', 'Rencana kegiatan berhasil dihapus.');
    }
}
