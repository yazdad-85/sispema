@extends('layouts.app')

@section('title', 'Promosi Siswa')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-graduation-cap mr-2"></i>
                        Promosi Siswa
                    </h3>
                </div>
                
                <div class="card-body">
                    <!-- Warning Section -->
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Peringatan:</strong> Pastikan tahun ajaran baru sudah dibuat sebelum melakukan promosi!
                        <a href="{{ route('academic-years.index') }}" class="btn btn-sm btn-warning ml-2">
                            <i class="fas fa-calendar-plus mr-1"></i>Buat Tahun Ajaran Baru
                        </a>
                    </div>
                    
                    <!-- Current Academic Year Info -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tahun Ajaran Aktif:</strong> {{ $currentAcademicYear->year_start }}/{{ $currentAcademicYear->year_end }}
                    </div>
                    
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('student-promotions.index') }}" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="institution_id" class="mr-2">Lembaga:</label>
                                    <select name="institution_id" id="institution_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">Pilih Lembaga</option>
                                        @foreach($institutions as $institution)
                                            <option value="{{ $institution->id }}" 
                                                {{ $selectedInstitution == $institution->id ? 'selected' : '' }}>
                                                {{ $institution->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                @if($selectedInstitution)
                                <div class="form-group mr-3">
                                    <label for="level" class="mr-2">Tingkat:</label>
                                    <select name="level" id="level" class="form-control" onchange="this.form.submit()">
                                        <option value="">Pilih Tingkat</option>
                                        @foreach($availableLevels as $level)
                                            <option value="{{ $level }}" 
                                                {{ $selectedLevel == $level ? 'selected' : '' }}>
                                                {{ $level }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search mr-1"></i>Filter
                                    </button>
                                    <a href="{{ route('student-promotions.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-refresh mr-1"></i>Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Students by Level -->
                    @if($studentsByLevel->isNotEmpty())
                        @foreach($studentsByLevel as $level => $students)
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users mr-2"></i>
                                        Tingkat {{ $level }} ({{ $students->count() }} siswa)
                                    </h5>
                                </div>
                                
                                <div class="card-body">
                                    <!-- Level Actions -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <div class="btn-group" role="group">
                                                <input type="checkbox" id="selectAll{{ $level }}" 
                                                       onchange="toggleAllStudents('{{ $level }}')" 
                                                       class="btn-check">
                                                <label class="btn btn-outline-primary" for="selectAll{{ $level }}">
                                                    <i class="fas fa-check-square mr-1"></i>Pilih Semua
                                                </label>
                                                
                                                @if(in_array($level, ['IX', 'XII']))
                                                    <button type="button" class="btn btn-danger"
                                                        onclick="promoteSelected('{{ $level }}', 'graduate')">
                                                        <i class="fas fa-graduation-cap mr-1"></i> Lulus
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-warning"
                                                        onclick="promoteSelected('{{ $level }}', 'grade_up')">
                                                        <i class="fas fa-arrow-up mr-1"></i> Naik Kelas
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Students Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">Pilih</th>
                                                    <th width="10%">NIS</th>
                                                    <th width="25%">Nama</th>
                                                    <th width="20%">Kelas</th>
                                                    <th width="20%">Lembaga</th>
                                                    <th width="20%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($students as $student)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" 
                                                                   class="student-checkbox" 
                                                                   data-level="{{ $level }}"
                                                                   value="{{ $student->id }}">
                                                        </td>
                                                        <td>{{ $student->nis }}</td>
                                                        <td>{{ $student->name }}</td>
                                                        <td>{{ $student->classRoom->class_name ?? '-' }}</td>
                                                        <td>{{ $student->classRoom->institution->name ?? '-' }}</td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                @if(in_array($level, ['IX', 'XII']))
                                                                    <button type="button" class="btn btn-danger btn-sm"
                                                                        onclick="promoteIndividual('{{ $student->id }}', 'graduate')">
                                                                        <i class="fas fa-graduation-cap"></i>
                                                                    </button>
                                                                @else
                                                                    <button type="button" class="btn btn-warning btn-sm"
                                                                        onclick="promoteIndividual('{{ $student->id }}', 'grade_up')">
                                                                        <i class="fas fa-arrow-up"></i>
                                                                    </button>
                                                                @endif
                                                                
                                                                <button type="button" class="btn btn-info btn-sm"
                                                                    onclick="showPaymentHistory('{{ $student->id }}')">
                                                                    <i class="fas fa-history"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            @if($selectedInstitution && $selectedLevel)
                                Tidak ada siswa aktif di tingkat {{ $selectedLevel }} untuk lembaga yang dipilih.
                            @elseif($selectedInstitution)
                                Pilih tingkat untuk melihat siswa yang dapat dipromosi.
                            @else
                                Pilih lembaga dan tingkat untuk melihat siswa yang dapat dipromosi.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Individual Promotion Modal -->
<div class="modal fade" id="individualPromotionModal" tabindex="-1" aria-labelledby="individualPromotionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="individualPromotionModalLabel">Promosi Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="individualPromotionForm">
                    <input type="hidden" id="individual_student_id" name="student_id">
                    <input type="hidden" id="individual_promotion_type" name="promotion_type">
                    
                    <div class="form-group">
                        <label>Jenis Promosi:</label>
                        <p id="promotion_type_display" class="form-control-plaintext"></p>
                    </div>
                    
                    <div class="form-group" id="individualTargetClassGroup" style="display: none;">
                        <label for="individual_target_institution_id">Lembaga Target</label>
                        <select name="target_institution_id" id="individual_target_institution_id" class="form-control" onchange="loadTargetClassesByInstitution()">
                            <option value="">Pilih Lembaga Target (Opsional)</option>
                        </select>
                        
                        <label for="individual_target_class_id" class="mt-3">Kelas Target</label>
                        <select name="target_class_id" id="individual_target_class_id" class="form-control">
                            <option value="">Pilih Kelas Target (Opsional)</option>
                        </select>
                        
                        <small class="form-text text-muted">
                            <strong>Pilihan:</strong><br>
                            • Pilih kelas spesifik untuk menempatkan siswa di kelas tertentu<br>
                            • Pilih "Distribusi Otomatis" untuk sistem otomatis mendistribusikan siswa<br>
                            • Kosongkan untuk menggunakan distribusi otomatis default
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitPromotion()">Proses Promosi</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global variables
let selectedStudents = [];
let currentPromotionLevel = null;

// Toggle all students for a level
function toggleAllStudents(level) {
    const selectAllCheckbox = document.getElementById('selectAll' + level);
    const checkboxes = document.querySelectorAll(`input[data-level="${level}"].student-checkbox`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    // Update select all checkbox state
    const checkedCount = document.querySelectorAll(`input[data-level="${level}"].student-checkbox:checked`).length;
    selectAllCheckbox.checked = checkedCount === checkboxes.length;
    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
}

// Promote selected students
function promoteSelected(level, promotionType) {
    const checkboxes = document.querySelectorAll(`input[data-level="${level}"].student-checkbox:checked`);
    const studentIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (studentIds.length === 0) {
        alert('Pilih siswa yang akan dipromosi terlebih dahulu!');
        return;
    }
    
    // Store current level for auto-distribution
    currentPromotionLevel = level;
    
    if (promotionType === 'grade_up') {
        // Show modal for grade up with class selection
        showPromotionModal(studentIds, promotionType);
    } else {
        // Direct graduation
        if (confirm(`Apakah Anda yakin akan meluluskan ${studentIds.length} siswa dari tingkat ${level}?`)) {
            submitBulkPromotion(studentIds, promotionType);
        }
    }
}

// Promote individual student
function promoteIndividual(studentId, promotionType) {
    if (promotionType === 'grade_up') {
        showPromotionModal([studentId], promotionType);
    } else {
        if (confirm('Apakah Anda yakin akan meluluskan siswa ini?')) {
            submitBulkPromotion([studentId], promotionType);
        }
    }
}

// Show promotion modal
function showPromotionModal(studentIds, promotionType) {
    document.getElementById('individual_student_id').value = studentIds.join(',');
    document.getElementById('individual_promotion_type').value = promotionType;
    
    const promotionTypeDisplay = document.getElementById('promotion_type_display');
    const targetClassGroup = document.getElementById('individualTargetClassGroup');
    
    if (promotionType === 'grade_up') {
        promotionTypeDisplay.textContent = 'Naik Kelas';
        targetClassGroup.style.display = 'block';
        loadTargetClasses(studentIds[0]);
    } else {
        promotionTypeDisplay.textContent = 'Lulus';
        targetClassGroup.style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('individualPromotionModal'));
    modal.show();
}

// Load target classes
function loadTargetClasses(studentId) {
    console.log('loadTargetClasses called with studentId:', studentId);
    
    fetch(`/api/classes?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            console.log('API Response for loadTargetClasses:', data);
            if (data.success) {
                loadTargetInstitutions(data.nextLevel);
            } else {
                console.error('Error loading classes:', data.message);
                // Fallback: load institutions directly
                loadTargetInstitutions();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback: load institutions directly
            loadTargetInstitutions();
        });
}

// Load target institutions
function loadTargetInstitutions(nextLevel) {
    const studentId = document.getElementById('individual_student_id').value.split(',')[0];
    const institutionSelect = document.getElementById('individual_target_institution_id');
    const classSelect = document.getElementById('individual_target_class_id');
    
    console.log('loadTargetInstitutions called with studentId:', studentId, 'nextLevel:', nextLevel);
    
    // Clear existing options
    institutionSelect.innerHTML = '<option value="">Pilih Lembaga Target (Opsional)</option>';
    classSelect.innerHTML = '<option value="">Pilih Kelas Target (Opsional)</option>';
    
    // First, try to get classes from API
    if (studentId) {
        fetch(`/api/classes?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                console.log('API Response:', data);
                
                if (data.success) {
                    // Store available classes globally (even if empty)
                    window.availableClasses = data.classes || [];
                    console.log('Available classes:', data.classes);
                    
                    if (data.classes && data.classes.length > 0) {
                        // Get unique institutions from available classes
                        const institutionMap = new Map();
                        data.classes.forEach(cls => {
                            if (!institutionMap.has(cls.institution.id)) {
                                institutionMap.set(cls.institution.id, cls.institution);
                            }
                        });
                        
                        // Populate institution dropdown
                        const uniqueInstitutions = Array.from(institutionMap.values()).sort((a, b) => a.name.localeCompare(b.name));
                        console.log('Unique institutions:', uniqueInstitutions);
                        
                        uniqueInstitutions.forEach(institution => {
                            const option = document.createElement('option');
                            option.value = institution.id;
                            option.textContent = institution.name;
                            institutionSelect.appendChild(option);
                        });
                        
                        // Auto-populate classes if only one institution
                        if (uniqueInstitutions.length === 1) {
                            institutionSelect.value = uniqueInstitutions[0].id;
                            loadTargetClassesByInstitution();
                        }
                    } else {
                        // No classes available, but still load institutions for manual selection
                        console.log('No classes available, loading all institutions');
                        loadAllInstitutions();
                        
                        // Add auto-distribute option to class dropdown
                        const autoOption = document.createElement('option');
                        autoOption.value = 'auto_distribute';
                        autoOption.textContent = 'Distribusi Otomatis (Sistem akan membuat kelas baru)';
                        classSelect.appendChild(autoOption);
                    }
                } else {
                    // Fallback: load all institutions
                    loadAllInstitutions();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback: load all institutions
                loadAllInstitutions();
            });
    } else {
        // Fallback: load all institutions
        loadAllInstitutions();
    }
}

// Load all institutions as fallback
function loadAllInstitutions() {
    console.log('Loading all institutions as fallback');
    
    // Get institutions from the page data (they should be available in the filter dropdown)
    const filterInstitutionSelect = document.getElementById('institution_id');
    const targetInstitutionSelect = document.getElementById('individual_target_institution_id');
    
    if (filterInstitutionSelect && targetInstitutionSelect) {
        // Copy options from filter dropdown
        const options = filterInstitutionSelect.querySelectorAll('option');
        options.forEach(option => {
            if (option.value) { // Skip empty option
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.textContent = option.textContent;
                targetInstitutionSelect.appendChild(newOption);
            }
        });
        
        console.log('Loaded', options.length - 1, 'institutions from filter dropdown');
    }
}

// Load target classes by institution
function loadTargetClassesByInstitution() {
    const institutionId = document.getElementById('individual_target_institution_id').value;
    const classSelect = document.getElementById('individual_target_class_id');
    
    console.log('loadTargetClassesByInstitution called with institutionId:', institutionId);
    console.log('Available classes:', window.availableClasses);
    
    // Clear existing options
    classSelect.innerHTML = '<option value="">Pilih Kelas Target (Opsional)</option>';
    
    if (!institutionId || !window.availableClasses) {
        console.log('Missing institutionId or availableClasses');
        return;
    }
    
    // Get current level
    let currentLevel = currentPromotionLevel;
    if (!currentLevel) {
        // Try to get from page
        const levelElements = document.querySelectorAll('[data-level]');
        if (levelElements.length > 0) {
            currentLevel = levelElements[0].getAttribute('data-level');
        }
    }
    
    console.log('Current level:', currentLevel);
    
    // Get next level
    const nextLevel = getNextLevel(currentLevel);
    console.log('Next level:', nextLevel);
    
    if (!nextLevel) {
        console.error('No next level found for:', currentLevel);
        return;
    }
    
    // Filter classes by institution and level
    const filteredClasses = window.availableClasses.filter(cls => 
        cls.institution.id == institutionId && cls.level === nextLevel
    );
    
    console.log('Filtered classes:', filteredClasses);
    
    if (filteredClasses.length === 0) {
        // Add auto-distribute option when no classes are available
        const autoOption = document.createElement('option');
        autoOption.value = 'auto_distribute';
        autoOption.textContent = `Distribusi Otomatis (Sistem akan membuat kelas ${nextLevel} baru)`;
        classSelect.appendChild(autoOption);
        
        // Add info message
        const infoOption = document.createElement('option');
        infoOption.value = '';
        infoOption.textContent = `Tidak ada kelas ${nextLevel} di lembaga ini - gunakan distribusi otomatis`;
        infoOption.disabled = true;
        classSelect.appendChild(infoOption);
        return;
    }
    
    // Populate class options
    filteredClasses.forEach(cls => {
        const option = document.createElement('option');
        option.value = cls.id;
        option.textContent = `${cls.class_name} (${cls.level})`;
        classSelect.appendChild(option);
    });
    
    // Add auto-distribute option if multiple classes
    if (filteredClasses.length > 1) {
        const autoOption = document.createElement('option');
        autoOption.value = 'auto_distribute';
        autoOption.textContent = `Distribusi Otomatis (${filteredClasses.length} kelas tersedia)`;
        classSelect.appendChild(autoOption);
    }
    
    console.log('Class dropdown populated with', filteredClasses.length, 'classes');
}

// Get next level
function getNextLevel(currentLevel) {
    const levelMap = {
        'VII': 'VIII',
        'VIII': 'IX',
        'IX': null,
        'X': 'XI',
        'XI': 'XII',
        'XII': null
    };
    return levelMap[currentLevel] || null;
}

// Submit promotion
function submitPromotion() {
    const studentIds = document.getElementById('individual_student_id').value.split(',');
    const promotionType = document.getElementById('individual_promotion_type').value;
    const targetClassId = document.getElementById('individual_target_class_id').value;
    
    try {
        console.log('submitPromotion called:', studentIds, promotionType);
        alert('Memproses promosi ' + studentIds.length + ' siswa...');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('promotion_type', promotionType);
        
        // Add student IDs
        studentIds.forEach(id => {
            formData.append('student_ids[]', id);
        });
        
        // Add target class if selected
        if (targetClassId) {
            formData.append('target_class_id', targetClassId);
            console.log('Target class ID:', targetClassId);
        }
        
        // Add loading indicator
        const submitButton = document.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...';
        }
        
        // Close modal before submit
        const modal = bootstrap.Modal.getInstance(document.getElementById('individualPromotionModal'));
        if (modal) {
            modal.hide();
        }
        
        // Submit using fetch
        fetch('{{ route("student-promotions.promote") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                // Reload page to show results
                window.location.reload();
            } else {
                throw new Error('Network response was not ok');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saat memproses promosi: ' + error.message);
        });
        
    } catch (error) {
        console.error('Error in submitPromotion:', error);
        alert('Error saat memproses promosi: ' + error.message);
    }
}

// Submit bulk promotion
function submitBulkPromotion(studentIds, promotionType) {
    try {
        console.log('submitBulkPromotion called:', studentIds, promotionType);
        alert('Memproses promosi ' + studentIds.length + ' siswa...');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('promotion_type', promotionType);
        
        // Add student IDs
        studentIds.forEach(id => {
            formData.append('student_ids[]', id);
        });
        
        // Submit using fetch
        fetch('{{ route("student-promotions.promote") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                // Reload page to show results
                window.location.reload();
            } else {
                throw new Error('Network response was not ok');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saat memproses promosi: ' + error.message);
        });
        
    } catch (error) {
        console.error('Error in submitBulkPromotion:', error);
        alert('Error saat memproses promosi: ' + error.message);
    }
}

// Show payment history
function showPaymentHistory(studentId) {
    window.open(`{{ route('student-promotions.payment-history', '') }}/${studentId}`, '_blank');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Student promotion script loaded');
    console.log('All functions available:', {
        toggleAllStudents: typeof toggleAllStudents,
        promoteSelected: typeof promoteSelected,
        promoteIndividual: typeof promoteIndividual,
        showPaymentHistory: typeof showPaymentHistory
    });
});
</script>
@endpush