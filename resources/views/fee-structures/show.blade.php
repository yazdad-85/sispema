@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Detail Struktur Biaya</h3>
                    <div>
                        <a href="{{ route('fee-structures.edit', $feeStructure->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('fee-structures.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Lembaga:</strong></td>
                                    <td>{{ $feeStructure->institution->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kelas:</strong></td>
                                    <td>{{ $feeStructure->class->class_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tahun Ajaran:</strong></td>
                                    <td>
                                        {{ $feeStructure->academicYear->year_start ?? '' }}-{{ $feeStructure->academicYear->year_end ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $feeStructure->is_active ? 'success' : 'danger' }} text-white">
                                            {{ $feeStructure->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Biaya Bulanan:</strong></td>
                                    <td>
                                        <span class="h5 text-primary">
                                            Rp {{ number_format($feeStructure->monthly_amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Biaya Tahunan:</strong></td>
                                    <td>
                                        <span class="h5 text-success">
                                            Rp {{ number_format($feeStructure->yearly_amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Diskon Beasiswa:</strong></td>
                                    <td>
                                        @if($feeStructure->scholarship_discount > 0)
                                            <span class="badge bg-warning text-dark">
                                                {{ $feeStructure->scholarship_discount }}%
                                            </span>
                                        @else
                                            <span class="badge bg-secondary text-white">0%</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Dibuat:</strong></td>
                                    <td>{{ $feeStructure->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($feeStructure->description)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Deskripsi</h5>
                            <p>{{ $feeStructure->description }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Informasi Tambahan</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-info">
                                            {{ $feeStructure->monthly_amount > 0 ? number_format($feeStructure->monthly_amount / 1000, 1) : 0 }}K
                                        </h4>
                                        <p class="text-muted">Biaya per Bulan (Rata-rata)</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-success">
                                            {{ $feeStructure->yearly_amount > 0 ? number_format($feeStructure->yearly_amount / 1000000, 1) : 0 }}M
                                        </h4>
                                        <p class="text-muted">Total per Tahun</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-warning">
                                            {{ $feeStructure->scholarship_discount }}%
                                        </h4>
                                        <p class="text-muted">Diskon Beasiswa</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Smart Payment Summary --}}
                        <div class="col-12 mt-4">
                            <h5>Informasi Pembayaran Bulanan</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Informasi:</strong> Sistem menggunakan perhitungan cerdas untuk menghindari kembalian yang tidak praktis. 
                                Bulan pertama (Juli) memiliki jumlah yang berbeda dari bulan-bulan lainnya.
                            </div>
                            
                            @php
                                $smartDistribution = \App\Models\FeeStructure::calculateSmartMonthlyDistribution($feeStructure->yearly_amount);
                                $monthlyBreakdown = $smartDistribution['monthly_breakdown'];
                            @endphp
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-primary">Bulan Pertama (Juli)</h6>
                                            <h4 class="card-text text-primary">
                                                Rp {{ number_format($monthlyBreakdown['Juli'], 0, ',', '.') }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-secondary">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Bulan Reguler (Agustus-Juni)</h6>
                                            <h4 class="card-text text-dark">
                                                Rp {{ number_format($monthlyBreakdown['Agustus'], 0, ',', '.') }}
                                            </h4>
                                            <small class="text-muted">x 11 bulan</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-success mt-3">
                                <strong>Total:</strong> Rp {{ number_format($smartDistribution['total_calculated'], 0, ',', '.') }} 
                                ({{ $smartDistribution['difference'] == 0 ? 'Sesuai dengan biaya tahunan' : 'Selisih: Rp ' . number_format($smartDistribution['difference'], 0, ',', '.') }})
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
