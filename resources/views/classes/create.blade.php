@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Kelas Baru</h1>
        <a href="{{ route('classes.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Kelas</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('classes.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="grade_level">Jenjang <span class="text-danger">*</span></label>
                            <select class="form-control @error('grade_level') is-invalid @enderror" 
                                    id="grade_level" name="grade_level" required onchange="updateClassOptions()">
                                <option value="">Pilih Jenjang</option>
                                <option value="MI" {{ old('grade_level') == 'MI' ? 'selected' : '' }}>MI (Madrasah Ibtidaiyah)</option>
                                <option value="SD" {{ old('grade_level') == 'SD' ? 'selected' : '' }}>SD (Sekolah Dasar)</option>
                                <option value="MTs" {{ old('grade_level') == 'MTs' ? 'selected' : '' }}>MTs (Madrasah Tsanawiyah)</option>
                                <option value="SMP" {{ old('grade_level') == 'SMP' ? 'selected' : '' }}>SMP (SMP)</option>
                                <option value="MA" {{ old('grade_level') == 'MA' ? 'selected' : '' }}>MA (Madrasah Aliyah)</option>
                                <option value="SMA" {{ old('grade_level') == 'SMA' ? 'selected' : '' }}>SMA (SMA)</option>
                                <option value="SMK" {{ old('grade_level') == 'SMK' ? 'selected' : '' }}>SMK (SMK)</option>
                            </select>
                            @error('grade_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="class_level">Kelas <span class="text-danger">*</span></label>
                            <select class="form-control @error('class_level') is-invalid @enderror" 
                                    id="class_level" name="class_level" required>
                                <option value="">Pilih Jenjang terlebih dahulu</option>
                            </select>
                            @error('class_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="class_name">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('class_name') is-invalid @enderror" 
                                   id="class_name" name="class_name" value="{{ old('class_name') }}" 
                                   placeholder="Contoh: 10 IPA, 10 TPM 1, XI IPS 2" required>
                            @error('class_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="institution_id">Lembaga <span class="text-danger">*</span></label>
                            <select class="form-control @error('institution_id') is-invalid @enderror" 
                                    id="institution_id" name="institution_id" required>
                                <option value="">Pilih Lembaga</option>
                                @foreach($institutions as $institution)
                                    <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>
                                        {{ $institution->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('institution_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="academic_year_id">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select class="form-control @error('academic_year_id') is-invalid @enderror" 
                                    id="academic_year_id" name="academic_year_id" required>
                                <option value="">Pilih Tahun Ajaran</option>
                                @foreach($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}" {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                        {{ $academicYear->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_year_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="capacity">Kapasitas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" 
                                   id="capacity" name="capacity" value="{{ old('capacity') }}" min="1" required>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select class="form-control @error('is_active') is-invalid @enderror" 
                                    id="is_active" name="is_active">
                                <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateClassOptions() {
    const gradeLevel = document.getElementById('grade_level').value;
    const classLevelSelect = document.getElementById('class_level');
    
    // Reset options
    classLevelSelect.innerHTML = '<option value="">Pilih Kelas</option>';
    
    if (gradeLevel === 'MI' || gradeLevel === 'SD') {
        // Kelas 1-6 untuk MI/SD
        for (let i = 1; i <= 6; i++) {
            const option = document.createElement('option');
            option.value = `Kelas ${i}`;
            option.textContent = `Kelas ${i}`;
            classLevelSelect.appendChild(option);
        }
    } else if (gradeLevel === 'MTs' || gradeLevel === 'SMP') {
        // Kelas 7-9 untuk MTs/SMP
        for (let i = 7; i <= 9; i++) {
            const option = document.createElement('option');
            option.value = `Kelas ${i}`;
            option.textContent = `Kelas ${i}`;
            classLevelSelect.appendChild(option);
        }
    } else if (gradeLevel === 'MA' || gradeLevel === 'SMA' || gradeLevel === 'SMK') {
        // Kelas 10-12 untuk MA/SMA/SMK
        for (let i = 10; i <= 12; i++) {
            const option = document.createElement('option');
            option.value = `Kelas ${i}`;
            option.textContent = `Kelas ${i}`;
            classLevelSelect.appendChild(option);
        }
    }
}

// Set initial class options if grade level is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const gradeLevel = document.getElementById('grade_level').value;
    if (gradeLevel) {
        updateClassOptions();
    }
});
</script>
@endsection
