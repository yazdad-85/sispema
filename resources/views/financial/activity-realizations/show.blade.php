@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-eye me-2"></i>Detail Realisasi Kegiatan
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('activity-realizations.edit', $realization) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('activity-realizations.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="200"><strong>Rencana Kegiatan:</strong></td>
                                        <td>
                                            <div>
                                                <strong>{{ $realization->plan->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $realization->plan->academicYear->name }}</small>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal:</strong></td>
                                        <td>{{ $realization->date->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Deskripsi:</strong></td>
                                        <td>{{ $realization->description }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipe Transaksi:</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $realization->transaction_type == 'debit' ? 'danger' : 'success' }} fs-6">
                                                {{ ucfirst($realization->transaction_type) }}
                                                @if($realization->transaction_type == 'debit')
                                                    (Pengeluaran)
                                                @else
                                                    (Pemasukan)
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Harga Satuan:</strong></td>
                                        <td class="fw-bold">Rp {{ number_format($realization->unit_price, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($realization->equivalent_1 || $realization->equivalent_2 || $realization->equivalent_3)
                                    <tr>
                                        <td><strong>Perhitungan:</strong></td>
                                        <td>
                                            @php
                                                $formula = 'Harga Satuan';
                                                if($realization->equivalent_1) $formula .= ' × ' . number_format($realization->equivalent_1, 0, ',', '.');
                                                if($realization->equivalent_2) $formula .= ' × ' . number_format($realization->equivalent_2, 0, ',', '.');
                                                if($realization->equivalent_3) $formula .= ' × ' . number_format($realization->equivalent_3, 0, ',', '.');
                                            @endphp
                                            {{ $formula }}
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Total Nominal:</strong></td>
                                        <td class="fw-bold fs-5 text-{{ $realization->transaction_type == 'debit' ? 'danger' : 'success' }}">
                                            Rp {{ number_format($realization->total_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $realization->status == 'confirmed' ? 'success' : 'warning' }} fs-6">
                                                {{ ucfirst($realization->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($realization->proof)
                                    <tr>
                                        <td><strong>Bukti Transaksi:</strong></td>
                                        <td>
                                            <a href="{{ Storage::url($realization->proof) }}" target="_blank" class="btn btn-outline-primary">
                                                <i class="fas fa-file me-1"></i>Lihat File
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Dibuat:</strong></td>
                                        <td>{{ $realization->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Diperbarui:</strong></td>
                                        <td>{{ $realization->updated_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Tambahan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Kategori:</strong><br>
                                        <span class="badge bg-{{ $realization->plan->category->type == 'pemasukan' ? 'success' : 'danger' }}">
                                            {{ $realization->plan->category->name }}
                                            ({{ ucfirst($realization->plan->category->type) }})
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Periode Rencana:</strong><br>
                                        <small class="text-muted">
                                            {{ $realization->plan->start_date->format('d/m/Y') }} - 
                                            {{ $realization->plan->end_date->format('d/m/Y') }}
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Budget Rencana:</strong><br>
                                        <span class="fw-bold text-primary">
                                            Rp {{ number_format($realization->plan->budget_amount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    
                                    @if($realization->is_auto_generated)
                                    <div class="alert alert-info">
                                        <i class="fas fa-robot me-1"></i>
                                        <small>Realisasi ini dibuat otomatis oleh sistem.</small>
                                    </div>
                                    @endif
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
