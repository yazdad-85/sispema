@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import Data Siswa</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Download Template Excel</h5>
                                </div>
                                <div class="card-body">
                                    <p>Pilih Tahun Ajaran dan Lembaga untuk download template Excel yang sudah disesuaikan:</p>
                                    
                                    <form id="templateForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="academic_year_id">Tahun Ajaran *</label>
                                                    <select class="form-control" id="academic_year_id" name="academic_year_id" required>
                                                        <option value="">-- Pilih Tahun Ajaran --</option>
                                                        @foreach($academicYears as $year)
                                                            <option value="{{ $year->id }}" 
                                                                    data-year-start="{{ $year->year_start }}" 
                                                                    data-year-end="{{ $year->year_end }}">
                                                                {{ $year->year_start }}/{{ $year->year_end }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="institution_id">Nama Lembaga *</label>
                                                    <select class="form-control" id="institution_id" name="institution_id" required>
                                                        <option value="">-- Pilih Lembaga --</option>
                                                        @foreach($institutions as $institution)
                                                            <option value="{{ $institution->id }}">
                                                                {{ $institution->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <strong>Template akan berisi:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Data lembaga dan kelas yang dipilih</li>
                                                <li>Petunjuk pengisian yang lengkap</li>
                                                <li>Data referensi untuk validasi</li>
                                            </ul>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success btn-lg" id="downloadBtn" disabled>
                                            <i class="fas fa-download"></i> Download Template Excel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Upload File Excel</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        
                                        <div class="form-group">
                                            <label for="file">Pilih File Excel (.xlsx)</label>
                                            <input type="file" class="form-control @error('file') is-invalid @enderror" 
                                                   id="file" name="file" accept=".xlsx,.xls" required>
                                            @error('file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Pastikan file menggunakan template yang sudah didownload
                                            </small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-upload"></i> Import Data
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Informasi Template</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6>Data yang Otomatis:</h6>
                                            <ul>
                                                @if(Auth::user()->isSuperAdmin())
                                                    <li>Lembaga: Pilih dari dropdown</li>
                                                    <li>Kelas: Pilih dari dropdown</li>
                                                @else
                                                    <li>
                                                        Lembaga: {{ Auth::user()->institutions->pluck('name')->join(', ') ?: 'Belum diatur' }}
                                                    </li>
                                                    <li>Kelas: Sesuai lembaga yang diizinkan</li>
                                                @endif
                                                <li>Tahun Ajaran: {{ \App\Models\AcademicYear::where('is_current', true)->first()->year_start ?? '' }}-{{ \App\Models\AcademicYear::where('is_current', true)->first()->year_end ?? '' }}</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <h6>Kolom Wajib (*):</h6>
                                            <ul>
                                                <li>NIS (Nomor Induk Siswa)</li>
                                                <li>Nama Lengkap</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <h6>Kolom Opsional:</h6>
                                            <ul>
                                                <li>Email</li>
                                                <li>No. HP</li>
                                                <li>Alamat</li>
                                                <li>Nama Orang Tua</li>
                                                <li>No. HP Orang Tua</li>
                                                <li>Kategori Beasiswa</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Data Referensi Tersedia</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 class="text-primary">{{ $institutions->count() }}</h4>
                                                <p class="text-muted">Lembaga</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 class="text-success">{{ $academicYears->count() }}</h4>
                                                <p class="text-muted">Tahun Ajaran</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 class="text-warning">{{ $classes->count() }}</h4>
                                                <p class="text-muted">Kelas</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 class="text-info">{{ $scholarshipCategories->count() }}</h4>
                                                <p class="text-muted">Kategori Beasiswa</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="text-center">
                                <a href="{{ route('students.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Siswa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const academicYearSelect = document.getElementById('academic_year_id');
    const institutionSelect = document.getElementById('institution_id');
    const downloadBtn = document.getElementById('downloadBtn');
    const templateForm = document.getElementById('templateForm');
    
    function updateDownloadButton() {
        const academicYearId = academicYearSelect.value;
        const institutionId = institutionSelect.value;
        
        if (academicYearId && institutionId) {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download Template Excel';
        } else {
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fas fa-download"></i> Pilih Tahun Ajaran & Lembaga';
        }
    }
    
    academicYearSelect.addEventListener('change', updateDownloadButton);
    institutionSelect.addEventListener('change', updateDownloadButton);
    
    templateForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const academicYearId = academicYearSelect.value;
        const institutionId = institutionSelect.value;
        
        if (academicYearId && institutionId) {
            // Redirect to download with parameters
            const downloadUrl = '{{ route("students.download-template") }}?academic_year_id=' + academicYearId + '&institution_id=' + institutionId;
            window.location.href = downloadUrl;
        }
    });
    
    // Initialize button state
    updateDownloadButton();
});
</script>
@endpush
