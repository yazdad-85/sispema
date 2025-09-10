@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Detail Pembayaran</h3>
                    <div>
                        @if($payment->status === 'pending' && in_array($payment->payment_method, ['transfer', 'qris']))
                            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#verificationModal">
                                <i class="fas fa-check-circle"></i> Verifikasi Pembayaran
                            </button>
                        @endif
                        <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-success">
                            <i class="fas fa-print"></i> Cetak Kwitansi
                        </a>
                        <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Informasi Pembayaran</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>No. Kwitansi:</strong></td>
                                    <td>{{ $payment->receipt_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Pembayaran:</strong></td>
                                    <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y H:i') : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah Pembayaran:</strong></td>
                                    <td>
                                        <span class="h4 text-success">
                                            Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Metode Pembayaran:</strong></td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            {{ ucfirst($payment->payment_method ?? 'Cash') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $payment->status_class }} text-white">
                                            {{ $payment->status_text }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Informasi Siswa</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>NIS:</strong></td>
                                    <td>{{ $payment->student->nis ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nama:</strong></td>
                                    <td>{{ $payment->student->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Lembaga:</strong></td>
                                    <td>{{ $payment->student->institution->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kelas:</strong></td>
                                    <td>{{ $payment->student->classRoom->class_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tahun Ajaran:</strong></td>
                                    <td>
                                        {{ $payment->student->academicYear->year_start ?? '' }}-{{ $payment->student->academicYear->year_end ?? '' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($payment->billingRecord)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Detail Tagihan</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Jumlah Tagihan</th>
                                            <th>Jumlah Bayar</th>
                                            <th>Sisa Tagihan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Rp {{ number_format($payment->billingRecord->amount ?? 0, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</td>
                                            <td>
                                                @php
                                                    $remaining = ($payment->billingRecord->amount ?? 0) - $payment->total_amount;
                                                @endphp
                                                <span class="badge bg-{{ $remaining > 0 ? 'warning' : 'success' }} text-white">
                                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($remaining > 0)
                                                    <span class="badge bg-warning text-white">Belum Lunas</span>
                                                @else
                                                    <span class="badge bg-success text-white">Lunas</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($payment->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Catatan</h5>
                            <p>{{ $payment->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Riwayat Status</h5>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Pembayaran Dibuat</h6>
                                        <p class="timeline-text">
                                            {{ $payment->created_at->format('d/m/Y H:i') }} - 
                                            Status: {{ ucfirst($payment->status) }}
                                        </p>
                                    </div>
                                </div>
                                @if($payment->updated_at != $payment->created_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Terakhir Diupdate</h6>
                                        <p class="timeline-text">
                                            {{ $payment->updated_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
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

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}
</style>

<!-- Verification Modal -->
@if($payment->status === 'pending' && in_array($payment->payment_method, ['transfer', 'qris']))
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Verifikasi Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payments.verify', $payment->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="verification_status" class="form-label">Status Verifikasi <span class="text-danger">*</span></label>
                        <select class="form-control" id="verification_status" name="verification_status" required>
                            <option value="">Pilih Status</option>
                            <option value="verified">Terverifikasi (Pembayaran Masuk)</option>
                            <option value="failed">Gagal (Pembayaran Tidak Masuk)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Catatan Verifikasi</label>
                        <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" 
                                  placeholder="Contoh: Bukti transfer sudah diterima, atau alasan pembayaran gagal"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <strong>Informasi:</strong><br>
                        • <strong>Terverifikasi:</strong> Pembayaran berhasil masuk ke rekening, tagihan akan dikurangi<br>
                        • <strong>Gagal:</strong> Pembayaran tidak masuk, tagihan tidak terpengaruh
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Verifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
