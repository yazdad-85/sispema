@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Tahun Ajaran</h1>
        <a href="{{ route('academic-years.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Edit Tahun Ajaran</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('academic-years.update', $academicYear->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="year_start">Tahun Mulai <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('year_start') is-invalid @enderror" 
                                   id="year_start" name="year_start" value="{{ old('year_start', $academicYear->year_start) }}" 
                                   min="2000" max="2100" required>
                            @error('year_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="year_end">Tahun Selesai <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('year_end') is-invalid @enderror" 
                                   id="year_end" name="year_end" value="{{ old('year_end', $academicYear->year_end) }}" 
                                   min="2000" max="2100" required>
                            @error('year_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="active" {{ old('status', $academicYear->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ old('status', $academicYear->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="is_current">Tahun Ajaran Aktif</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" 
                                       id="is_current" name="is_current" value="1" 
                                       {{ old('is_current', $academicYear->is_current) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_current">
                                    Set sebagai tahun ajaran aktif saat ini
                                </label>
                            </div>
                            @error('is_current')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3" 
                              placeholder="Contoh: Tahun Ajaran 2024/2025">{{ old('description', $academicYear->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="{{ route('academic-years.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearStart = document.getElementById('year_start');
    const yearEnd = document.getElementById('year_end');
    
    // Validate year_end > year_start
    yearEnd.addEventListener('change', function() {
        if (parseInt(this.value) <= parseInt(yearStart.value)) {
            this.setCustomValidity('Tahun selesai harus lebih besar dari tahun mulai');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>
@endsection
