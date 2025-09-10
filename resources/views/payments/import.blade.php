@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import Data Pembayaran</h3>
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

                    @if(session('import_errors'))
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            <h6>Beberapa data tidak berhasil diimport:</h6>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Upload File Excel</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('payments.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group">
                                            <label for="excel_file">Pilih File Excel (.xlsx)</label>
                                            <input type="file" class="form-control @error('excel_file') is-invalid @enderror" 
                                                   id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                            @error('excel_file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                File harus berformat Excel (.xlsx atau .xls) dan tidak lebih dari 5MB
                                            </small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload"></i> Import Data
                                            </button>
                                            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Kembali
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Download Template</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Download template Excel untuk memastikan format data yang benar.</p>
                                    <a href="{{ route('payments.download-template') }}" class="btn btn-success btn-block">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Petunjuk Import</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Kolom Wajib:</h6>
                                            <ul>
                                                <li><strong>nis</strong> - NIS siswa (harus ada di database)</li>
                                                <li><strong>payment_date</strong> - Tanggal pembayaran (format: YYYY-MM-DD)</li>
                                                <li><strong>total_amount</strong> - Jumlah pembayaran (angka)</li>
                                                <li><strong>payment_method</strong> - Metode pembayaran (cash/transfer/qris/edc)</li>
                                                <li><strong>status</strong> - Status pembayaran (pending/completed/failed/cancelled)</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Kolom Opsional:</h6>
                                            <ul>
                                                <li><strong>notes</strong> - Catatan pembayaran</li>
                                                <li><strong>billing_month</strong> - Bulan tagihan (jika kosong akan otomatis)</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <h6><i class="fas fa-info-circle"></i> Catatan Penting:</h6>
                                        <ul class="mb-0">
                                            <li>Sistem akan otomatis mencari tagihan yang sesuai berdasarkan NIS dan bulan</li>
                                            <li>Jika tidak ada tagihan yang cocok, pembayaran akan ditolak</li>
                                            <li>Pastikan NIS siswa sudah terdaftar di database</li>
                                            <li>Format tanggal harus YYYY-MM-DD (contoh: 2024-01-15)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Data Referensi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6>Total Siswa: {{ \App\Models\Student::count() }}</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Total Tagihan Aktif: {{ \App\Models\BillingRecord::where('status', 'active')->count() }}</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Total Pembayaran: {{ \App\Models\Payment::count() }}</h6>
                                        </div>
                                    </div>
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
