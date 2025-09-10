@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-tag me-2"></i>Detail Kategori: {{ $category->name }}
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>Nama Kategori:</strong></td>
                                    <td>{{ $category->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipe:</strong></td>
                                    <td>
                                        <span class="badge {{ $category->type === 'pemasukan' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($category->type) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $category->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Dibuat:</strong></td>
                                    <td>{{ $category->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Diupdate:</strong></td>
                                    <td>{{ $category->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Statistik Kegiatan</h5>
                                    <h2 class="text-primary">{{ $category->activityPlans->count() }}</h2>
                                    <p class="card-text">Total Rencana Kegiatan</p>
                                    
                                    @if($category->activityPlans->count() > 0)
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                Total Budget: <strong>Rp {{ number_format($category->activityPlans->sum('budget_amount'), 0, ',', '.') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($category->activityPlans->count() > 0)
                        <hr>
                        <h5 class="mb-3">Rencana Kegiatan Terkait</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Kegiatan</th>
                                        <th>Tahun Ajaran</th>
                                        <th>Budget</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($category->activityPlans as $plan)
                                    <tr>
                                        <td>{{ $plan->name }}</td>
                                        <td>{{ $plan->academicYear->year_start }}/{{ $plan->academicYear->year_end }}</td>
                                        <td>Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}</td>
                                        <td>{{ $plan->start_date->format('d/m/Y') }}</td>
                                        <td>{{ $plan->end_date->format('d/m/Y') }}</td>
                                        <td>
                                            <a href="{{ route('activity-plans.show', $plan) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
