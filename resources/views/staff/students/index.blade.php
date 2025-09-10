@extends('layouts.app')

@section('content')
<style>
/* Custom Pagination Styling */
.pagination {
    margin: 0;
    gap: 5px;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination .page-item .page-link {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 8px 12px;
    font-size: 14px;
    transition: all 0.2s ease;
    min-width: 40px;
    text-align: center;
}

.pagination .page-item .page-link:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    color: #495057;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
    font-weight: 600;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
}

/* Bootstrap Pagination Arrow Icons */
.pagination .page-link .fas {
    font-size: 12px;
    font-weight: 600;
}

.pagination .page-item:first-child .page-link,
.pagination .page-item:last-child .page-link {
    padding: 8px 10px;
    min-width: 36px;
}

/* Filter styling */
.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-label.fw-bold {
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Table improvements */
.table th {
    font-weight: 600;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #333 !important;
}

.table td {
    vertical-align: middle;
    padding: 0.75rem;
    color: #333 !important;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.02);
}

.table-striped tbody tr:nth-of-type(even) {
    background-color: #ffffff;
}

/* Ensure all text in table is readable */
.table {
    color: #333 !important;
}

.table tbody tr {
    color: #333 !important;
}

.table tbody tr td {
    color: #333 !important;
}

.table tbody tr th {
    color: #333 !important;
}

/* Override any conflicting styles */
.table * {
    color: inherit !important;
}

.table td *,
.table th * {
    color: #333 !important;
}

/* Specific text elements in table */
.table .text-muted {
    color: #6c757d !important;
}

.table .font-weight-bold {
    color: #333 !important;
    font-weight: 600 !important;
}

.table .text-danger {
    color: #dc3545 !important;
}

.table .text-success {
    color: #28a745 !important;
}

.table .text-warning {
    color: #ffc107 !important;
}

.table .text-info {
    color: #17a2b8 !important;
}

/* Force table text colors */
.table-responsive .table {
    color: #333 !important;
}

.table-responsive .table td,
.table-responsive .table th {
    color: #333 !important;
}

/* Override any Bootstrap or other framework colors */
.table td,
.table th,
.table caption {
    color: #333 !important;
}

/* Badge improvements */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
    border-radius: 0.375rem;
}

/* Badge colors with readable text */
.badge.bg-success {
    background-color: #28a745 !important;
    color: white !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
    color: white !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
    color: white !important;
}

.badge.bg-primary {
    background-color: #007bff !important;
    color: white !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
    color: white !important;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .pagination {
        gap: 3px;
    }
    
    .pagination .page-item .page-link {
        padding: 6px 10px;
        font-size: 13px;
        min-width: 35px;
    }
    
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        padding: 6px 8px;
        min-width: 32px;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}

/* Card improvements */
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.card-header h4 {
    margin: 0;
    font-weight: 600;
}

/* Button improvements */
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Custom Badge Colors for Better Readability */
.badge.bg-warning {
    background-color: #ff6b35 !important; /* Orange instead of yellow */
    color: white !important;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.badge.bg-success {
    background-color: #28a745 !important;
    color: white !important;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.badge.bg-info {
    background-color: #17a2b8 !important;
    color: white !important;
    font-weight: 600;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
    color: white !important;
    font-weight: 600;
}

/* Table Row Colors for Better Contrast */
.table-warning {
    background-color: #fff3cd !important; /* Light yellow with better contrast */
    border-left: 4px solid #ff6b35;
}

.table-success {
    background-color: #d4edda !important; /* Light green */
    border-left: 4px solid #28a745;
}

.table-info {
    background-color: #d1ecf1 !important; /* Light blue */
    border-left: 4px solid #17a2b8;
}

/* Status Badge Improvements */
.status-badge {
    font-size: 0.85em;
    padding: 0.5em 0.75em;
    border-radius: 0.375rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Amount Badge Improvements */
.amount-badge {
    font-size: 0.9em;
    padding: 0.6em 0.8em;
    border-radius: 0.375rem;
    font-weight: 600;
}

/* Table Header Improvements */
.table th {
    font-weight: 600;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #333 !important;
}

/* Table Cell Improvements */
.table td {
    vertical-align: middle;
    padding: 0.75rem;
    color: #333 !important;
}

/* Responsive Badge Sizing */
@media (max-width: 768px) {
    .badge {
        font-size: 0.75em;
        padding: 0.4em 0.6em;
    }
    
    .status-badge {
        font-size: 0.8em;
        padding: 0.4em 0.6em;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-users me-2"></i>Data Siswa</h4>
                    <p class="text-muted mb-0">Halaman ini menampilkan data siswa dari lembaga yang Anda kelola</p>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="institution_filter" class="form-label fw-bold">Lembaga</label>
                            <select class="form-control form-select" id="institution_filter">
                                <option value="">Semua Lembaga</option>
                                @foreach($institutions as $institution)
                                    <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="class_filter" class="form-label fw-bold">Kelas</label>
                            <select class="form-control form-select" id="class_filter">
                                <option value="">Semua Kelas</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="academic_year_filter" class="form-label fw-bold">Tahun Ajaran</label>
                            <select class="form-control form-select" id="academic_year_filter">
                                <option value="">Semua Tahun</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->year_start }}-{{ $year->year_end }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="search_filter" class="form-label fw-bold">Cari Siswa</label>
                            <input type="text" class="form-control" id="search_filter" placeholder="NIS atau Nama Siswa">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                <i class="fas fa-search me-1"></i> Terapkan Filter
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i> Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Lengkap</th>
                                    <th>Lembaga</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Beasiswa</th>
                                    <th>Status</th>
                                    <th>Total Kewajiban</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>{{ $student->nis }}</strong></td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->institution->name ?? '-' }}</td>
                                        <td>{{ $student->classRoom->class_name ?? '-' }}</td>
                                        <td>
                                            {{ $student->academicYear->year_start ?? '' }}-{{ $student->academicYear->year_end ?? '' }}
                                        </td>
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
                                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'danger' }}">
                                                {{ ucfirst($student->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                // Calculate total outstanding with scholarship discount like in detail page
                                                $currentAcademicYear = $student->academicYear;
                                                $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                                
                                                // Get FeeStructure for current academic year
                                                $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($student->institution_id, $currentAcademicYear->id, $currentLevel) : null;
                                                $yearlyAmount = $fs ? (float)$fs->yearly_amount : 0;
                                                
                                                // Scholarship calculation with level restrictions (Alumni & Yatim only at VII/X)
                                                $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                                $scholarshipCategory = $student->scholarshipCategory;
                                                
                                                // Check if scholarship applies to current level
                                                $scholarshipApplies = true;
                                                if ($scholarshipCategory) {
                                                    $categoryName = $scholarshipCategory->name;
                                                    $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                                    if (in_array($categoryName, ['Alumni', 'Yatim Piatu, Piatu, Yatim'])) {
                                                        $scholarshipApplies = in_array($currentLevel, ['VII', 'X']);
                                                    }
                                                }
                                                
                                                // Apply discount only if scholarship applies to current level
                                                if ($scholarshipApplies) {
                                                    $discountAmount = $yearlyAmount * ($scholarshipPct/100);
                                                } else {
                                                    $discountAmount = 0;
                                                    $scholarshipPct = 0; // Reset percentage for display
                                                }
                                                $effectiveYearly = max(0, $yearlyAmount - $discountAmount);
                                                
                                                // Check if student has 100% scholarship discount
                                                $hasFullDiscount = $scholarshipPct >= 100;
                                                
                                                // Calculate previous debt (use annual previous year billing if available)
                                                $computedPrevDebt = 0;
                                                if ($student->academicYear) {
                                                    $prevYear = $student->academicYear->year_start - 1;
                                                    $prevHyphen = $prevYear . '-' . ($prevYear + 1);
                                                    $prevSlash = $prevYear . '/' . ($prevYear + 1);
                                                    $annualPrev = $student->billingRecords
                                                        ->where('notes', 'ANNUAL')
                                                        ->first(function($br) use ($prevHyphen, $prevSlash){
                                                            return $br->origin_year === $prevHyphen || $br->origin_year === $prevSlash;
                                                        });
                                                    if ($annualPrev) {
                                                        $paidPrev = $student->payments
                                                            ->where('billing_record_id', $annualPrev->id)
                                                            ->whereIn('status', ['verified', 'completed'])
                                                            ->sum('total_amount');
                                                        $computedPrevDebt = max(0, (float)$annualPrev->amount - (float)$paidPrev);
                                                    }
                                                }
                                                $previousDebt = max((float)($student->previous_debt ?? 0), (float)$computedPrevDebt);
                                                
                                                // Apply scholarship rules for previous starting levels (VII/X):
                                                // - Yatim 100% => previous debt becomes 0
                                                // - Alumni X% => previous debt reduced by X%
                                                $currentLevelForPrev = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                                $previousLevel = $currentLevelForPrev;
                                                if ($currentLevelForPrev === 'VIII') { $previousLevel = 'VII'; }
                                                elseif ($currentLevelForPrev === 'IX') { $previousLevel = 'VIII'; }
                                                elseif ($currentLevelForPrev === 'XI') { $previousLevel = 'X'; }
                                                elseif ($currentLevelForPrev === 'XII') { $previousLevel = 'XI'; }
                                                $discountPctPrev = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                                $categoryNamePrev = optional($student->scholarshipCategory)->name;
                                                $isYatimCategory = $categoryNamePrev === 'Yatim Piatu, Piatu, Yatim' && $discountPctPrev >= 100;
                                                $isAlumniCategory = $categoryNamePrev === 'Alumni' && $discountPctPrev > 0;
                                                if (in_array($previousLevel, ['VII','X'])) {
                                                    if ($isYatimCategory) {
                                                        $previousDebt = 0;
                                                    } elseif ($isAlumniCategory) {
                                                        $previousDebt = max(0, $previousDebt * (1 - ($discountPctPrev/100)));
                                                    }
                                                }
                                                
                                                // Calculate total obligation and remaining balance
                                                if ($hasFullDiscount) {
                                                    $totalObligation = 0;
                                                    $remainingBalance = 0;
                                                    $previousDebt = 0;
                                                } else {
                                                    $totalObligation = $effectiveYearly + $previousDebt;
                                                    $creditBalance = (float)($student->credit_balance ?? 0);
                                                    $remainingBalance = max(0, $effectiveYearly - $creditBalance);
                                                }
                                            @endphp
                                            <div>
                                                <strong>Rp {{ number_format($totalObligation, 0, ',', '.') }}</strong>
                                                @if($scholarshipPct > 0)
                                                    <br>
                                                    <small class="text-success">
                                                        <i class="fas fa-gift me-1"></i>Diskon {{ $scholarshipPct }}%
                                                    </small>
                                                @endif
                                                @if($previousDebt > 0 && !$hasFullDiscount)
                                                    <br>
                                                    <small class="text-muted">
                                                        Tunggakan: {{ number_format($previousDebt, 0, ',', '.') }}
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('staff.students.show', $student->id) }}" 
                                               class="btn btn-sm btn-info" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">Tidak ada data siswa</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Menampilkan {{ $students->firstItem() ?? 0 }} sampai {{ $students->lastItem() ?? 0 }} 
                            dari {{ $students->total() }} data siswa
                        </div>
                        <div>
                            {{ $students->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const institutionFilter = document.getElementById('institution_filter').value;
    const classFilter = document.getElementById('class_filter').value;
    const yearFilter = document.getElementById('academic_year_filter').value;
    const searchFilter = document.getElementById('search_filter').value;
    
    let url = '{{ route("staff.students.index") }}?';
    const params = new URLSearchParams();
    
    if (institutionFilter) params.append('institution_id', institutionFilter);
    if (classFilter) params.append('class_id', classFilter);
    if (yearFilter) params.append('academic_year_id', yearFilter);
    if (searchFilter) params.append('search', searchFilter);
    
    window.location.href = url + params.toString();
}

function clearFilters() {
    document.getElementById('institution_filter').value = '';
    document.getElementById('class_filter').value = '';
    document.getElementById('academic_year_filter').value = '';
    document.getElementById('search_filter').value = '';
    applyFilters();
}

// Auto-apply filters on change
document.getElementById('institution_filter').addEventListener('change', applyFilters);
document.getElementById('class_filter').addEventListener('change', applyFilters);
document.getElementById('academic_year_filter').addEventListener('change', applyFilters);

// Search on Enter key
document.getElementById('search_filter').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Set current filter values from URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('institution_id')) {
        document.getElementById('institution_filter').value = urlParams.get('institution_id');
    }
    if (urlParams.get('class_id')) {
        document.getElementById('class_filter').value = urlParams.get('class_id');
    }
    if (urlParams.get('academic_year_id')) {
        document.getElementById('academic_year_filter').value = urlParams.get('academic_year_id');
    }
    if (urlParams.get('search')) {
        document.getElementById('search_filter').value = urlParams.get('search');
    }
});
</script>
@endsection
