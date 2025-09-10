@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>Detail Rencana Kegiatan: {{ $activityPlan->name }}
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('activity-plans.edit', $activityPlan) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('activity-plans.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>Nama Kegiatan:</strong></td>
                                    <td>{{ $activityPlan->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tahun Ajaran:</strong></td>
                                    <td>{{ $activityPlan->academicYear->year_start }}/{{ $activityPlan->academicYear->year_end }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kategori:</strong></td>
                                    <td>
                                        <span class="badge {{ $activityPlan->category->type === 'pemasukan' ? 'bg-success' : 'bg-warning' }}">
                                            {{ $activityPlan->category->name }} ({{ ucfirst($activityPlan->category->type) }})
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Mulai:</strong></td>
                                    <td>{{ $activityPlan->start_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Selesai:</strong></td>
                                    <td>{{ $activityPlan->end_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Budget:</strong></td>
                                    <td class="fw-bold text-primary">Rp {{ number_format($activityPlan->budget_amount, 0, ',', '.') }}</td>
                                </tr>
                                @if($activityPlan->unit_price)
                                <tr>
                                    <td><strong>Harga Satuan:</strong></td>
                                    <td>Rp {{ number_format($activityPlan->unit_price, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($activityPlan->equivalent_1 || $activityPlan->equivalent_2 || $activityPlan->equivalent_3)
                                <tr>
                                    <td><strong>Perhitungan:</strong></td>
                                    <td>
                                        @php
                                            $formula = 'Harga Satuan';
                                            if($activityPlan->equivalent_1) $formula .= ' × ' . number_format($activityPlan->equivalent_1, 0, ',', '.') . ' ' . ($activityPlan->unit_1 ?? 'pax');
                                            if($activityPlan->equivalent_2) $formula .= ' × ' . number_format($activityPlan->equivalent_2, 0, ',', '.') . ' ' . ($activityPlan->unit_2 ?? 'orang');
                                            if($activityPlan->equivalent_3) $formula .= ' × ' . number_format($activityPlan->equivalent_3, 0, ',', '.') . ' ' . ($activityPlan->unit_3 ?? 'kegiatan');
                                        @endphp
                                        {{ $formula }}
                                    </td>
                                </tr>
                                @endif
                                @if($activityPlan->description)
                                <tr>
                                    <td><strong>Deskripsi:</strong></td>
                                    <td>{{ $activityPlan->description }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Progress Realisasi</h5>
                                    <div class="progress mb-3" style="height: 30px;">
                                        <div class="progress-bar {{ $activityPlan->realization_percentage > 100 ? 'bg-danger' : ($activityPlan->realization_percentage > 80 ? 'bg-warning' : 'bg-success') }}" 
                                             role="progressbar" style="width: {{ min($activityPlan->realization_percentage, 100) }}%">
                                            {{ number_format($activityPlan->realization_percentage, 1) }}%
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h6 class="text-muted">Realisasi</h6>
                                            <h5 class="text-info">Rp {{ number_format($activityPlan->total_realization, 0, ',', '.') }}</h5>
                                        </div>
                                        <div class="col-6">
                                            <h6 class="text-muted">Sisa</h6>
                                            <h5 class="{{ $activityPlan->remaining_budget >= 0 ? 'text-success' : 'text-danger' }}">
                                                Rp {{ number_format($activityPlan->remaining_budget, 0, ',', '.') }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($activityPlan->realizations->count() > 0)
                        <hr>
                        <h5 class="mb-3">Realisasi Kegiatan</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Deskripsi</th>
                                        <th>Tipe</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activityPlan->realizations as $realization)
                                    <tr>
                                        <td>{{ $realization->date->format('d/m/Y') }}</td>
                                        <td>{{ $realization->description }}</td>
                                        <td>
                                            <span class="badge {{ $realization->transaction_type === 'debit' ? 'bg-success' : 'bg-warning' }}">
                                                {{ ucfirst($realization->transaction_type) }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($realization->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge {{ $realization->status === 'confirmed' ? 'bg-success' : 'bg-warning' }}">
                                                {{ ucfirst($realization->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('activity-realizations.show', $realization) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada realisasi kegiatan</p>
                            <a href="{{ route('activity-realizations.create', ['plan_id' => $activityPlan->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Tambah Realisasi
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
