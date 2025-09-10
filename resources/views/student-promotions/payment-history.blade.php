<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran - {{ $student->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .summary-card {
            border-left: 4px solid #28a745;
        }
        .billing-card {
            border-left: 4px solid #007bff;
        }
        .payment-card {
            border-left: 4px solid #ffc107;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .status-badge {
            font-size: 0.8em;
        }
        .amount {
            font-weight: 600;
        }
        .outstanding {
            color: #dc3545;
        }
        .paid {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="card header-card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1">
                            <i class="fas fa-history me-2"></i>
                            Riwayat Pembayaran
                        </h2>
                        <h4 class="mb-0">{{ $student->name }}</h4>
                        <p class="mb-0 opacity-75">
                            NIS: {{ $student->nis }} | 
                            Kelas: {{ $student->classRoom->class_name ?? 'Tidak ada kelas' }} | 
                            Lembaga: {{ $student->classRoom->institution->name ?? 'Tidak ada lembaga' }}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>
                            Cetak
                        </button>
                        <button class="btn btn-outline-light" onclick="window.close()">
                            <i class="fas fa-times me-1"></i>
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card summary-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-file-invoice fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Total Tagihan</h5>
                        <h3 class="text-success">{{ $paymentHistory->flatten()->count() }}</h3>
                        <small class="text-muted">Record Tagihan</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Total Pembayaran</h5>
                        <h3 class="text-primary">{{ $student->payments->count() }}</h3>
                        <small class="text-muted">Transaksi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h5 class="card-title">Sisa Tagihan</h5>
                        <h3 class="text-danger">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</h3>
                        <small class="text-muted">Belum Lunas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History by Academic Year -->
        @if($paymentHistory->isNotEmpty())
            @foreach($paymentHistory as $academicYear => $billingRecords)
                <div class="card billing-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Tahun Ajaran {{ $academicYear }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Jenis Biaya</th>
                                        <th>Kelas Asal</th>
                                        <th>Jumlah</th>
                                        <th>Terbayar</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                        <th>Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($billingRecords as $billing)
                                        <tr>
                                            <td>
                                                <strong>{{ $billing->billing_month }}</strong>
                                            </td>
                                            <td>
                                                {{ $billing->feeStructure->fee_name ?? 'Tidak ada data' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $billing->origin_class ?? 'Tidak ada data' }}
                                                </span>
                                            </td>
                                            <td class="amount">
                                                Rp {{ number_format($billing->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="paid">
                                                Rp {{ number_format($billing->getTotalPaidAttribute(), 0, ',', '.') }}
                                            </td>
                                            <td class="outstanding">
                                                Rp {{ number_format($billing->remaining_balance, 0, ',', '.') }}
                                            </td>
                                            <td>
                                                @php
                                                    $statusClass = match($billing->status) {
                                                        'fully_paid' => 'success',
                                                        'partially_paid' => 'warning',
                                                        'overdue' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    $statusText = match($billing->status) {
                                                        'fully_paid' => 'Lunas',
                                                        'partially_paid' => 'Sebagian',
                                                        'overdue' => 'Terlambat',
                                                        default => ucfirst($billing->status)
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }} status-badge">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $billing->due_date ? $billing->due_date->format('d/m/Y') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum Ada Riwayat Pembayaran</h5>
                    <p class="text-muted">Siswa ini belum memiliki tagihan atau pembayaran.</p>
                </div>
            </div>
        @endif

        <!-- Payment Details -->
        @if($student->payments->isNotEmpty())
            <div class="card payment-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Detail Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Kwitansi</th>
                                    <th>Jumlah</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Kasir</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->payments->sortByDesc('payment_date') as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <code>{{ $payment->receipt_number ?? '-' }}</code>
                                        </td>
                                        <td class="amount">
                                            Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ ucfirst($payment->payment_method) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $payment->status_class }}">
                                                {{ $payment->status_text }}
                                            </span>
                                        </td>
                                        <td>{{ $payment->kasir->name ?? '-' }}</td>
                                        <td>{{ $payment->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Footer Info -->
        <div class="card mt-4">
            <div class="card-body text-center text-muted">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Riwayat pembayaran ini akan tetap tersimpan meskipun siswa dipromosi ke kelas lain.
                    <br>
                    Data terakhir diperbarui: {{ now()->format('d/m/Y H:i') }}
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
