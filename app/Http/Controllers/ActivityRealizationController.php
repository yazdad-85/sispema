<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityRealization;
use App\Models\ActivityPlan;
use App\Models\CashBook;

class ActivityRealizationController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityRealization::with(['plan.academicYear', 'plan.category']);

        // Filter by plan
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        
        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $realizations = $query->orderBy('date', 'desc')->paginate(15);
        
        $activityPlans = ActivityPlan::with(['academicYear', 'category'])->get();

        return view('financial.activity-realizations.index', compact('realizations', 'activityPlans'));
    }

    public function create(Request $request)
    {
        $activityPlans = ActivityPlan::with(['academicYear', 'category'])->get();
        $selectedPlan = $request->get('plan_id');
        
        return view('financial.activity-realizations.create', compact('activityPlans', 'selectedPlan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:activity_plans,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'transaction_type' => 'required|in:debit,credit',
            'unit_price' => 'required|numeric|min:0',
            'equivalent_1' => 'nullable|numeric|min:0',
            'equivalent_2' => 'nullable|numeric|min:0',
            'equivalent_3' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'status' => 'nullable|in:pending,confirmed'
        ]);

        // Handle file upload
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('activity-realizations', 'public');
        }

        $realization = ActivityRealization::create([
            'plan_id' => $request->plan_id,
            'date' => $request->date,
            'description' => $request->description,
            'transaction_type' => $request->transaction_type,
            'unit_price' => $request->unit_price,
            'equivalent_1' => $request->equivalent_1 ?? 0,
            'equivalent_2' => $request->equivalent_2 ?? 0,
            'equivalent_3' => $request->equivalent_3 ?? 0,
            'total_amount' => $request->total_amount,
            'proof' => $proofPath,
            'status' => $request->status ?? 'confirmed',
            'is_auto_generated' => false
        ]);

        // Add to cash book only for manual realizations
        // Auto-generated realizations (from SPP payments) already have cash book entries
        if (!$realization->is_auto_generated) {
            $debit = $request->transaction_type === 'debit' ? $realization->total_amount : 0;
            $credit = $request->transaction_type === 'credit' ? $realization->total_amount : 0;

            CashBook::addEntry(
                $realization->date,
                "Realisasi: " . $realization->description,
                $debit,
                $credit,
                'realization',
                $realization->id
            );
        }

        return redirect()->route('activity-realizations.index')
            ->with('success', 'Realisasi kegiatan berhasil ditambahkan.');
    }

    public function show(ActivityRealization $realization)
    {
        $realization->load(['plan.academicYear', 'plan.category']);
        
        return view('financial.activity-realizations.show', compact('realization'));
    }

    public function edit(ActivityRealization $realization)
    {
        $activityPlans = ActivityPlan::with(['academicYear', 'category'])->get();
        
        return view('financial.activity-realizations.edit', compact('realization', 'activityPlans'));
    }

    public function update(Request $request, ActivityRealization $realization)
    {
        $request->validate([
            'plan_id' => 'required|exists:activity_plans,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'transaction_type' => 'required|in:debit,credit',
            'unit_price' => 'required|numeric|min:0',
            'equivalent_1' => 'nullable|numeric|min:0',
            'equivalent_2' => 'nullable|numeric|min:0',
            'equivalent_3' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'status' => 'nullable|in:pending,confirmed'
        ]);

        // Handle file upload
        $proofPath = $realization->proof;
        if ($request->hasFile('proof')) {
            // Delete old file if exists
            if ($realization->proof && \Storage::disk('public')->exists($realization->proof)) {
                \Storage::disk('public')->delete($realization->proof);
            }
            $proofPath = $request->file('proof')->store('activity-realizations', 'public');
        }

        $realization->update([
            'plan_id' => $request->plan_id,
            'date' => $request->date,
            'description' => $request->description,
            'transaction_type' => $request->transaction_type,
            'unit_price' => $request->unit_price,
            'equivalent_1' => $request->equivalent_1 ?? 0,
            'equivalent_2' => $request->equivalent_2 ?? 0,
            'equivalent_3' => $request->equivalent_3 ?? 0,
            'total_amount' => $request->total_amount,
            'proof' => $proofPath,
            'status' => $request->status ?? $realization->status
        ]);

        // Update cash book entry only for manual realizations
        if (!$realization->is_auto_generated) {
            $cashBookEntry = CashBook::byReference('realization', $realization->id)->first();
            if ($cashBookEntry) {
                $debit = $request->transaction_type === 'debit' ? $realization->total_amount : 0;
                $credit = $request->transaction_type === 'credit' ? $realization->total_amount : 0;

                $cashBookEntry->update([
                    'date' => $realization->date,
                    'description' => "Realisasi: " . $realization->description,
                    'debit' => $debit,
                    'credit' => $credit
                ]);

                // Recalculate balance for all entries after this one
                $this->recalculateCashBookBalance();
            }
        }

        return redirect()->route('activity-realizations.index')
            ->with('success', 'Realisasi kegiatan berhasil diperbarui.');
    }

    public function destroy(ActivityRealization $realization)
    {
        // Remove from cash book only for manual realizations
        if (!$realization->is_auto_generated) {
            $cashBookEntry = CashBook::byReference('realization', $realization->id)->first();
            if ($cashBookEntry) {
                $cashBookEntry->delete();
                $this->recalculateCashBookBalance();
            }
        }

        $realization->delete();

        return redirect()->route('activity-realizations.index')
            ->with('success', 'Realisasi kegiatan berhasil dihapus.');
    }

    private function recalculateCashBookBalance()
    {
        $entries = CashBook::orderBy('date')->orderBy('id')->get();
        $balance = 0;

        foreach ($entries as $entry) {
            $balance = $balance + $entry->credit - $entry->debit; // Credit adds, debit subtracts
            $entry->update(['balance' => $balance]);
        }
    }
}
