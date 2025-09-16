@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-book me-2"></i>Buku Kas Umum
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('financial-reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('financial-reports.cash-book.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Tambah Transaksi
                            </a>
                            <a href="{{ route('financial-reports.cash-book', ['export_pdf' => 1] + request()->query()) }}" class="btn btn-success">
                                <i class="fas fa-file-pdf me-1"></i>Export PDF
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="academic_year_id" class="form-label">Tahun Ajaran</label>
                                <select name="academic_year_id" id="academic_year_id" class="form-select">
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="month" class="form-label">Bulan</label>
                                <select name="month" id="month" class="form-select">
                                    <option value="">-- Pilih Bulan --</option>
                                    <option value="1" {{ request('month') == '1' ? 'selected' : '' }}>Januari</option>
                                    <option value="2" {{ request('month') == '2' ? 'selected' : '' }}>Februari</option>
                                    <option value="3" {{ request('month') == '3' ? 'selected' : '' }}>Maret</option>
                                    <option value="4" {{ request('month') == '4' ? 'selected' : '' }}>April</option>
                                    <option value="5" {{ request('month') == '5' ? 'selected' : '' }}>Mei</option>
                                    <option value="6" {{ request('month') == '6' ? 'selected' : '' }}>Juni</option>
                                    <option value="7" {{ request('month') == '7' ? 'selected' : '' }}>Juli</option>
                                    <option value="8" {{ request('month') == '8' ? 'selected' : '' }}>Agustus</option>
                                    <option value="9" {{ request('month') == '9' ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ request('month') == '10' ? 'selected' : '' }}>Oktober</option>
                                    <option value="11" {{ request('month') == '11' ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ request('month') == '12' ? 'selected' : '' }}>Desember</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="{{ route('financial-reports.cash-book') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Pemasukan</h6>
                                            <h4 class="mb-0">Rp {{ number_format($entries->sum('credit'), 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-up fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Pengeluaran</h6>
                                            <h4 class="mb-0">Rp {{ number_format($entries->sum('debit'), 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-down fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Saldo Akhir</h6>
                                            <h4 class="mb-0">Rp {{ number_format($currentBalance, 0, ',', '.') }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-wallet fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Debit</th>
                                    <th>Kredit</th>
                                    <th>Saldo</th>
                                    <th>Referensi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $entry->date->format('d/m/Y') }}</td>
                                        <td>{{ $entry->description }}</td>
                                        <td class="text-end">
                                            @if($entry->debit > 0)
                                                <span class="text-danger fw-bold">Rp {{ number_format($entry->debit, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($entry->credit > 0)
                                                <span class="text-success fw-bold">Rp {{ number_format($entry->credit, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">
                                            Rp {{ number_format($entry->balance, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @if($entry->reference_type && $entry->reference_id)
                                                <span class="badge bg-info">
                                                    {{ ucfirst($entry->reference_type) }}
                                                </span>
                                            @else
                                                <span class="text-muted">Manual</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($entry->reference_type === 'manual' || !$entry->reference_type)
                                                <form action="{{ route('financial-reports.cash-book.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">Auto</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>Tidak ada transaksi ditemukan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
