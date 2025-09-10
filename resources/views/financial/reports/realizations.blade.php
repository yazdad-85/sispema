@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Laporan Realisasi Kegiatan
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('financial-reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                        <a href="{{ route('financial-reports.realizations', ['export_pdf' => 1] + request()->query()) }}" class="btn btn-success">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="academic_year_id" class="form-label">Tahun Ajaran</label>
                                <select name="academic_year_id" id="academic_year_id" class="form-select">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="{{ route('financial-reports.realizations') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Rencana</h6>
                                            <h4 class="mb-0">{{ $activityPlans->count() }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clipboard-list fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Realisasi Pemasukan</h6>
                                            <h4 class="mb-0">Rp {{ number_format($activityPlans->where('category.type', 'pemasukan')->sum('total_realization'), 0, ',', '.') }}</h4>
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
                                            <h6 class="card-title">Realisasi Pengeluaran</h6>
                                            <h4 class="mb-0">Rp {{ number_format($activityPlans->where('category.type', 'pengeluaran')->sum('total_realization'), 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-down fa-2x"></i>
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
                                            <h6 class="card-title">Net Realisasi</h6>
                                            @php
                                                $totalPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('total_realization');
                                                $totalPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('total_realization');
                                                $netRealisasi = $totalPemasukan - $totalPengeluaran;
                                            @endphp
                                            <h4 class="mb-0 {{ $netRealisasi >= 0 ? 'text-success' : 'text-warning' }}">
                                                Rp {{ number_format($netRealisasi, 0, ',', '.') }}
                                            </h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-balance-scale fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kegiatan</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Kategori</th>
                                    <th>Budget Pemasukan</th>
                                    <th>Budget Pengeluaran</th>
                                    <th>Realisasi Pemasukan</th>
                                    <th>Realisasi Pengeluaran</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activityPlans as $plan)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $plan->name }}</strong>
                                                @if($plan->description)
                                                    <br><small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $plan->academicYear->name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $plan->category->type == 'pemasukan' ? 'success' : 'danger' }}">
                                                {{ $plan->category->name }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            @if($plan->category->type == 'pemasukan')
                                                <span class="fw-bold text-success">Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($plan->category->type == 'pengeluaran')
                                                <span class="fw-bold text-danger">Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($plan->category->type == 'pemasukan')
                                                <span class="fw-bold text-success">Rp {{ number_format($plan->total_realization, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($plan->category->type == 'pengeluaran')
                                                <span class="fw-bold text-danger">Rp {{ number_format($plan->total_realization, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar 
                                                    @if($plan->realization_percentage >= 100) bg-success
                                                    @elseif($plan->realization_percentage >= 75) bg-info
                                                    @elseif($plan->realization_percentage >= 50) bg-warning
                                                    @else bg-danger
                                                    @endif" 
                                                    role="progressbar" 
                                                    style="width: {{ min($plan->realization_percentage, 100) }}%"
                                                    aria-valuenow="{{ $plan->realization_percentage }}" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    {{ number_format($plan->realization_percentage, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($plan->realization_percentage >= 100)
                                                <span class="badge bg-success">Selesai</span>
                                            @elseif($plan->realization_percentage >= 75)
                                                <span class="badge bg-info">Hampir Selesai</span>
                                            @elseif($plan->realization_percentage >= 50)
                                                <span class="badge bg-warning">Sedang Berjalan</span>
                                            @elseif($plan->realization_percentage > 0)
                                                <span class="badge bg-danger">Baru Dimulai</span>
                                            @else
                                                <span class="badge bg-secondary">Belum Dimulai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>Tidak ada rencana kegiatan ditemukan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                
                                <!-- Total Row -->
                                @if($activityPlans->count() > 0)
                                    @php
                                        $totalBudgetPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('budget_amount');
                                        $totalBudgetPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('budget_amount');
                                        $totalRealisasiPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('total_realization');
                                        $totalRealisasiPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('total_realization');
                                        $netBudget = $totalBudgetPemasukan - $totalBudgetPengeluaran;
                                        $netRealisasi = $totalRealisasiPemasukan - $totalRealisasiPengeluaran;
                                    @endphp
                                    <tr class="table-dark">
                                        <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-right fw-bold text-success">Rp {{ number_format($totalBudgetPemasukan, 0, ',', '.') }}</td>
                                        <td class="text-right fw-bold text-danger">Rp {{ number_format($totalBudgetPengeluaran, 0, ',', '.') }}</td>
                                        <td class="text-right fw-bold text-success">Rp {{ number_format($totalRealisasiPemasukan, 0, ',', '.') }}</td>
                                        <td class="text-right fw-bold text-danger">Rp {{ number_format($totalRealisasiPengeluaran, 0, ',', '.') }}</td>
                                        <td colspan="2" class="text-center fw-bold {{ $netRealisasi >= 0 ? 'text-success' : 'text-warning' }}">
                                            NET: Rp {{ number_format($netRealisasi, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
