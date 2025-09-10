@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Tahun Ajaran</h1>
        <div>
            <a href="{{ route('academic-years.edit', $academicYear->id) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> Edit
            </a>
            <a href="{{ route('academic-years.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Tahun Ajaran</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Tahun Ajaran:</strong>
                        </div>
                        <div class="col-sm-9">
                            <h5 class="text-primary">{{ $academicYear->fullName }}</h5>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Status:</strong>
                        </div>
                        <div class="col-sm-9">
                            @if($academicYear->status === 'active')
                                <span class="badge bg-success text-white">Aktif</span>
                            @else
                                <span class="badge bg-danger text-white">Tidak Aktif</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Tahun Aktif:</strong>
                        </div>
                        <div class="col-sm-9">
                            @if($academicYear->is_current)
                                <span class="badge bg-primary text-white">Tahun Ajaran Aktif Saat Ini</span>
                                @else
                                <span class="badge bg-secondary text-white">Bukan Tahun Aktif</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Deskripsi:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $academicYear->description ?? 'Tidak ada deskripsi' }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Dibuat:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $academicYear->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Terakhir Update:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $academicYear->updated_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistik</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-users fa-3x text-primary"></i>
                        </div>
                        <h4 class="text-primary">{{ $academicYear->students_count ?? 0 }}</h4>
                        <p class="text-gray-600">Total Siswa</p>
                    </div>
                    
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-chalkboard fa-3x text-success"></i>
                        </div>
                        <h4 class="text-success">{{ $academicYear->classes_count ?? 0 }}</h4>
                        <p class="text-gray-600">Total Kelas</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-list-alt fa-3x text-info"></i>
                        </div>
                        <h4 class="text-info">{{ $academicYear->feeStructures_count ?? 0 }}</h4>
                        <p class="text-gray-600">Struktur Biaya</p>
                    </div>
                </div>
            </div>
            
            @if(!$academicYear->is_current)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aksi</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('academic-years.set-current', $academicYear->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block" 
                                onclick="return confirm('Set tahun ajaran ini sebagai tahun aktif?')">
                            <i class="fas fa-check me-2"></i>Set Sebagai Tahun Aktif
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
