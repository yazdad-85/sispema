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

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Pemasukan</h6>
                                            <h4 class="mb-0">Rp {{ number_format($totalCredit, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-up fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Pengeluaran</h6>
                                            <h4 class="mb-0">Rp {{ number_format($totalDebit, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-down fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Saldo Akhir</h6>
                                            <h4 class="mb-0">Rp {{ number_format($currentBalance, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-wallet fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Net Profit</h6>
                                            <h4 class="mb-0">Rp {{ number_format($totalCredit - $totalDebit, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Pemasukan per Kategori</h6>
                                </div>
                                <div class="card-body">
                                    @forelse($pemasukanCategories as $category)
                                        @php
                                            $categoryTotal = $entries->where('reference_type', 'payment')
                                                ->filter(function($entry) use ($category) {
                                                    if (!$entry->payment) return false;
                                                    // Cek apakah pembayaran ini memiliki realisasi dengan kategori yang sesuai
                                                    $realization = App\Models\ActivityRealization::where('proof', $entry->payment->receipt_number)
                                                        ->whereHas('plan', function($query) use ($category) {
                                                            $query->where('category_id', $category->id);
                                                        })
                                                        ->first();
                                                    return $realization !== null;
                                                })
                                                ->sum('credit');
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded {{ $categoryTotal > 0 ? 'bg-light-success' : 'bg-light' }}">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                                                </div>
                                                <span class="fw-medium">{{ $category->name }}</span>
                                            </div>
                                            <span class="fw-bold text-success fs-6">
                                                @if($categoryTotal > 0)
                                                    Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                                                @else
                                                    <span class="text-muted">Rp 0</span>
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-muted">Tidak ada kategori pemasukan</p>
                                    @endforelse
                                    
                                    <!-- Total Pemasukan -->
                                    <div class="border-top pt-3 mt-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-success">Total Pemasukan</span>
                                            <span class="fw-bold text-success fs-5">Rp {{ number_format($totalCredit, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0"><i class="fas fa-arrow-down me-2"></i>Pengeluaran per Kategori</h6>
                                </div>
                                <div class="card-body">
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
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded {{ $categoryTotal > 0 ? 'bg-light-danger' : 'bg-light' }}">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="fas fa-circle text-danger" style="font-size: 8px;"></i>
                                                </div>
                                                <span class="fw-medium">{{ $category->name }}</span>
                                            </div>
                                            <span class="fw-bold text-danger fs-6">
                                                @if($categoryTotal > 0)
                                                    Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                                                @else
                                                    <span class="text-muted">Rp 0</span>
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-muted">Tidak ada kategori pengeluaran</p>
                                    @endforelse
                                    
                                    <!-- Total Pengeluaran -->
                                    <div class="border-top pt-3 mt-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-danger">Total Pengeluaran</span>
                                            <span class="fw-bold text-danger fs-5">Rp {{ number_format($totalDebit, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Transaksi Terbaru</h6>
                            <span class="badge bg-primary">{{ $entries->count() }} transaksi</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">Tanggal</th>
                                            <th>Keterangan</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Kredit</th>
                                            <th class="text-end">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($entries->take(10) as $entry)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark">{{ $entry->date->format('d/m/Y') }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($entry->reference_type == 'payment')
                                                            <i class="fas fa-credit-card text-success me-2"></i>
                                                        @elseif($entry->reference_type == 'realization')
                                                            <i class="fas fa-receipt text-info me-2"></i>
                                                        @else
                                                            <i class="fas fa-file-invoice text-secondary me-2"></i>
                                                        @endif
                                                        <span>{{ Str::limit($entry->description, 50) }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    @if($entry->debit > 0)
                                                        <span class="text-danger fw-bold">Rp {{ number_format($entry->debit, 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($entry->credit > 0)
                                                        <span class="text-success fw-bold">Rp {{ number_format($entry->credit, 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-bold {{ $entry->balance < 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($entry->balance, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-3">
                                                    <span class="text-muted">Tidak ada transaksi</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
