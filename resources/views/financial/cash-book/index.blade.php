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
                        <a href="{{ route('cash-book.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tambah Entri
                        </a>
                        <a href="{{ route('financial-reports.cash-book', ['export_pdf' => 1]) }}" class="btn btn-success">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="{{ route('cash-book.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Current Balance -->
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-wallet me-2"></i>
                            <strong>Saldo Kas Saat Ini:</strong>
                        </div>
                        <h4 class="mb-0 text-success">Rp {{ number_format($currentBalance, 0, ',', '.') }}</h4>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

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
                                @forelse($entries as $index => $entry)
                                <tr>
                                    <td>{{ $entries->firstItem() + $index }}</td>
                                    <td>{{ $entry->date->format('d/m/Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-success">
                                        @if($entry->debit > 0)
                                            Rp {{ number_format($entry->debit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-danger">
                                        @if($entry->credit > 0)
                                            Rp {{ number_format($entry->credit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="fw-bold">
                                        Rp {{ number_format($entry->balance, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if($entry->reference_type)
                                            <span class="badge bg-info">{{ ucfirst($entry->reference_type) }}</span>
                                        @else
                                            <span class="badge bg-secondary">Manual</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($entry->reference_type === 'manual')
                                            <form action="{{ route('cash-book.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus entri ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
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
                                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada entri buku kas</p>
                                        <a href="{{ route('cash-book.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Tambah Entri Pertama
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($entries->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $entries->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
