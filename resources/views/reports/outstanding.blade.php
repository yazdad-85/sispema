@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Laporan Tunggakan</h3>
                    <div>
                        <a href="{{ route('reports.payments') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="row g-2 align-items-end mb-3">
                        @if(isset($institutions))
                        <div class="col-xl-2 col-lg-6 col-md-6">
                            <label class="form-label mb-1">Lembaga</label>
                            <select name="institution_id" class="form-control">
                                <option value="">Semua Lembaga</option>
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst->id }}" {{ request('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-xl-2 col-lg-6 col-md-6">
                            <label class="form-label mb-1">Kelas</label>
                            <select name="class_id" class="form-control">
                                <option value="">Semua Kelas</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-6 col-md-6">
                            <label class="form-label mb-1">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-control">
                                <option value="">Semua Tahun Ajaran</option>
                                @foreach(($academicYears ?? []) as $ay)
                                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year_start }}-{{ $ay->year_end }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(isset($cashiers) && count($cashiers) > 0)
                        <div class="col-xl-2 col-lg-6 col-md-6">
                            <label class="form-label mb-1">Kasir</label>
                            <select name="cashier_id" class="form-control">
                                <option value="">Semua Kasir</option>
                                @foreach($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" {{ request('cashier_id') == $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-xl-2 col-lg-6 col-md-6">
                            <button class="btn btn-primary me-2" type="submit"><i class="fas fa-filter"></i> Filter</button>
                            <a href="{{ route('reports.outstanding') }}" class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Reset</a>
                        </div>
                    </form>

                    @if(!empty($summaryData) && count($summaryData) > 0)
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            @php $grandTotalStudents = 0; $grandTotalOutstanding = 0; $grandTotalBilled = 0; @endphp
                            @foreach($summaryData as $institutionName => $institutionData)
                                @php 
                                    $grandTotalStudents += $institutionData['total_students'];
                                    $grandTotalOutstanding += $institutionData['total_outstanding'];
                                    $grandTotalBilled += $institutionData['total_billed'];
                                @endphp
                            @endforeach
                            
                            <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">{{ number_format($grandTotalStudents) }}</h4>
                                                <small>Total Siswa Bertunggakan</small>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-users fa-2x"></i>
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
                                                <h4 class="mb-0">Rp {{ number_format($grandTotalOutstanding, 0, ',', '.') }}</h4>
                                                <small>Total Tunggakan</small>
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
                                                <h4 class="mb-0">Rp {{ number_format($grandTotalBilled, 0, ',', '.') }}</h4>
                                                <small>Total Tagihan</small>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">{{ $grandTotalBilled > 0 ? number_format(($grandTotalOutstanding / $grandTotalBilled) * 100, 1) : 0 }}%</h4>
                                                <small>% Tunggakan</small>
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
                                <h5 class="card-title mb-0">Rekapan Tunggakan per Lembaga</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Lembaga</th>
                                                <th class="text-center">Jumlah Siswa</th>
                                                <th class="text-end">Total Tagihan</th>
                                                <th class="text-end">Total Tunggakan</th>
                                                <th class="text-end">Sudah Dibayar</th>
                                                <th class="text-center">% Tunggakan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($summaryData as $institutionName => $institutionData)
                                                @php
                                                    $paid = $institutionData['total_billed'] - $institutionData['total_outstanding'];
                                                    $percentage = $institutionData['total_billed'] > 0 ? ($institutionData['total_outstanding'] / $institutionData['total_billed']) * 100 : 0;
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $institutionName }}</strong></td>
                                                    <td class="text-center">{{ number_format($institutionData['total_students']) }}</td>
                                                    <td class="text-end">Rp {{ number_format($institutionData['total_billed'], 0, ',', '.') }}</td>
                                                    <td class="text-end text-warning">Rp {{ number_format($institutionData['total_outstanding'], 0, ',', '.') }}</td>
                                                    <td class="text-end text-success">Rp {{ number_format($paid, 0, ',', '.') }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-{{ $percentage <= 20 ? 'success' : ($percentage <= 40 ? 'warning' : 'danger') }}">
                                                            {{ number_format($percentage, 1) }}%
                                                        </span>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Detail per Kelas -->
                                                @foreach($institutionData['classes'] as $className => $classData)
                                                    @php
                                                        $classPaid = $classData['total_billed'] - $classData['total_outstanding'];
                                                        $classPercentage = $classData['total_billed'] > 0 ? ($classData['total_outstanding'] / $classData['total_billed']) * 100 : 0;
                                                    @endphp
                                                    <tr class="table-light">
                                                        <td class="ps-4"><i class="fas fa-arrow-right me-2"></i>{{ $className }}</td>
                                                        <td class="text-center">{{ number_format($classData['total_students']) }}</td>
                                                        <td class="text-end">Rp {{ number_format($classData['total_billed'], 0, ',', '.') }}</td>
                                                        <td class="text-end text-warning">Rp {{ number_format($classData['total_outstanding'], 0, ',', '.') }}</td>
                                                        <td class="text-end text-success">Rp {{ number_format($classPaid, 0, ',', '.') }}</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-{{ $classPercentage <= 20 ? 'success' : ($classPercentage <= 40 ? 'warning' : 'danger') }}">
                                                                {{ number_format($classPercentage, 1) }}%
                                                            </span>
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
                                                <th class="text-end"><strong>Rp {{ number_format($grandTotalOutstanding, 0, ',', '.') }}</strong></th>
                                                <th class="text-end"><strong>Rp {{ number_format($grandTotalBilled - $grandTotalOutstanding, 0, ',', '.') }}</strong></th>
                                                <th class="text-center">
                                                    <strong>{{ $grandTotalBilled > 0 ? number_format(($grandTotalOutstanding / $grandTotalBilled) * 100, 1) : 0 }}%</strong>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success mb-0">Tidak ada tunggakan.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const institutionFilter = document.querySelector('select[name="institution_id"]');
    const classFilter = document.querySelector('select[name="class_id"]');
    
    if (institutionFilter && classFilter) {
        institutionFilter.addEventListener('change', function() {
            const institutionId = this.value;
            
            // Reset class filter
            classFilter.innerHTML = '<option value="">Semua Kelas</option>';
            
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
