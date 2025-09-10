@extends('layouts.app')

@section('content')
<style>
    /* Pagination size tweaks */
    .pagination { gap: .25rem; }
    .pagination .page-link {
        padding: .25rem .5rem;
        font-size: .875rem;
        line-height: 1.2;
        border-radius: .25rem;
    }
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link { border-radius: .25rem; }
    .card-footer .text-muted { font-size: .875rem; }
    /* Ensure large decorative arrows (if any) don't affect layout */
    .table-responsive + .card-footer { margin-top: .5rem; }
    
    /* Force-hide any global carousel controls that might leak on this page */
    .carousel, .carousel-inner, .carousel-item { position: static !important; }
    .carousel-control-prev, .carousel-control-next,
    .carousel-control-prev-icon, .carousel-control-next-icon {
        display: none !important;
        width: 0 !important; height: 0 !important;
    }
    
    /* If any icon-based arrows are injected, keep them small */
    a[rel="prev"], a[rel="next"] { font-size: .875rem !important; line-height: 1.2 !important; }
    .fa-angle-left, .fa-angle-right, .fa-chevron-left, .fa-chevron-right { font-size: 1rem !important; }
    /* Tailwind-style SVG arrows from Laravel paginator */
    nav[role="navigation"] svg,
    .pagination svg,
    svg.w-5.h-5 { width: 1rem !important; height: 1rem !important; }
    /* Hide DataTables default info and prev/next controls if initialized globally */
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { display: none !important; }
    /* Hide Laravel Tailwind paginator previous/next section, keep numeric only */
    nav[role="navigation"] > div:first-child { display: none !important; }
    nav[role="navigation"] > div:last-child { display: block !important; }
    nav[role="navigation"] > div:last-child > div:first-child { display: none !important; }
    nav[role="navigation"] > div:last-child > div:last-child { display: block !important; }
    /* As a hard fallback, hide any oversized overlay by clipping page overflow */
    .container-fluid { overflow: hidden; }
</style>
</style>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Kelas</h1>
                    <div class="d-flex gap-2">
                <a href="{{ route('classes.export-template') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-download fa-sm text-white-50"></i> Download Template Excel
                </a>
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-upload fa-sm text-white-50"></i> Import Excel
                </button>
                <a href="{{ route('classes.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Kelas
                </a>
            </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter & Pencarian</h6>
        </div>
        

        <div class="card-body">
            <form method="GET" action="{{ route('classes.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="institution_id" class="form-label">Lembaga</label>
                    <select class="form-control" id="institution_id" name="institution_id">
                        <option value="">Semua Lembaga</option>
                        @foreach($institutions as $institution)
                            <option value="{{ $institution->id }}" {{ request('institution_id') == $institution->id ? 'selected' : '' }}>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="academic_year_id" class="form-label">Tahun Ajaran</label>
                    <select class="form-control" id="academic_year_id" name="academic_year_id">
                        <option value="">Semua Tahun Ajaran</option>
                        @isset($academicYears)
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Nama kelas atau jenjang...">
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kelas</th>
                            <th>Jenjang</th>
                            <th>Lembaga</th>
                            <th>Tahun Ajaran</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                        <tr>
                            <td>{{ $classes->firstItem() + $loop->index }}</td>
                            <td>{{ $class->class_name }}</td>
                            <td>{{ $class->grade_level }}</td>
                            <td>{{ $class->institution->name ?? 'N/A' }}</td>
                            <td>{{ $class->academicYear->name ?? 'N/A' }}</td>
                            <td>{{ $class->capacity }}</td>
                            <td>
                                @if($class->is_active)
                                    <span class="badge bg-success text-white">Aktif</span>
                                @else
                                    <span class="badge bg-danger text-white">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('classes.show', $class->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('classes.edit', $class->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('classes.destroy', $class->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data kelas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Menampilkan {{ $classes->firstItem() ?? 0 }} - {{ $classes->lastItem() ?? 0 }} 
                        dari {{ $classes->total() }} data
                    </div>
                    <div>
                        {{ $classes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Data Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('classes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih File Excel</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        <div class="form-text">Format file: .xlsx atau .xls (Max: 2MB)</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Petunjuk Import:</h6>
                        <ol class="mb-0">
                            <li>Download template Excel terlebih dahulu</li>
                            <li>Isi data sesuai format template (mulai dari baris 4)</li>
                            <li>Pastikan Lembaga dan Tahun Ajaran sesuai dengan data yang ada</li>
                            <li>Upload file Excel yang sudah diisi</li>
                            <li>Template sudah berisi styling dan format yang rapi</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
