@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Struktur Biaya Baru</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('fee-structures.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="institution_id">Lembaga <span class="text-danger">*</span></label>
                                    <select class="form-control @error('institution_id') is-invalid @enderror" 
                                            id="institution_id" name="institution_id" required>
                                        <option value="">Pilih Lembaga</option>
                                        @foreach($institutions as $institution)
                                            <option value="{{ $institution->id }}" 
                                                    {{ old('institution_id') == $institution->id ? 'selected' : '' }}>
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
                                    <label for="level">Level Kelas <span class="text-danger">*</span></label>
                                    <select class="form-control @error('level') is-invalid @enderror" 
                                            id="level" name="level" required>
                                        <option value="">Pilih Level</option>
                                        <option value="VII" {{ old('level') == 'VII' ? 'selected' : '' }}>VII - Tingkat 1 (SMP/MTs)</option>
                                        <option value="VIII" {{ old('level') == 'VIII' ? 'selected' : '' }}>VIII - Tingkat 2 (SMP/MTs)</option>
                                        <option value="IX" {{ old('level') == 'IX' ? 'selected' : '' }}>IX - Tingkat 3 (SMP/MTs)</option>
                                        <option value="X" {{ old('level') == 'X' ? 'selected' : '' }}>X - Tingkat 1 (SMA/MA/SMK)</option>
                                        <option value="XI" {{ old('level') == 'XI' ? 'selected' : '' }}>XI - Tingkat 2 (SMA/MA/SMK)</option>
                                        <option value="XII" {{ old('level') == 'XII' ? 'selected' : '' }}>XII - Tingkat 3 (SMA/MA/SMK)</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Pilih level kelas, bukan kelas spesifik. Sistem akan otomatis pilih kelas yang sesuai.
                                    </small>
                                    @error('level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year_id">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select class="form-control @error('academic_year_id') is-invalid @enderror" 
                                            id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Pilih Tahun Ajaran</option>
                                        @foreach($academicYears as $academicYear)
                                            <option value="{{ $academicYear->id }}" 
                                                    {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                                {{ $academicYear->year_start }}-{{ $academicYear->year_end }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="scholarship_discount">Diskon Beasiswa (%)</label>
                                    <input type="number" class="form-control @error('scholarship_discount') is-invalid @enderror" 
                                           id="scholarship_discount" name="scholarship_discount" 
                                           value="{{ old('scholarship_discount', 0) }}" 
                                           min="0" max="100" step="0.01">
                                    <small class="form-text text-muted">Persentase diskon untuk beasiswa (0-100%)</small>
                                    @error('scholarship_discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="monthly_amount">Biaya Bulanan (Rp)</label>
                                    <input type="number" class="form-control @error('monthly_amount') is-invalid @enderror" 
                                           id="monthly_amount" name="monthly_amount" 
                                           value="{{ old('monthly_amount') }}" min="0" 
                                           placeholder="500000">
                                    <small class="form-text text-muted">
                                        Input biaya per bulan. Jika kosong, akan dihitung otomatis dari biaya tahunan.
                                    </small>
                                    @error('monthly_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="yearly_amount">Biaya Tahunan (Rp)</label>
                                    <input type="number" class="form-control @error('yearly_amount') is-invalid @enderror" 
                                           id="yearly_amount" name="yearly_amount" 
                                           value="{{ old('yearly_amount') }}" min="0" 
                                           placeholder="6000000">
                                    <small class="form-text text-muted">
                                        Input biaya per tahun. Jika kosong, akan dihitung otomatis dari biaya bulanan.
                                    </small>
                                    @error('yearly_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-calculator"></i> Sistem Kalkulasi Otomatis:</h6>
                            <ul class="mb-0">
                                <li><strong>Input Bulanan saja:</strong> Biaya tahunan = Bulanan × 12</li>
                                <li><strong>Input Tahunan saja:</strong> Biaya bulanan = Tahunan ÷ 12</li>
                                <li><strong>Input keduanya:</strong> Gunakan nilai yang diinput (untuk override)</li>
                            </ul>
                            <small class="text-muted">
                                <strong>Contoh:</strong> Input biaya tahunan Rp 4.500.000 → Otomatis jadi Rp 375.000/bulan
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Penjelasan tambahan tentang struktur biaya ini</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Hidden field untuk class_id yang akan diisi otomatis -->
                        <input type="hidden" id="class_id" name="class_id" value="{{ old('class_id') }}">
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Sistem Level Kelas:</h6>
                            <ul class="mb-0">
                                <li><strong>VII, X:</strong> Tingkat 1 (Biaya dasar)</li>
                                <li><strong>VIII, XI:</strong> Tingkat 2 (Biaya +10%)</li>
                                <li><strong>IX, XII:</strong> Tingkat 3 (Biaya +20%)</li>
                            </ul>
                            <small class="text-muted">
                                Ketika Anda memilih level, sistem akan otomatis memilih kelas yang sesuai dari lembaga yang dipilih.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Struktur Biaya Aktif</label>
                            </div>
                            <small class="form-text text-muted">Struktur biaya yang tidak aktif tidak akan muncul di form siswa</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('fee-structures.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate yearly amount when monthly amount changes
document.getElementById('monthly_amount').addEventListener('input', function() {
    const monthlyAmount = parseFloat(this.value) || 0;
    const yearlyAmount = monthlyAmount * 12;
    
    // Update yearly amount field
    document.getElementById('yearly_amount').value = yearlyAmount;
    
    // Update info text
    updateCalculationInfo();
});

// Auto-calculate monthly amount when yearly amount changes
document.getElementById('yearly_amount').addEventListener('input', function() {
    const yearlyAmount = parseFloat(this.value) || 0;
    const monthlyAmount = Math.round(yearlyAmount / 12);
    
    // Update monthly amount field
    document.getElementById('monthly_amount').value = monthlyAmount;
    
    // Update info text
    updateCalculationInfo();
});

// Function to update calculation info
function updateCalculationInfo() {
    const monthlyAmount = parseFloat(document.getElementById('monthly_amount').value) || 0;
    const yearlyAmount = parseFloat(document.getElementById('yearly_amount').value) || 0;
    
    if (monthlyAmount > 0 && yearlyAmount > 0) {
        // Both filled - show manual override message
        showInfoMessage('info', 'Kedua field diisi. Nilai akan digunakan sesuai input manual.');
    } else if (monthlyAmount > 0) {
        // Only monthly filled
        const calculatedYearly = monthlyAmount * 12;
        showInfoMessage('success', `Biaya tahunan akan dihitung otomatis: Rp ${calculatedYearly.toLocaleString('id-ID')}`);
    } else if (yearlyAmount > 0) {
        // Only yearly filled
        const calculatedMonthly = Math.round(yearlyAmount / 12);
        showInfoMessage('success', `Biaya bulanan akan dihitung otomatis: Rp ${calculatedMonthly.toLocaleString('id-ID')}`);
    } else {
        // Both empty
        showInfoMessage('warning', 'Input salah satu: biaya bulanan ATAU biaya tahunan');
    }
}

// Function to show info message
function showInfoMessage(type, message) {
    const infoDiv = document.getElementById('calculation-info');
    if (!infoDiv) {
        // Create info div if it doesn't exist
        const newInfoDiv = document.createElement('div');
        newInfoDiv.id = 'calculation-info';
        newInfoDiv.className = `alert alert-${type} mt-2`;
        newInfoDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
        
        // Insert after yearly amount field
        const yearlyField = document.getElementById('yearly_amount').closest('.form-group');
        yearlyField.appendChild(newInfoDiv);
    } else {
        // Update existing info div
        infoDiv.className = `alert alert-${type} mt-2`;
        infoDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
    }
}

// Initialize calculation info on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCalculationInfo();
});

// Auto-select class based on level and institution
document.getElementById('level').addEventListener('change', function() {
    const level = this.value;
    const institutionId = document.getElementById('institution_id').value;
    const classIdField = document.getElementById('class_id');
    
    if (level && institutionId) {
        // Fetch classes for selected institution and level
        fetch(`/api/institutions/${institutionId}/classes`)
            .then(response => response.json())
            .then(data => {
                if (data.classes && data.classes.length > 0) {
                    // Find class with matching level
                    const matchingClass = data.classes.find(classItem => {
                        // Extract level from class name (e.g., "VII A" -> "VII", "X TPM 1" -> "X")
                        const className = classItem.class_name.toUpperCase();
                        if (className.includes('VII')) return level === 'VII';
                        if (className.includes('VIII')) return level === 'VIII';
                        if (className.includes('IX')) return level === 'IX';
                        if (className.includes('X') && !className.includes('XI') && !className.includes('XII')) return level === 'X';
                        if (className.includes('XI')) return level === 'XI';
                        if (className.includes('XII')) return level === 'XII';
                        return false;
                    });
                    
                    if (matchingClass) {
                        classIdField.value = matchingClass.id;
                        console.log(`Auto-selected class: ${matchingClass.class_name} for level ${level}`);
                    } else {
                        classIdField.value = '';
                        console.warn(`No class found for level ${level} in institution ${institutionId}`);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching classes:', error);
                classIdField.value = '';
            });
    } else {
        classIdField.value = '';
    }
});

// Dynamic class loading based on selected institution
const instSelect = document.getElementById('institution_id');
if (instSelect) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Error loading classes';
                option.disabled = true;
                classSelect.appendChild(option);
            });
    }
});
}
</script>
@endsection
