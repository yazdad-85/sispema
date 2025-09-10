@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Laporan Keuangan
                    </h3>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Rencana Kegiatan -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                    <h5 class="card-title">Rencana Kegiatan</h5>
                                    <p class="card-text">Laporan rencana kegiatan dan budget</p>
                                    <a href="{{ route('financial-reports.activity-plans') }}" class="btn btn-light">
                                        <i class="fas fa-eye me-1"></i>Lihat Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Realisasi Kegiatan -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                    <h5 class="card-title">Realisasi Kegiatan</h5>
                                    <p class="card-text">Progress realisasi vs rencana</p>
                                    <a href="{{ route('financial-reports.realizations') }}" class="btn btn-light">
                                        <i class="fas fa-eye me-1"></i>Lihat Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buku Kas Umum -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-book fa-3x mb-3"></i>
                                    <h5 class="card-title">Buku Kas Umum</h5>
                                    <p class="card-text">Transaksi harian dan saldo</p>
                                    <a href="{{ route('financial-reports.cash-book') }}" class="btn btn-light">
                                        <i class="fas fa-eye me-1"></i>Lihat Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Neraca Keuangan -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card bg-warning text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-balance-scale fa-3x mb-3"></i>
                                    <h5 class="card-title">Neraca Keuangan</h5>
                                    <p class="card-text">Posisi keuangan dan analisis</p>
                                    <a href="{{ route('financial-reports.balance-sheet') }}" class="btn btn-light">
                                        <i class="fas fa-eye me-1"></i>Lihat Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">Statistik Cepat</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Total Kategori</h6>
                                            <h3 class="text-primary">{{ \App\Models\Category::count() }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Rencana Kegiatan</h6>
                                            <h3 class="text-success">{{ \App\Models\ActivityPlan::count() }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Realisasi</h6>
                                            <h3 class="text-info">{{ \App\Models\ActivityRealization::count() }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Saldo Kas</h6>
                                            <h3 class="text-warning">Rp {{ number_format(\App\Models\CashBook::getCurrentBalance(), 0, ',', '.') }}</h3>
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
