@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Laporan Pembayaran</h3>
                    <div>
                        <a href="{{ route('reports.outstanding') }}" class="btn btn-secondary">
                            <i class="fas fa-chart-bar"></i> Tunggakan
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end mb-3">
                        @if(isset($institutions))
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Lembaga</label>
                            <select id="filter_institution" name="institution_id" class="form-control">
                                <option value="">Semua</option>
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst->id }}" {{ request('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Kelas</label>
                            <select id="filter_class" name="class_id" class="form-control" placeholder="Semua">
                                <option value="">Semua</option>
                                @foreach(($classes ?? []) as $cls)
                                    <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>{{ $cls->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Metode</label>
                            <select name="payment_method" class="form-control">
                                <option value="">Semua</option>
                                @foreach(($paymentMethods ?? []) as $key => $label)
                                    <option value="{{ $key }}" {{ request('payment_method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-control">
                                <option value="">Semua</option>
                                @foreach(($academicYears ?? []) as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year_start }}-{{ $ay->year_end }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(isset($cashiers) && count($cashiers) > 0)
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Kasir</label>
                            <select name="cashier_id" class="form-control">
                                <option value="">Semua Kasir</option>
                                @foreach($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" {{ request('cashier_id') == $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-xl-1 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Dari</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" />
                        </div>
                        <div class="col-xl-1 col-lg-4 col-md-6">
                            <label class="form-label mb-1">Sampai</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" />
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 mt-2">
                            <button class="btn btn-primary me-2" type="submit"><i class="fas fa-filter"></i> Filter</button>
                            <a href="{{ route('reports.payments') }}" class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Reset</a>
                        </div>
                    </form>

                    <!-- Summary Cards -->
                    @if(isset($summaryData) && count($summaryData) > 0)
                    <div class="row mb-4">
                        @php $grandTotalStudents = 0; $grandTotalPaid = 0; $grandTotalBilled = 0; @endphp
                        @foreach($summaryData as $institutionName => $institutionData)
                            @php 
                                $grandTotalStudents += $institutionData['total_students'];
                                $grandTotalPaid += $institutionData['total_paid'];
                                $grandTotalBilled += $institutionData['total_billed'];
                            @endphp
                        @endforeach
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ number_format($grandTotalStudents) }}</h4>
                                            <small>Total Siswa</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">Rp {{ number_format($grandTotalPaid, 0, ',', '.') }}</h4>
                                            <small>Total Dibayar</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-money-bill-wave fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">Rp {{ number_format($grandTotalBilled - $grandTotalPaid, 0, ',', '.') }}</h4>
                                            <small>Total Belum Dibayar</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $grandTotalBilled > 0 ? number_format(($grandTotalPaid / $grandTotalBilled) * 100, 1) : 0 }}%</h4>
                                            <small>Persentase Pembayaran</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-percentage fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Table per Lembaga -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Rekapan per Lembaga</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Lembaga</th>
                                            <th class="text-center">Jumlah Siswa</th>
                                            <th class="text-end">Total Tagihan</th>
                                            <th class="text-end">Sudah Dibayar</th>
                                            <th class="text-end">Belum Dibayar</th>
                                            <th class="text-center">% Pembayaran</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($summaryData as $institutionName => $institutionData)
                                            @php
                                                $unpaid = $institutionData['total_billed'] - $institutionData['total_paid'];
                                                $percentage = $institutionData['total_billed'] > 0 ? ($institutionData['total_paid'] / $institutionData['total_billed']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $institutionName }}</strong></td>
                                                <td class="text-center">{{ number_format($institutionData['total_students']) }}</td>
                                                <td class="text-end">Rp {{ number_format($institutionData['total_billed'], 0, ',', '.') }}</td>
                                                <td class="text-end text-success">Rp {{ number_format($institutionData['total_paid'], 0, ',', '.') }}</td>
                                                <td class="text-end text-warning">Rp {{ number_format($unpaid, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger') }}">
                                                        {{ number_format($percentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('reports.payments.export', ['institution_id' => $institutionData['institution_id']]) }}" class="btn btn-sm btn-info me-1">
                                                        <i class="fas fa-file-pdf"></i> PDF
                                                    </a>
                                                </td>
                                            </tr>
                                            
                                            <!-- Detail per Kelas -->
                                            @foreach($institutionData['classes'] as $className => $classData)
                                                @php
                                                    $classUnpaid = $classData['total_billed'] - $classData['total_paid'];
                                                    $classPercentage = $classData['total_billed'] > 0 ? ($classData['total_paid'] / $classData['total_billed']) * 100 : 0;
                                                @endphp
                                                <tr class="table-light">
                                                    <td class="ps-4"><i class="fas fa-arrow-right me-2"></i>{{ $className }}</td>
                                                    <td class="text-center">{{ number_format($classData['total_students']) }}</td>
                                                    <td class="text-end">Rp {{ number_format($classData['total_billed'], 0, ',', '.') }}</td>
                                                    <td class="text-end text-success">Rp {{ number_format($classData['total_paid'], 0, ',', '.') }}</td>
                                                    <td class="text-end text-warning">Rp {{ number_format($classUnpaid, 0, ',', '.') }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-{{ $classPercentage >= 80 ? 'success' : ($classPercentage >= 60 ? 'warning' : 'danger') }}">
                                                            {{ number_format($classPercentage, 1) }}%
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('reports.payments.export', ['institution_id' => $institutionData['institution_id'], 'class_id' => $classData['class_id']]) }}" class="btn btn-sm btn-info me-1">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th><strong>TOTAL</strong></th>
                                            <th class="text-center"><strong>{{ number_format($grandTotalStudents) }}</strong></th>
                                            <th class="text-end"><strong>Rp {{ number_format($grandTotalBilled, 0, ',', '.') }}</strong></th>
                                            <th class="text-end"><strong>Rp {{ number_format($grandTotalPaid, 0, ',', '.') }}</strong></th>
                                            <th class="text-end"><strong>Rp {{ number_format($grandTotalBilled - $grandTotalPaid, 0, ',', '.') }}</strong></th>
                                            <th class="text-center">
                                                <strong>{{ $grandTotalBilled > 0 ? number_format(($grandTotalPaid / $grandTotalBilled) * 100, 1) : 0 }}%</strong>
                                            </th>
                                            <th class="text-center">
                                                <a href="{{ route('reports.payments.export', ['institution_id' => 'all']) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="fas fa-file-pdf"></i> PDF
                                                </a>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Summary per Kasir (Admin Only) -->
                    @if(isset($cashierSummary) && count($cashierSummary) > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Rekapan per Kasir</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-info">
                                        <tr>
                                            <th>Nama Kasir</th>
                                            <th class="text-center">Jumlah Transaksi</th>
                                            <th class="text-end">Total Pembayaran</th>
                                            <th class="text-center">% dari Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalCashierAmount = 0; @endphp
                                        @foreach($cashierSummary as $kasirId => $cashierData)
                                            @php
                                                $kasir = $payments->first(function($p) use ($kasirId) { return $p->kasir_id == $kasirId; })->kasir ?? null;
                                                $kasirName = $kasir ? $kasir->name : 'Kasir #' . $kasirId;
                                                $percentage = $totalNominal > 0 ? ($cashierData['total_amount'] / $totalNominal) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $kasirName }}</strong></td>
                                                <td class="text-center">{{ number_format($cashierData['total_payments']) }}</td>
                                                <td class="text-end text-success">Rp {{ number_format($cashierData['total_amount'], 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ number_format($percentage, 1) }}%</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-info">
                                        <tr>
                                            <th><strong>TOTAL</strong></th>
                                            <th class="text-center"><strong>{{ number_format($payments->count()) }}</strong></th>
                                            <th class="text-end"><strong>Rp {{ number_format($totalNominal, 0, ',', '.') }}</strong></th>
                                            <th class="text-center"><strong>100%</strong></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Detail Transactions (Collapsible) -->
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h5 class="card-title mb-0 text-white">
                                <a href="#detailTransactions" data-bs-toggle="collapse" class="text-decoration-none text-white">
                                    <i class="fas fa-chevron-down me-2"></i>Detail Transaksi
                                </a>
                            </h5>
                        </div>
                        <div class="collapse" id="detailTransactions">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tanggal</th>
                                                <th>NIS</th>
                                                <th>Nama Siswa</th>
                                                <th>Lembaga</th>
                                                <th>Kelas</th>
                                                <th>Nominal</th>
                                                <th>Metode</th>
                                                <th>Kasir</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $pageTotal = 0; @endphp
                                            @forelse($payments as $i => $payment)
                                                @php $pageTotal += (float)($payment->total_amount ?? 0); @endphp
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                                                    <td>{{ $payment->billingRecord->student->nis ?? '-' }}</td>
                                                    <td>{{ $payment->billingRecord->student->name ?? '-' }}</td>
                                                    <td>{{ $payment->billingRecord->student->institution->name ?? '-' }}</td>
                                                    <td>{{ $payment->billingRecord->student->classRoom->class_name ?? '-' }}</td>
                                                    <td>Rp {{ number_format($payment->total_amount ?? 0, 0, ',', '.') }}</td>
                                                    <td><span class="badge bg-info text-dark">{{ strtoupper($payment->payment_method ?? 'cash') }}</span></td>
                                                                                            <td>
                                            @if($payment->kasir)
                                                <span class="badge bg-secondary">{{ $payment->kasir->name }}</span>
                                            @elseif($payment->kasir_id)
                                                <span class="badge bg-warning text-dark">Kasir #{{ $payment->kasir_id }}</span>
                                            @else
                                                <span class="badge bg-light text-dark">-</span>
                                            @endif
                                        </td>
                                                    <td>
                                                        <span class="badge bg-{{ $payment->status_class }} text-dark">{{ $payment->status_text }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center">Tidak ada data pembayaran.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <th colspan="7" class="text-end">Total Halaman:</th>
                                                <th colspan="3">Rp {{ number_format($pageTotal, 0, ',', '.') }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const institutionFilter = document.getElementById('filter_institution');
    const classFilter = document.getElementById('filter_class');
    
    if (institutionFilter && classFilter) {
        institutionFilter.addEventListener('change', function() {
            const institutionId = this.value;
            
            // Reset class filter
            classFilter.innerHTML = '<option value="">Semua</option>';
            
            if (institutionId) {
                // Fetch classes for selected institution
                fetch(`/api/institutions/${institutionId}/classes`)
                    .then(response => response.json())
                    .then(data => {
                        data.classes.forEach(cls => {
                            const option = document.createElement('option');
                            option.value = cls.id;
                            option.textContent = cls.class_name;
                            classFilter.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching classes:', error);
                    });
            }
        });
    }
});
</script>
@endsection
