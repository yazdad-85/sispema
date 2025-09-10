@extends('layouts.app')

@section('content')
<style>
    /* Tablet-specific fixes */
    @media (min-width: 768px) and (max-width: 1199px) {
        .card-body h2 {
            font-size: 1.5rem !important;
            line-height: 1.3 !important;
        }
        
        .btn-block {
            padding: 0.75rem 0.5rem !important;
            font-size: 0.85rem !important;
            line-height: 1.2 !important;
            min-height: 80px;
        }
        
        .btn-block i {
            font-size: 1.5rem !important;
            margin-bottom: 0.5rem !important;
        }
        
        .text-xs {
            font-size: 0.7rem !important;
        }
        
        .h5 {
            font-size: 1.1rem !important;
        }
        
        .card-body {
            padding: 1rem !important;
        }
        
        .container-fluid {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
    }
    
    /* Ensure text doesn't get cut off */
    .btn-block {
        white-space: normal !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    .card-body h2 {
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }
</style>

<div class="container-fluid px-0">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">Selamat Datang di @appName</h2>
                            <p class="mb-0 opacity-75">
                                @auth
                                    {{ Auth::user()->name }} - {{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}
                                @else
                                    Silakan login untuk melanjutkan
                                @endauth
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <i class="fas fa-graduation-cap fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Siswa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalStudents }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Pembayaran Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($totalPaymentsToday, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tunggakan Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $activeArrears }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Lembaga</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalInstitutions }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @auth
                        @if(Auth::user()->isSuperAdmin())
                        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('students.create') }}" class="btn btn-primary btn-block action-btn">
                                <i class="fas fa-user-plus fa-2x mb-2"></i><br>
                                <span class="btn-text">Tambah Siswa</span>
                            </a>
                        </div>
                        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('payments.create') }}" class="btn btn-success btn-block action-btn">
                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                <span class="btn-text">Input Pembayaran</span>
                            </a>
                        </div>
                        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('reports.outstanding') }}" class="btn btn-warning btn-block action-btn">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                <span class="btn-text">Laporan Tunggakan</span>
                            </a>
                        </div>
                        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('settings.index') }}" class="btn btn-info btn-block action-btn">
                                <i class="fas fa-cog fa-2x mb-2"></i><br>
                                <span class="btn-text">Pengaturan</span>
                            </a>
                        </div>
                        @elseif(Auth::user()->isStaff())
                        <!-- Quick Actions untuk Staff -->
                        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('payments.create') }}" class="btn btn-success btn-block action-btn">
                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                <span class="btn-text">Input Pembayaran</span>
                            </a>
                        </div>
                        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('students.index') }}" class="btn btn-primary btn-block action-btn">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                <span class="btn-text">Data Siswa</span>
                            </a>
                        </div>
                        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('reports.payments') }}" class="btn btn-info btn-block action-btn">
                                <i class="fas fa-chart-line fa-2x mb-2"></i><br>
                                <span class="btn-text">Laporan Pembayaran</span>
                            </a>
                        </div>
                        @else
                        <!-- Quick Actions untuk role lain (admin_pusat, kasir) -->
                        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('payments.create') }}" class="btn btn-success btn-block action-btn">
                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                <span class="btn-text">Input Pembayaran</span>
                            </a>
                        </div>
                        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('students.index') }}" class="btn btn-primary btn-block action-btn">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                <span class="btn-text">Data Siswa</span>
                            </a>
                        </div>
                        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12 mb-3">
                            <a href="{{ route('reports.payments') }}" class="btn btn-info btn-block action-btn">
                                <i class="fas fa-chart-line fa-2x mb-2"></i><br>
                                <span class="btn-text">Laporan Pembayaran</span>
                            </a>
                        </div>
                        @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row g-3">
        <div class="col-xxl-8 col-xl-8 col-lg-12 col-md-12 col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Aktivitas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                    <td>Pembayaran {{ $payment->billingRecord->student->name ?? 'N/A' }}</td>
                                    <td><span class="badge badge-success">Selesai</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada aktivitas pembayaran</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-4 col-lg-12 col-md-12 col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Info Sistem</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Versi Aplikasi:</strong><br>
                        <span class="text-muted">@appName v1.0.0</span>
                    </div>
                    <div class="mb-3">
                        <strong>Framework:</strong><br>
                        <span class="text-muted">Laravel {{ app()->version() }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>PHP Version:</strong><br>
                        <span class="text-muted">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Database:</strong><br>
                        <span class="text-muted">MySQL</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
