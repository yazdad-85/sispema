<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderBy('year_start', 'desc')->get();
        return view('academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('academic-years.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
            'status' => 'required|in:active,inactive',
            'is_current' => 'nullable',
            'description' => 'nullable|string|max:255',
        ]);

        // Checkbox: set boolean explicitly
        $validated['is_current'] = $request->has('is_current');

        // Jika tahun ajaran baru diset sebagai current, hapus status current dari yang lain
        if ($validated['is_current']) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        AcademicYear::create($validated);

        return redirect()->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil ditambahkan');
    }

    public function show(AcademicYear $academicYear)
    {
        $academicYear->load(['students', 'classes', 'feeStructures']);
        return view('academic-years.show', compact('academicYear'));
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
            'status' => 'required|in:active,inactive',
            'is_current' => 'nullable',
            'description' => 'nullable|string|max:255',
        ]);

        // Checkbox: set boolean explicitly
        $validated['is_current'] = $request->has('is_current');

        // Jika tahun ajaran baru diset sebagai current, hapus status current dari yang lain
        if ($validated['is_current'] && !$academicYear->is_current) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        $academicYear->update($validated);

        return redirect()->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil diperbarui');
    }

    public function destroy(AcademicYear $academicYear)
    {
        // Cegah penghapusan jika masih menjadi Tahun Aktif
        if ($academicYear->is_current) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Tidak bisa menghapus: nonaktifkan status "Tahun Aktif" terlebih dahulu.');
        }

        // Cek apakah tahun ajaran masih digunakan di relasi lain
        $hasStudents = $academicYear->students()->exists();
        $hasClasses = $academicYear->classes()->exists();
        $hasFeeStructures = $academicYear->feeStructures()->exists();
        $hasBillingRecords = $academicYear->feeStructures()->whereHas('billingRecords')->exists();
        $hasPayments = $academicYear->feeStructures()->whereHas('billingRecords.payments')->exists();

        if ($hasStudents || $hasClasses || $hasFeeStructures || $hasBillingRecords || $hasPayments) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Tahun ajaran tidak dapat dihapus karena masih memiliki data terkait (siswa/kelas/struktur biaya/tagihan/pembayaran).');
        }

        $academicYear->delete();

        return redirect()->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil dihapus');
    }

    public function setCurrent(AcademicYear $academicYear)
    {
        // Hapus status current dari semua tahun ajaran
        AcademicYear::where('is_current', true)->update(['is_current' => false]);
        
        // Set tahun ajaran yang dipilih sebagai current
        $academicYear->update(['is_current' => true]);

        return redirect()->route('academic-years.index')
            ->with('success', 'Tahun ajaran ' . $academicYear->name . ' berhasil diset sebagai tahun ajaran aktif');
    }
}
