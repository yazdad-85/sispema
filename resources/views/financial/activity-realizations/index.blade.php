@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Realisasi Kegiatan
                    </h3>
                    <a href="{{ route('activity-realizations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tambah Realisasi
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="plan_id" class="form-label">Rencana Kegiatan</label>
                                <select name="plan_id" id="plan_id" class="form-select">
                                    <option value="">Semua Rencana</option>
                                    @foreach($activityPlans as $plan)
                                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }} ({{ $plan->academicYear->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="transaction_type" class="form-label">Tipe Transaksi</label>
                                <select name="transaction_type" id="transaction_type" class="form-select">
                                    <option value="">Semua Tipe</option>
                                    <option value="debit" {{ request('transaction_type') == 'debit' ? 'selected' : '' }}>Debit</option>
                                    <option value="credit" {{ request('transaction_type') == 'credit' ? 'selected' : '' }}>Kredit</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Tanggal Mulai</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Tanggal Akhir</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="{{ route('activity-realizations.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Rencana Kegiatan</th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th>Tipe</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Bukti</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($realizations as $realization)
                                    <tr>
                                        <td>{{ $loop->iteration + ($realizations->currentPage() - 1) * $realizations->perPage() }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $realization->plan->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $realization->plan->academicYear->name }}</small>
                                            </div>
                                        </td>
                                        <td>{{ $realization->date->format('d/m/Y') }}</td>
                                        <td>{{ Str::limit($realization->description, 50) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $realization->transaction_type == 'debit' ? 'danger' : 'success' }}">
                                                {{ ucfirst($realization->transaction_type) }}
                                            </span>
                                        </td>
                                        <td class="fw-bold text-{{ $realization->transaction_type == 'debit' ? 'danger' : 'success' }}">
                                            Rp {{ number_format($realization->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $realization->status == 'confirmed' ? 'success' : 'warning' }}">
                                                {{ ucfirst($realization->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($realization->proof)
                                                <a href="{{ Storage::url($realization->proof) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('activity-realizations.show', $realization) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('activity-realizations.edit', $realization) }}" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('activity-realizations.destroy', $realization) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus realisasi ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>Tidak ada realisasi kegiatan ditemukan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($realizations->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $realizations->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
