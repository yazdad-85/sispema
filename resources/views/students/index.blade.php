@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Siswa</h3>
                    <div>
                        <a href="{{ route('students.import-template') }}" class="btn btn-success me-2">
                            <i class="fas fa-file-excel"></i> Import Excel
                        </a>
                        <a href="{{ route('students.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Siswa
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            {{ session('warning') }}
                        </div>
                    @endif

                    @if(session('import_errors'))
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            <h6>Detail Error Import:</h6>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('students.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <label for="search" class="form-label">Cari</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="{{ request('search') }}" placeholder="NIS/Nama">
                                </div>
                                <div class="col-md-2">
                                    <label for="institution_id" class="form-label">Lembaga</label>
                                    <select class="form-control" id="institution_id" name="institution_id">
                                        <option value="">Semua Lembaga</option>
                                        @foreach($institutions as $institution)
                                            <option value="{{ $institution->id }}" 
                                                {{ request('institution_id') == $institution->id ? 'selected' : '' }}>
                                                {{ $institution->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="class_id" class="form-label">Kelas</label>
                                    <select class="form-control" id="class_id" name="class_id">
                                        <option value="">Semua Kelas</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" 
                                                {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->class_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="academic_year_id" class="form-label">Tahun Ajaran</label>
                                    <select class="form-control" id="academic_year_id" name="academic_year_id">
                                        <option value="">Semua Tahun</option>
                                        @foreach($academicYears as $academicYear)
                                            <option value="{{ $academicYear->id }}" 
                                                {{ request('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                                {{ $academicYear->year_start }}-{{ $academicYear->year_end }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <a href="{{ route('students.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Lembaga</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Beasiswa</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td>{{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}</td>
                                        <td>{{ $student->nis }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->institution->name ?? '-' }}</td>
                                        <td>{{ $student->classRoom->class_name ?? '-' }}</td>
                                        <td>{{ $student->academicYear->year_start ?? '' }}-{{ $student->academicYear->year_end ?? '' }}</td>
                                        <td>
                                            @if($student->scholarshipCategory)
                                                <span class="badge bg-info text-white">
                                                    {{ $student->scholarshipCategory->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'danger' }} text-dark">
                                                {{ ucfirst($student->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('students.show', $student->id) }}" 
                                                   class="btn btn-sm btn-info" title="Lihat">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('students.edit', $student->id) }}" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('students.destroy', $student->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            title="Hapus" 
                                                            onclick="return confirm('Yakin ingin menghapus siswa ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada data siswa</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $students->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    
    /* Ensure pagination is compact and shows ellipsis properly */
    .pagination .page-item:not(.active):not(:first-child):not(:last-child) {
        margin: 0 0.125rem;
    }
    
    /* Style ellipsis dots */
    .pagination .page-item.disabled .page-link {
        border: none;
        background: transparent;
        color: #6c757d;
    }
    
    /* Custom dropdown styling for better appearance */
    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Make dropdowns more readable */
    select.form-control {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        padding-right: 2.5rem;
    }
    
    /* As a hard fallback, hide any oversized overlay by clipping page overflow */
    .container-fluid { overflow: hidden; }
</style>

<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up event listeners...');
    
    const institutionSelect = document.getElementById('institution_id');
    const classSelect = document.getElementById('class_id');
    
    if (institutionSelect && classSelect) {
        console.log('Found institution and class selects');
        
        // Add some basic styling and functionality for dropdowns
        console.log('Setting up vanilla JavaScript dropdown functionality');
        
        // Dynamic class loading based on selected institution
        institutionSelect.addEventListener('change', function() {
            const institutionId = this.value;
            console.log('Institution changed to:', institutionId);
            
            // Auto-submit form to reload page with new institution filter
            // This ensures classes are loaded from server with correct data
            const form = this.closest('form');
            if (form) {
                // Clear class selection before submitting
                classSelect.value = '';
                
                // Submit form to reload page
                form.submit();
            }
        });
        
        // Add event listener for reset button
        const resetButton = document.querySelector('a[href*="students"]');
        if (resetButton) {
            resetButton.addEventListener('click', function(e) {
                // Reset class dropdown to initial state
                classSelect.innerHTML = '<option value="">Pilih Lembaga terlebih dahulu</option>';
            });
        }
        
        console.log('Event listener attached successfully');
    } else {
        console.error('Could not find institution or class select elements');
    }
});
</script>
@endsection
