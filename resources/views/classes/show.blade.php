@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Kelas</h1>
        <div>
            <a href="{{ route('classes.edit', $class->id) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> Edit
            </a>
            <a href="{{ route('classes.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Kelas</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Nama Kelas:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->class_name }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Jenjang:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->grade_level }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Lembaga:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->institution->name ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Tahun Ajaran:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->academicYear->name ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Kapasitas:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->capacity }} siswa
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Status:</strong>
                        </div>
                        <div class="col-sm-9">
                            @if($class->is_active)
                                <span class="badge bg-success text-white">Aktif</span>
                            @else
                                <span class="badge bg-danger text-white">Tidak Aktif</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Dibuat:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Terakhir Update:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $class->updated_at->format('d/m/Y H:i') }}
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
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-users fa-3x text-primary"></i>
                        </div>
                        <h4 class="text-primary">{{ $class->students_count ?? 0 }}</h4>
                        <p class="text-gray-600">Total Siswa</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-percentage fa-3x text-success"></i>
                        </div>
                        <h4 class="text-success">
                            @if($class->capacity > 0)
                                {{ round((($class->students_count ?? 0) / $class->capacity) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </h4>
                        <p class="text-gray-600">Tingkat Hunian</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
