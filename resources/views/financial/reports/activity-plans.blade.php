@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>Laporan Rencana Kegiatan
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('financial-reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                        <a href="{{ route('financial-reports.activity-plans', ['export_pdf' => 1] + request()->query()) }}" class="btn btn-success">
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
                                    <a href="{{ route('financial-reports.activity-plans') }}" class="btn btn-secondary">
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
                                            <h6 class="card-title">Total Pemasukan</h6>
                                            <h4 class="mb-0">Rp {{ number_format($activityPlans->where('category.type', 'pemasukan')->sum('budget_amount'), 0, ',', '.') }}</h4>
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
                                            <h4 class="mb-0">Rp {{ number_format($activityPlans->where('category.type', 'pengeluaran')->sum('budget_amount'), 0, ',', '.') }}</h4>
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
                                            <h6 class="card-title">Net Budget</h6>
                                            @php
                                                $totalPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('budget_amount');
                                                $totalPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('budget_amount');
                                                $netBudget = $totalPemasukan - $totalPengeluaran;
                                            @endphp
                                            <h4 class="mb-0 {{ $netBudget >= 0 ? 'text-success' : 'text-warning' }}">
                                                Rp {{ number_format($netBudget, 0, ',', '.') }}
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
                                    <th>Periode</th>
                                    <th>Pemasukan</th>
                                    <th>Pengeluaran</th>
                                    <th>Perhitungan</th>
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
                                        <td>
                                            <div>
                                                <small class="text-muted">Mulai:</small><br>
                                                {{ $plan->start_date->format('d/m/Y') }}
                                                <br>
                                                <small class="text-muted">Selesai:</small><br>
                                                {{ $plan->end_date->format('d/m/Y') }}
                                            </div>
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
                                        <td>
                                            @if($plan->unit_price)
                                                <small class="text-muted">
                                                    @php
                                                        $formula = 'Rp ' . number_format($plan->unit_price, 0, ',', '.');
                                                        if($plan->equivalent_1) $formula .= ' × ' . number_format($plan->equivalent_1, 0, ',', '.') . ' ' . ($plan->unit_1 ?? 'pax');
                                                        if($plan->equivalent_2) $formula .= ' × ' . number_format($plan->equivalent_2, 0, ',', '.') . ' ' . ($plan->unit_2 ?? 'orang');
                                                        if($plan->equivalent_3) $formula .= ' × ' . number_format($plan->equivalent_3, 0, ',', '.') . ' ' . ($plan->unit_3 ?? 'kegiatan');
                                                    @endphp
                                                    {{ $formula }}
                                                </small>
                                            @else
                                                <span class="text-muted">Manual</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $now = now();
                                                $isActive = $now->between($plan->start_date, $plan->end_date);
                                                $isUpcoming = $now->lt($plan->start_date);
                                                $isCompleted = $now->gt($plan->end_date);
                                            @endphp
                                            
                                            @if($isActive)
                                                <span class="badge bg-success">Aktif</span>
                                            @elseif($isUpcoming)
                                                <span class="badge bg-warning">Akan Datang</span>
                                            @elseif($isCompleted)
                                                <span class="badge bg-secondary">Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
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
                                        $totalPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('budget_amount');
                                        $totalPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('budget_amount');
                                        $netBudget = $totalPemasukan - $totalPengeluaran;
                                    @endphp
                                    <tr class="table-dark">
                                        <td colspan="5" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-right fw-bold text-success">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                                        <td class="text-right fw-bold text-danger">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                                        <td colspan="2" class="text-center fw-bold {{ $netBudget >= 0 ? 'text-success' : 'text-warning' }}">
                                            NET: Rp {{ number_format($netBudget, 0, ',', '.') }}
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
