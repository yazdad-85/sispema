@extends('layouts.app')

@section('content')
<style>
.bg-light-success {
    background-color: rgba(40, 167, 69, 0.1) !important;
    border-left: 3px solid #28a745;
}
.bg-light-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
    border-left: 3px solid #dc3545;
}
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="fas fa-balance-scale me-2"></i>Neraca Keuangan
                        </h3>
                        <small class="text-muted">
                            Periode: {{ request('start_date') ? date('d/m/Y', strtotime(request('start_date'))) : 'Semua' }} - 
                            {{ request('end_date') ? date('d/m/Y', strtotime(request('end_date'))) : 'Semua' }}
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('financial-reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                        <a href="{{ route('financial-reports.balance-sheet', ['export_pdf' => 1] + request()->query()) }}" class="btn btn-success">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Tanggal Akhir</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="{{ route('financial-reports.balance-sheet') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Neraca Keuangan -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-arrow-up me-2"></i>AKTIVA</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                                        <span class="fw-medium">Kas dan Bank</span>
                                        <span class="fw-bold text-primary fs-6">Rp {{ number_format($currentBalance, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                                        <span class="fw-medium">Piutang SPP</span>
                                        <span class="fw-bold text-primary fs-6">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <span class="fw-bold text-primary">TOTAL AKTIVA</span>
                                        <span class="fw-bold text-primary fs-5">Rp {{ number_format($currentBalance + $totalPiutang, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-arrow-down me-2"></i>PASIVA</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                                        <span class="fw-medium">Hutang Jangka Pendek</span>
                                        <span class="fw-bold text-success fs-6">Rp {{ number_format($totalHutang, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                                        <span class="fw-medium">Modal</span>
                                        <span class="fw-bold text-success fs-6">Rp {{ number_format($totalModal, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <span class="fw-bold text-success">TOTAL PASIVA</span>
                                        <span class="fw-bold text-success fs-5">Rp {{ number_format($totalHutang + $totalModal, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Laporan Laba Rugi -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Laporan Laba Rugi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success mb-3">PENDAPATAN</h6>
                                            @forelse($pemasukanCategories as $category)
                                                @php
                                                    $categoryTotal = $entries->where('reference_type', 'payment')
                                                        ->filter(function($entry) use ($category) {
                                                            if (!$entry->payment) return false;
                                                            $realization = App\Models\ActivityRealization::where('proof', $entry->payment->receipt_number)
                                                                ->whereHas('plan', function($query) use ($category) {
                                                                    $query->where('category_id', $category->id);
                                                                })
                                                                ->first();
                                                            return $realization !== null;
                                                        })
                                                        ->sum('credit');
                                                @endphp
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 {{ $categoryTotal > 0 ? 'bg-light-success' : 'bg-light' }}">
                                                    <span class="fw-medium">{{ $category->name }}</span>
                                                    <span class="fw-bold text-success">
                                                        @if($categoryTotal > 0)
                                                            Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">Rp 0</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-muted">Tidak ada pendapatan</p>
                                            @endforelse
                                            <div class="border-top pt-2 mt-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-success">Total Pendapatan</span>
                                                    <span class="fw-bold text-success fs-5">Rp {{ number_format($totalCredit, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h6 class="text-danger mb-3">BEBAN</h6>
                                            @forelse($pengeluaranCategories as $category)
                                                @php
                                                    $categoryTotal = $entries->where('reference_type', 'realization')
                                                        ->filter(function($entry) use ($category) {
                                                            if (!$entry->realization) return false;
                                                            $plan = $entry->realization->plan;
                                                            return $plan && $plan->category_id == $category->id;
                                                        })
                                                        ->sum('debit');
                                                @endphp
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 {{ $categoryTotal > 0 ? 'bg-light-danger' : 'bg-light' }}">
                                                    <span class="fw-medium">{{ $category->name }}</span>
                                                    <span class="fw-bold text-danger">
                                                        @if($categoryTotal > 0)
                                                            Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">Rp 0</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-muted">Tidak ada beban</p>
                                            @endforelse
                                            <div class="border-top pt-2 mt-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-danger">Total Beban</span>
                                                    <span class="fw-bold text-danger fs-5">Rp {{ number_format($totalDebit, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Laba Rugi Bersih -->
                                    <div class="border-top pt-3 mt-3">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                            <span class="fw-bold fs-5 {{ ($totalCredit - $totalDebit) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ ($totalCredit - $totalDebit) >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                                            </span>
                                            <span class="fw-bold fs-4 {{ ($totalCredit - $totalDebit) >= 0 ? 'text-success' : 'text-danger' }}">
                                                Rp {{ number_format($totalCredit - $totalDebit, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
