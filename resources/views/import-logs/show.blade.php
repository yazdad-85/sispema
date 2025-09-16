@extends('layouts.app')

@section('title', 'Detail Import Log')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-import"></i> Detail Log Import
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('import-logs.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="{{ route('import-logs.download', $logData['started_at']) }}" 
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-download"></i> Download Log
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-file"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Data</span>
                                    <span class="info-box-number">{{ $logData['summary']['total_rows'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-check"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Berhasil</span>
                                    <span class="info-box-number">{{ $logData['summary']['success_count'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-times"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Error</span>
                                    <span class="info-box-number">{{ $logData['summary']['error_count'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Warning</span>
                                    <span class="info-box-number">{{ $logData['summary']['warning_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-info-circle text-info"></i> Informasi Import
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Tipe Import:</strong></td>
                                            <td>{{ ucfirst($logData['import_type']) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>User ID:</strong></td>
                                            <td>{{ $logData['user_id'] }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>File:</strong></td>
                                            <td>{{ $logData['file_name'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dimulai:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($logData['started_at'])->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Selesai:</strong></td>
                                            <td>{{ isset($logData['finished_at']) ? \Carbon\Carbon::parse($logData['finished_at'])->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Durasi:</strong></td>
                                            <td>{{ $logData['duration'] ?? 0 }} detik</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-pie text-primary"></i> Statistik
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="progress-group">
                                        <div class="progress-text">
                                            <span class="float-left">Success Rate</span>
                                            <span class="float-right">
                                                {{ $logData['summary']['total_rows'] > 0 ? round(($logData['summary']['success_count'] / $logData['summary']['total_rows']) * 100, 2) : 0 }}%
                                            </span>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-success" 
                                                 style="width: {{ $logData['summary']['total_rows'] > 0 ? ($logData['summary']['success_count'] / $logData['summary']['total_rows']) * 100 : 0 }}%">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-group">
                                        <div class="progress-text">
                                            <span class="float-left">Error Rate</span>
                                            <span class="float-right">
                                                {{ $logData['summary']['total_rows'] > 0 ? round(($logData['summary']['error_count'] / $logData['summary']['total_rows']) * 100, 2) : 0 }}%
                                            </span>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-danger" 
                                                 style="width: {{ $logData['summary']['total_rows'] > 0 ? ($logData['summary']['error_count'] / $logData['summary']['total_rows']) * 100 : 0 }}%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Errors -->
                    @if(count($logData['errors']) > 0)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-exclamation-circle text-danger"></i> 
                                    Error Details ({{ count($logData['errors']) }})
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Baris</th>
                                                <th>Pesan Error</th>
                                                <th>Data</th>
                                                <th>Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($logData['errors'] as $error)
                                                <tr>
                                                    <td><span class="badge badge-danger">{{ $error['row'] }}</span></td>
                                                    <td>{{ $error['message'] }}</td>
                                                    <td>
                                                        @if(!empty($error['data']))
                                                            <button class="btn btn-sm btn-outline-info" 
                                                                    onclick="showData({{ json_encode($error['data']) }})">
                                                                <i class="fas fa-eye"></i> Lihat Data
                                                            </button>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($error['timestamp'])->format('H:i:s') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Warnings -->
                    @if(count($logData['warnings']) > 0)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-exclamation-triangle text-warning"></i> 
                                    Warning Details ({{ count($logData['warnings']) }})
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Baris</th>
                                                <th>Pesan Warning</th>
                                                <th>Data</th>
                                                <th>Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($logData['warnings'] as $warning)
                                                <tr>
                                                    <td><span class="badge badge-warning">{{ $warning['row'] }}</span></td>
                                                    <td>{{ $warning['message'] }}</td>
                                                    <td>
                                                        @if(!empty($warning['data']))
                                                            <button class="btn btn-sm btn-outline-info" 
                                                                    onclick="showData({{ json_encode($warning['data']) }})">
                                                                <i class="fas fa-eye"></i> Lihat Data
                                                            </button>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($warning['timestamp'])->format('H:i:s') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for showing data -->
<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Detail</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="dataContent"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function showData(data) {
    document.getElementById('dataContent').textContent = JSON.stringify(data, null, 2);
    $('#dataModal').modal('show');
}
</script>
@endsection
