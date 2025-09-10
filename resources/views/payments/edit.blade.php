@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Pembayaran</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.update', $payment->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">Siswa</label>
                                    <input type="text" class="form-control" value="{{ $payment->student->nis }} - {{ $payment->student->name }}" readonly>
                                    <small class="form-text text-muted">Siswa tidak dapat diubah</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_record_id">Tagihan Bulan</label>
                                    <input type="text" class="form-control" value="{{ $payment->billingRecord->billing_month ?? '-' }}" readonly>
                                    <small class="form-text text-muted">Tagihan tidak dapat diubah</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                           id="payment_date" name="payment_date" 
                                           value="{{ old('payment_date', $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                    @error('payment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_method">Metode Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-control @error('payment_method') is-invalid @enderror" 
                                            id="payment_method" name="payment_method" required>
                                        <option value="">Pilih Metode</option>
                                        <option value="cash" {{ old('payment_method', $payment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="transfer" {{ old('payment_method', $payment->payment_method) == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                                        <option value="qris" {{ old('payment_method', $payment->payment_method) == 'qris' ? 'selected' : '' }}>QRIS</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('total_amount') is-invalid @enderror" 
                                           id="total_amount" name="total_amount" 
                                           value="{{ old('total_amount', number_format($payment->total_amount, 0, ',', '.')) }}" placeholder="Contoh: 300000" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="">Pilih Status</option>
                                        <option value="pending" {{ old('status', $payment->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="completed" {{ old('status', $payment->status) == 'completed' ? 'selected' : '' }}>Terverifikasi</option>
                                        <option value="failed" {{ old('status', $payment->status) == 'failed' ? 'selected' : '' }}>Gagal</option>
                                        <option value="cancelled" {{ old('status', $payment->status) == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Catatan</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $payment->notes) }}</textarea>
                            <small class="form-text text-muted">Catatan tambahan tentang pembayaran ini</small>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current Billing Info -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">Informasi Tagihan Saat Ini</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Jumlah Tagihan:</strong> Rp {{ number_format($payment->billingRecord->amount ?? 0, 0, ',', '.') }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Jumlah Bayar:</strong> Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Sisa Tagihan:</strong> 
                                                @php
                                                    $remaining = ($payment->billingRecord->amount ?? 0) - $payment->total_amount;
                                                @endphp
                                                <span class="badge bg-{{ $remaining > 0 ? 'warning' : 'success' }} text-white">
                                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Pembayaran
                            </button>
                            <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format number with thousand separators
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Parse formatted number back to integer
function parseFormattedNumber(formattedNum) {
    return parseInt(formattedNum.replace(/\./g, '')) || 0;
}

// Auto-format payment amount input
document.getElementById('total_amount').addEventListener('input', function() {
    let value = this.value.replace(/[^\d]/g, ''); // Remove non-digits
    
    if (value) {
        // Format with thousand separators
        this.value = formatNumber(value);
    }
});

// Format total_amount before form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const totalAmountInput = document.getElementById('total_amount');
    const rawValue = totalAmountInput.value.replace(/\./g, '');
    totalAmountInput.value = rawValue;
});
</script>

@endsection
