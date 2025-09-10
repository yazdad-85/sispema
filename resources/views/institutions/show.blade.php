@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Lembaga</h1>
        <div>
            <a href="{{ route('institutions.edit', $institution->id) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> Edit
            </a>
            <a href="{{ route('institutions.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Lembaga</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Nama Lembaga:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $institution->name }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Email:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $institution->email }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Telepon:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $institution->phone ?? 'Tidak ada data' }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Status:</strong>
                        </div>
                        <div class="col-sm-9">
                            @if($institution->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-danger">Tidak Aktif</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Alamat:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $institution->address ?? 'Tidak ada data' }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Dibuat:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $institution->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Terakhir Update:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $institution->updated_at->format('d/m/Y H:i') }}
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
                        <h4 class="text-primary">{{ $institution->students_count ?? 0 }}</h4>
                        <p class="text-gray-600">Total Siswa</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-chalkboard fa-3x text-success"></i>
                        </div>
                        <h4 class="text-success">{{ $institution->classes_count ?? 0 }}</h4>
                        <p class="text-gray-600">Total Kelas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
