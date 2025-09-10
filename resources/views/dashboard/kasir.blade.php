@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Dashboard Kasir - {{ Auth::user()->institution->name }}</h4>
                </div>
                <div class="card-body">
                    <!-- Statistik Utama -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Total Siswa</h5>
                                    <h3>{{ number_format($totalStudents) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Pembayaran Hari Ini</h5>
                                    <h3>Rp {{ number_format($todayPayments) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Total Tunggakan</h5>
                                    <h3>Rp {{ number_format($totalOutstanding) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <a href="{{ route('payments.create') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus"></i> Input Pembayaran Baru
                            </a>
                            <a href="{{ route('payments.index') }}" class="btn btn-info btn-lg">
                                <i class="fas fa-list"></i> Daftar Pembayaran
                            </a>
                            <a href="{{ route('students.index') }}" class="btn btn-success btn-lg">
                                <i class="fas fa-users"></i> Data Siswa
                            </a>
                        </div>
                    </div>

                    <!-- Pembayaran Terbaru -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Pembayaran Terbaru</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No Kwitansi</th>
                                            <th>Nama Siswa</th>
                                            <th>Tanggal</th>
                                            <th>Jumlah</th>
                                            <th>Metode</th>
                                            <th>Kasir</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentPayments as $payment)
                                        <tr>
                                            <td>{{ $payment->receipt_number }}</td>
                                            <td>{{ $payment->student->name }}</td>
                                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                            <td>Rp {{ number_format($payment->total_amount) }}</td>
                                            <td>
                                                @switch($payment->payment_method)
                                                    @case('cash')
                                                        <span class="badge bg-success">Tunai</span>
                                                        @break
                                                    @case('transfer')
                                                        <span class="badge bg-info">Transfer</span>
                                                        @break
                                                    @case('digital_payment')
                                                        <span class="badge bg-warning">Digital</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>{{ $payment->kasir->name }}</td>
                                            <td>
                                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-sm btn-success">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Belum ada pembayaran</td>
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
    </div>
</div>
@endsection
