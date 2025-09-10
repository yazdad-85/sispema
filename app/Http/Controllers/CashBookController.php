<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashBook;

class CashBookController extends Controller
{
    public function index(Request $request)
    {
        $query = CashBook::query();

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $entries = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        
        $currentBalance = CashBook::getCurrentBalance();

        return view('financial.cash-book.index', compact('entries', 'currentBalance'));
    }

    public function create()
    {
        return view('financial.reports.cash-book-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
        ]);

        // Ensure at least one of debit or credit is provided
        if (!$request->debit && !$request->credit) {
            return back()->withErrors(['debit' => 'Minimal salah satu dari debit atau kredit harus diisi.']);
        }

        CashBook::addEntry(
            $request->date,
            $request->description,
            $request->debit ?? 0,
            $request->credit ?? 0,
            'manual',
            null
        );

        return redirect()->route('financial-reports.cash-book')
            ->with('success', 'Entri buku kas berhasil ditambahkan.');
    }

    public function destroy(CashBook $cashBook)
    {
        // Only allow deletion of manual entries
        if ($cashBook->reference_type !== 'manual') {
            return redirect()->route('cash-book.index')
                ->with('error', 'Tidak dapat menghapus entri yang dibuat otomatis.');
        }

        $cashBook->delete();

        // Recalculate balance
        $this->recalculateBalance();

        return redirect()->route('financial-reports.cash-book')
            ->with('success', 'Entri buku kas berhasil dihapus.');
    }

    private function recalculateBalance()
    {
        $entries = CashBook::orderBy('date')->orderBy('id')->get();
        $balance = 0;

        foreach ($entries as $entry) {
            $balance = $balance + $entry->credit - $entry->debit;
            $entry->update(['balance' => $balance]);
        }
    }
}
