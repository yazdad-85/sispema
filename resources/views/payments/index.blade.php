@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Pembayaran</h3>
                    <div>
                        <a href="{{ route('payments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Pembayaran
                        </a>
                        <a href="{{ route('payments.import') }}" class="btn btn-success me-2">
                            <i class="fas fa-file-excel"></i> Import Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="institution_filter">Lembaga</label>
                            <select class="form-control" id="institution_filter">
                                <option value="">Semua Lembaga</option>
                                @foreach($institutions as $institution)
                                    <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_method_filter">Metode Pembayaran</label>
                            <select class="form-control" id="payment_method_filter">
                                <option value="">Semua Metode</option>
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from">Dari Tanggal</label>
                            <input type="date" class="form-control" id="date_from">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="date_to">
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($totalPayments) }}</h4>
                                    <p class="mb-0">Total Pembayaran</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>Rp {{ number_format($totalAmount, 0, ',', '.') }}</h4>
                                    <p class="mb-0">Total Nominal</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($pendingPayments) }}</h4>
                                    <p class="mb-0" title="Pembayaran dengan status 'Pending' (menunggu konfirmasi)">Pending</p>
                                    <small class="text-light">Menunggu konfirmasi</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($completedBills) }}</h4>
                                    <p class="mb-0">Tagihan Lunas</p>
                                    <small class="text-light">Sisa tagihan = 0</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($transferQrisPayments) }}</h4>
                                    <p class="mb-0">Transfer & QRIS</p>
                                    <small class="text-light">Perlu konfirmasi</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-dark text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($payments->where('payment_method', 'cash')->count()) }}</h4>
                                    <p class="mb-0">Cash</p>
                                    <small class="text-light">Langsung lunas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($payments->where('status', 'verified')->count()) }}</h4>
                                    <p class="mb-0">Terverifikasi</p>
                                    <small class="text-light">Pembayaran berhasil</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="payments-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Lembaga</th>
                                    <th>Kelas</th>
                                    <th>Total Kewajiban</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Sisa Tagihan</th>
                                    <th>Status</th>
                                    <th>Metode</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $index => $payment)
                                    <tr>
                                        <td>{{ $payments->firstItem() + $index }}</td>
                                        <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $payment->billingRecord->student->nis ?? '-' }}</td>
                                        <td>{{ $payment->billingRecord->student->name ?? '-' }}</td>
                                        <td>{{ $payment->billingRecord->student->institution->name ?? '-' }}</td>
                                        <td>{{ $payment->billingRecord->student->classRoom->class_name ?? '-' }}</td>
                                        <td>
                                            @php
                                                $billingAmount = $payment->billingRecord->amount ?? 0;
                                                $previousDebt = $payment->billingRecord->student->previous_debt ?? 0;
                                                $totalObligation = $billingAmount + $previousDebt;
                                            @endphp
                                            <div>
                                                <strong>Rp {{ number_format($totalObligation, 0, ',', '.') }}</strong>
                                                @if($previousDebt > 0)
                                                    <br>
                                                    <small class="text-muted">
                                                        Struktur: {{ number_format($billingAmount, 0, ',', '.') }} + 
                                                        Tunggakan: {{ number_format($previousDebt, 0, ',', '.') }}
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @php
                                                $remaining = $totalObligation - $payment->total_amount;
                                            @endphp
                                            <span class="badge bg-{{ $remaining > 0 ? 'warning' : 'success' }} text-white">
                                                Rp {{ number_format($remaining, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $payment->status_class }} text-white">
                                                {{ $payment->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                {{ ucfirst($payment->payment_method ?? 'Cash') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($payment->status === 'pending' && in_array($payment->payment_method, ['transfer', 'qris']))
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#verificationModal{{ $payment->id }}"
                                                            title="Verifikasi Pembayaran">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('payments.show', $payment->id) }}" 
                                                   class="btn btn-sm btn-info" title="Lihat">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('payments.edit', $payment->id) }}" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('payments.receipt', $payment->id) }}" 
                                                   class="btn btn-sm btn-success" title="Cetak Kwitansi">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <form action="{{ route('payments.destroy', $payment->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            title="Hapus" 
                                                            onclick="return confirm('Yakin ingin menghapus pembayaran ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">Tidak ada data pembayaran</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verification Modals for each pending payment -->
@foreach($payments as $payment)
    @if($payment->status === 'pending' && in_array($payment->payment_method, ['transfer', 'qris']))
    <div class="modal fade" id="verificationModal{{ $payment->id }}" tabindex="-1" aria-labelledby="verificationModalLabel{{ $payment->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verificationModalLabel{{ $payment->id }}">Verifikasi Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('payments.verify', $payment->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Informasi Pembayaran:</strong><br>
                            <small class="text-muted">
                                {{ $payment->billingRecord->student->name ?? 'N/A' }} - 
                                Rp {{ number_format($payment->total_amount, 0, ',', '.') }} - 
                                {{ ucfirst($payment->payment_method) }}
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="verification_status{{ $payment->id }}" class="form-label">Status Verifikasi <span class="text-danger">*</span></label>
                            <select class="form-control" id="verification_status{{ $payment->id }}" name="verification_status" required>
                                <option value="">Pilih Status</option>
                                <option value="verified">Terverifikasi (Pembayaran Masuk)</option>
                                <option value="failed">Gagal (Pembayaran Tidak Masuk)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="verification_notes{{ $payment->id }}" class="form-label">Catatan Verifikasi</label>
                            <textarea class="form-control" id="verification_notes{{ $payment->id }}" name="verification_notes" rows="3" 
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
@endforeach

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const institutionFilter = document.getElementById('institution_filter');
    const paymentMethodFilter = document.getElementById('payment_method_filter');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    function applyFilters() {
        const table = document.getElementById('payments-table');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let row of rows) {
            let showRow = true;
            
            // Institution filter
            if (institutionFilter.value) {
                const institutionCell = row.querySelector('td:nth-child(5)');
                if (institutionCell) {
                    const selectedInstitution = institutionFilter.options[institutionFilter.selectedIndex].text;
                    if (!institutionCell.textContent.includes(selectedInstitution)) {
                        showRow = false;
                    }
                }
            }
            
            // Payment Method filter
            if (paymentMethodFilter.value) {
                const paymentMethodCell = row.querySelector('td:nth-child(11)'); // Assuming payment method is in the 11th column (index 10)
                if (paymentMethodCell) {
                    const selectedPaymentMethod = paymentMethodFilter.options[paymentMethodFilter.selectedIndex].text;
                    if (!paymentMethodCell.textContent.includes(selectedPaymentMethod)) {
                        showRow = false;
                    }
                }
            }
            
            // Date filter
            if (dateFrom.value || dateTo.value) {
                const dateCell = row.querySelector('td:nth-child(2)');
                if (dateCell) {
                    const rowDate = new Date(dateCell.textContent.split('/').reverse().join('-'));
                    if (dateFrom.value && rowDate < new Date(dateFrom.value)) {
                        showRow = false;
                    }
                    if (dateTo.value && rowDate > new Date(dateTo.value)) {
                        showRow = false;
                    }
                }
            }
            
            row.style.display = showRow ? '' : 'none';
        }
    }
    
    institutionFilter.addEventListener('change', applyFilters);
    paymentMethodFilter.addEventListener('change', applyFilters);
    dateFrom.addEventListener('change', applyFilters);
    dateTo.addEventListener('change', applyFilters);
});
</script>
@endsection
