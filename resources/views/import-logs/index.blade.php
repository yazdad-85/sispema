@extends('layouts.app')

@section('title', 'Import Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-import"></i> Log Import Excel
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('import-logs.clear') }}" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Yakin hapus semua log?')">
                            <i class="fas fa-trash"></i> Hapus Semua Log
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <select name="type" class="form-control">
                                    <option value="">Semua Tipe Import</option>
                                    <option value="students" {{ $importType == 'students' ? 'selected' : '' }}>Siswa</option>
                                    <option value="classes" {{ $importType == 'classes' ? 'selected' : '' }}>Kelas</option>
                                    <option value="payments" {{ $importType == 'payments' ? 'selected' : '' }}>Pembayaran</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="user_id" class="form-control" 
                                       placeholder="User ID" value="{{ $userId }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="{{ route('import-logs.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    @if(count($logs) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Tipe Import</th>
                                        <th>User</th>
                                        <th>File</th>
                                        <th>Dimulai</th>
                                        <th>Durasi</th>
                                        <th>Total Data</th>
                                        <th>Berhasil</th>
                                        <th>Error</th>
                                        <th>Warning</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr class="{{ $log['summary']['error_count'] > 0 ? 'table-danger' : ($log['summary']['warning_count'] > 0 ? 'table-warning' : 'table-success') }}">
                                            <td>
                                                <span class="badge badge-{{ $log['import_type'] == 'students' ? 'primary' : ($log['import_type'] == 'classes' ? 'info' : 'success') }}">
                                                    {{ ucfirst($log['import_type']) }}
                                                </span>
                                            </td>
                                            <td>User #{{ $log['user_id'] }}</td>
                                            <td>{{ $log['file_name'] ?? 'N/A' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($log['started_at'])->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ $log['duration'] ?? 0 }} detik</td>
                                            <td>{{ $log['summary']['total_rows'] }}</td>
                                            <td>
                                                <span class="badge badge-success">{{ $log['summary']['success_count'] }}</span>
                                            </td>
                                            <td>
                                                @if($log['summary']['error_count'] > 0)
                                                    <span class="badge badge-danger">{{ $log['summary']['error_count'] }}</span>
                                                @else
                                                    <span class="badge badge-light">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log['summary']['warning_count'] > 0)
                                                    <span class="badge badge-warning">{{ $log['summary']['warning_count'] }}</span>
                                                @else
                                                    <span class="badge badge-light">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('import-logs.show', $log['started_at']) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                                <a href="{{ route('import-logs.download', $log['started_at']) }}" 
                                                   class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Belum ada log import.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
