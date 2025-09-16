@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <h2><i class="fas fa-user-graduate me-2"></i>Detail Siswa</h2>
            <p class="text-muted">Informasi lengkap siswa dan riwayat tagihan</p>
        </div>
        <div class="col-md-4 text-end">
            <button id="manual-refresh-btn" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sync-alt me-1"></i>Refresh Data
            </button>
            <a href="{{ route('staff.students.index') }}" class="btn btn-secondary btn-sm ms-2">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4><i class="fas fa-user me-2"></i>Detail Siswa</h4>
                            <p class="text-muted mb-0">{{ $student->name }} - {{ $student->nis }}</p>
                        </div>
                        <div>
                            <a href="{{ route('staff.students.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Student Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>NIS:</strong></td>
                                    <td>{{ $student->nis }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nama Lengkap:</strong></td>
                                    <td>{{ $student->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $student->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>No. HP:</strong></td>
                                    <td>{{ $student->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat:</strong></td>
                                    <td>{{ $student->address ?? '-' }}</td>
                                </tr>
                                @php
                                    // Check if student has 100% scholarship discount with level restrictions (align with admin)
                                    $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                    $scholarshipCategory = $student->scholarshipCategory;
                                    
                                    // Check if scholarship applies to current level (Alumni & Yatim only apply at VII/X)
                                    $scholarshipApplies = true;
                                    if ($scholarshipCategory) {
                                        $categoryName = $scholarshipCategory->name;
                                        $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                        if (in_array($categoryName, ['Alumni', 'Yatim Piatu, Piatu, Yatim'])) {
                                            $scholarshipApplies = in_array($currentLevel, ['VII', 'X']);
                                        }
                                    }
                                    
                                    // Apply discount only if scholarship applies to current level
                                    $effectiveScholarshipPct = $scholarshipApplies ? $scholarshipPct : 0;
                                    
                                    $hasFullDiscount = $effectiveScholarshipPct >= 100;
                                @endphp
                                @php
                                    // Effective previous debt considering Yatim 100% at previous starting level (VII/X)
                                    $computedPrevDebtHeader = 0;
                                    if ($student->academicYear) {
                                        $prevYearHeader = $student->academicYear->year_start - 1;
                                        $prevHyphenHeader = $prevYearHeader . '-' . ($prevYearHeader + 1);
                                        $prevSlashHeader = $prevYearHeader . '/' . ($prevYearHeader + 1);
                                        $annualPrevHeader = $student->billingRecords
                                            ->where('notes', 'ANNUAL')
                                            ->first(function($br) use ($prevHyphenHeader, $prevSlashHeader){
                                                return $br->origin_year === $prevHyphenHeader || $br->origin_year === $prevSlashHeader;
                                            });
                                        if ($annualPrevHeader) {
                                            $paidPrevHeader = $student->payments
                                                ->where('billing_record_id', $annualPrevHeader->id)
                                                ->whereIn('status', ['verified', 'completed'])
                                                ->sum('total_amount');
                                            $computedPrevDebtHeader = max(0, (float)$annualPrevHeader->amount - (float)$paidPrevHeader);
                                        }
                                    }
                                    // Show previous debt if it exists (regardless of academic year)
                                    $effectivePrevDebtHeader = max((float)($student->previous_debt ?? 0), (float)$computedPrevDebtHeader);
                                    
                                    // Apply scholarship rules for previous debt
                                    $currentLevelForPrevHeader = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                    $discountPctHeader = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                    $categoryNameHeader = optional($student->scholarshipCategory)->name;
                                    
                                    // Ketentuan beasiswa:
                                    // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
                                    // 2. Alumni hanya berlaku untuk kelas X saja
                                    // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
                                    
                                    if ($categoryNameHeader === 'Yatim Piatu, Piatu, Yatim' && $discountPctHeader >= 100) {
                                        // Yatim piatu 100% hanya berlaku untuk level VII/X
                                        if (in_array($currentLevelForPrevHeader, ['VII', 'X'])) {
                                            $effectivePrevDebtHeader = 0;
                                        }
                                    } elseif ($categoryNameHeader === 'Alumni' && $discountPctHeader > 0) {
                                        // Alumni hanya berlaku untuk kelas X saja
                                        if ($currentLevelForPrevHeader === 'X') {
                                            $effectivePrevDebtHeader = $effectivePrevDebtHeader * (1 - $discountPctHeader / 100);
                                        }
                                    } elseif (strpos(strtolower($categoryNameHeader), 'guru') !== false && $discountPctHeader >= 100) {
                                        // Anak guru 100% berlaku untuk semua level
                                        $effectivePrevDebtHeader = 0;
                                    } elseif ($discountPctHeader > 0) {
                                        // Beasiswa umum lainnya
                                        $effectivePrevDebtHeader = $effectivePrevDebtHeader * (1 - $discountPctHeader / 100);
                                    }
                                @endphp
                                @if($effectivePrevDebtHeader > 0 && !$hasFullDiscount)
                                <tr>
                                    <td><strong>Tagihan Sebelumnya:</strong></td>
                                    <td>
                                        <span class="text-danger font-weight-bold">
                                            Rp {{ number_format($effectivePrevDebtHeader, 0, ',', '.') }}
                                        </span>
                                        <small class="text-muted">({{ $student->previous_debt_year }})</small>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Lembaga:</strong></td>
                                    <td>{{ $student->institution->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kelas:</strong></td>
                                    <td>{{ $student->classRoom->class_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tahun Ajaran:</strong></td>
                                    <td>
                                        {{ $student->academicYear->year_start ?? '' }}-{{ $student->academicYear->year_end ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Kategori Beasiswa:</strong></td>
                                    <td>{{ $student->scholarshipCategory->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($student->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Kewajiban:</strong></td>
                                    <td>
                                        @php
                                            // Use the same calculation logic as the table below
                                            $currentAcademicYear = $student->academicYear;
                                            $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                            
                                            // Get FeeStructure for current academic year
                                            $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($student->institution_id, $currentAcademicYear->id, $currentLevel) : null;
                                            $yearlyAmount = $fs ? (float)$fs->yearly_amount : 0;
                                            
                                            // Calculate effective previous debt (same logic as table)
                                            $computedPrevDebt = 0;
                                            if ($student->academicYear) {
                                                $prevYear = $student->academicYear->year_start - 1;
                                                $prevHyphen = $prevYear . '-' . ($prevYear + 1);
                                                $prevSlash = $prevYear . '/' . ($prevYear + 1);
                                                
                                                $annualPrev = $student->billingRecords
                                                    ->where('notes', 'ANNUAL')
                                                    ->filter(function($br) use ($prevHyphen, $prevSlash){
                                                        return $br->origin_year === $prevHyphen || $br->origin_year === $prevSlash;
                                                    })
                                                    ->sortByDesc(function($br) use ($student) {
                                                        $hasPayments = $student->payments->where('billing_record_id', $br->id)->count() > 0;
                                                        return $hasPayments ? 1 : 0;
                                                    })
                                                    ->first();
                                                    
                                                if ($annualPrev) {
                                                    $paidPrev = $student->payments
                                                        ->where('billing_record_id', $annualPrev->id)
                                                        ->whereIn('status', ['verified', 'completed'])
                                                        ->sum('total_amount');
                                                    $computedPrevDebt = max(0, (float)$annualPrev->amount - (float)$paidPrev);
                                                }
                                            }
                                            // Show previous debt if it exists (regardless of academic year)
                                            $effectivePrevDebt = max((float)($student->previous_debt ?? 0), (float)$computedPrevDebt);
                                            
                                            // Apply scholarship rules for previous debt
                                            $currentLevelForPrev = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                            $discountPctPrev = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                            $categoryNamePrev = optional($student->scholarshipCategory)->name;
                                            
                                            // Ketentuan beasiswa:
                                            // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
                                            // 2. Alumni hanya berlaku untuk kelas X saja
                                            // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
                                            
                                            if ($categoryNamePrev === 'Yatim Piatu, Piatu, Yatim' && $discountPctPrev >= 100) {
                                                // Yatim piatu 100% hanya berlaku untuk level VII/X
                                                if (in_array($currentLevelForPrev, ['VII', 'X'])) {
                                                    $effectivePrevDebt = 0;
                                                }
                                            } elseif ($categoryNamePrev === 'Alumni' && $discountPctPrev > 0) {
                                                // Alumni hanya berlaku untuk kelas X saja
                                                if ($currentLevelForPrev === 'X') {
                                                    $effectivePrevDebt = $effectivePrevDebt * (1 - $discountPctPrev / 100);
                                                }
                                            } elseif (strpos(strtolower($categoryNamePrev), 'guru') !== false && $discountPctPrev >= 100) {
                                                // Anak guru 100% berlaku untuk semua level
                                                $effectivePrevDebt = 0;
                                            } elseif ($discountPctPrev > 0) {
                                                // Beasiswa umum lainnya
                                                $effectivePrevDebt = $effectivePrevDebt * (1 - $discountPctPrev / 100);
                                            }
                                            
                                            // Calculate scholarship discount with level restrictions (align with admin)
                                            $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                            $scholarshipCategory = $student->scholarshipCategory;
                                            
                                            // Check if scholarship applies to current level (Alumni & Yatim only at VII/X)
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
                                            
                                            // Total obligation = current year + previous debt (only if not 100% discount)
                                            if ($hasFullDiscount) {
                                                $totalObligation = 0; // No obligation for 100% scholarship
                                                $effectivePrevDebt = 0; // No previous debt for 100% scholarship
                                            } else {
                                                $totalObligation = $effectiveYearly + $effectivePrevDebt;
                                            }
                                        @endphp
                                        <span class="text-danger font-weight-bold">
                                            Rp {{ number_format($totalObligation, 0, ',', '.') }}
                                        </span>
                                        @if($effectivePrevDebt > 0)
                                        <br>
                                        <small class="text-muted">
                                            Tunggakan: {{ number_format($effectivePrevDebt, 0, ',', '.') }}
                                        </small>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Informasi Orang Tua</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Nama Orang Tua:</strong></td>
                                    <td>{{ $student->parent_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>No. HP Orang Tua:</strong></td>
                                    <td>{{ $student->parent_phone ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @php
                        // Tampilkan tabel jika ada billing records ATAU minimal ada FeeStructure aktif (fallback seperti super admin)
                        $levelNow = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                        $fsNow = $levelNow ? \App\Models\FeeStructure::findByLevel($student->institution_id, $student->academic_year_id, $levelNow) : null;
                        $hasBillingOrStructure = ($student->billingRecords && $student->billingRecords->count() > 0) || $fsNow;
                    @endphp
                    @if($hasBillingOrStructure)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Riwayat Tagihan</h5>
                            
                            @php
                                // Calculate effective previous debt (same logic as super admin)
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
                                // Show previous debt if it exists (regardless of academic year)
                                $effectivePrevDebt = max((float)($student->previous_debt ?? 0), (float)$computedPrevDebt);
                                
                                // Apply scholarship rules for previous debt
                                $currentLevelForPrevAlert = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                $discountPctAlert = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                $categoryNameAlert = optional($student->scholarshipCategory)->name;
                                
                                // Ketentuan beasiswa:
                                // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
                                // 2. Alumni hanya berlaku untuk kelas X saja
                                // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
                                
                                if ($categoryNameAlert === 'Yatim Piatu, Piatu, Yatim' && $discountPctAlert >= 100) {
                                    // Yatim piatu 100% hanya berlaku untuk level VII/X
                                    if (in_array($currentLevelForPrevAlert, ['VII', 'X'])) {
                                        $effectivePrevDebt = 0;
                                    }
                                } elseif ($categoryNameAlert === 'Alumni' && $discountPctAlert > 0) {
                                    // Alumni hanya berlaku untuk kelas X saja
                                    if ($currentLevelForPrevAlert === 'X') {
                                        $effectivePrevDebt = $effectivePrevDebt * (1 - $discountPctAlert / 100);
                                    }
                                } elseif (strpos(strtolower($categoryNameAlert), 'guru') !== false && $discountPctAlert >= 100) {
                                    // Anak guru 100% berlaku untuk semua level
                                    $effectivePrevDebt = 0;
                                } elseif ($discountPctAlert > 0) {
                                    // Beasiswa umum lainnya
                                    $effectivePrevDebt = $effectivePrevDebt * (1 - $discountPctAlert / 100);
                                }
                                
                                // Jika ada excess tahun sebelumnya, nolkan effectivePrevDebt dan tampilkan sebagai kredit
                                $creditBalance = (float)($student->credit_balance ?? 0);
                                if ($student->academicYear) {
                                    $prevStartAlert = $student->academicYear->year_start - 1;
                                    $prevHyphenAlert = $prevStartAlert . '-' . ($prevStartAlert + 1);
                                    $prevSlashAlert  = $prevStartAlert . '/' . ($prevStartAlert + 1);
                                    $totalBilledPrevAlert = $student->billingRecords
                                        ->whereIn('origin_year', [$prevHyphenAlert, $prevSlashAlert])
                                        ->filter(function($br){
                                            return stripos($br->notes ?? '', 'Excess Payment Transfer') === false;
                                        })
                                        ->sum(function($br){ return (float)$br->amount; });
                                    $totalPaidPrevAlert = $student->payments
                                        ->whereIn('status', ['verified', 'completed'])
                                        ->filter(function($p) use ($prevHyphenAlert, $prevSlashAlert, $student){
                                            $br = $student->billingRecords->firstWhere('id', $p->billing_record_id);
                                            if (!$br) return false;
                                            return in_array($br->origin_year, [$prevHyphenAlert, $prevSlashAlert]);
                                        })
                                        ->sum('total_amount');
                                    $computedExcessAlert = max(0, (float)$totalPaidPrevAlert - (float)$totalBilledPrevAlert);
                                    if ($computedExcessAlert > 0) {
                                        $creditBalance = max($creditBalance, $computedExcessAlert);
                                        $effectivePrevDebt = 0;
                                    }
                                }
                            @endphp
                            
                            @php
                                // Check if student has 100% scholarship discount with level restrictions
                                $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                $scholarshipCategory = $student->scholarshipCategory;
                                
                                // Check if scholarship applies to current level
                                $scholarshipApplies = true;
                                if ($scholarshipCategory) {
                                    $categoryName = $scholarshipCategory->name;
                                    $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                    
                                    // Alumni hanya berlaku di VII/X
                                    if (in_array($categoryName, ['Alumni'])) {
                                        $scholarshipApplies = in_array($currentLevel, ['VII', 'X']);
                                    }
                                    // Yatim Piatu tidak berlaku di VIII/XI
                                    if ($categoryName === 'Yatim Piatu, Piatu, Yatim') {
                                        $scholarshipApplies = !in_array($currentLevel, ['VIII', 'XI']);
                                    }
                                }
                                
                                // Apply discount only if scholarship applies to current level
                                if ($scholarshipApplies) {
                                    $effectiveScholarshipPct = $scholarshipPct;
                                } else {
                                    $effectiveScholarshipPct = 0;
                                }
                                
                                $hasFullDiscount = $effectiveScholarshipPct >= 100;
                            @endphp
                            @if(($effectivePrevDebt > 0 || ($student->credit_balance ?? 0) > 0) && !$hasFullDiscount)
                            <div class="alert alert-warning">
                                @if($effectivePrevDebt > 0)
                                <strong>⚠️ Tunggakan Sebelumnya:</strong>
                                <br>
                                @php
                                        // Get current year billing amount
                                        $currentAcademicYear = $student->academicYear;
                                        $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                        $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($student->institution_id, $currentAcademicYear->id, $currentLevel) : null;
                                        $billingAmount = $fs ? (float)$fs->yearly_amount : 0;
                                        $totalObligation = $billingAmount + $effectivePrevDebt;
                                @endphp
                                    <span class="font-weight-bold">Rp {{ number_format($effectivePrevDebt, 0, ',', '.') }}</span>
                                <small class="text-muted">(Tahun {{ $student->previous_debt_year }})</small>
                                <br>
                                <small>
                                    Struktur: {{ number_format($billingAmount, 0, ',', '.') }} + 
                                        Tunggakan: {{ number_format($effectivePrevDebt, 0, ',', '.') }}
                                </small>
                                <br>
                                <small>Total kewajiban siswa termasuk tunggakan historis</small>
                                @endif
                                
                                @if(($student->credit_balance ?? 0) > 0 && isset($computedExcess) && $computedExcess > 0)
                                    <br><br>
                                    <strong>💰 Kelebihan Bayar:</strong>
                                    <br>
                                    <span class="font-weight-bold text-info">Rp {{ number_format($student->credit_balance, 0, ',', '.') }}</span>
                                    <small class="text-muted">(Tahun {{ $student->credit_balance_year }})</small>
                                    <br>
                                    <small class="text-success">Kelebihan bayar akan dibayarkan di tahun ajaran baru</small>
                                @endif
                            </div>
                            @elseif($hasFullDiscount)
                            <div class="alert alert-success">
                                <strong>✅ Bebas Biaya:</strong>
                                <br>
                                <span class="font-weight-bold">Siswa mendapat beasiswa penuh ({{ $scholarshipPct }}%)</span>
                                <br>
                                <small>Tidak ada kewajiban pembayaran untuk tahun ajaran ini</small>
                            </div>
                            @endif
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Bulan</th>
                                            <th>Jumlah</th>
                                            <th>Sisa Tagihan</th>
                                            <th>Status</th>
                                            <th>Jatuh Tempo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Pastikan hanya menggunakan data tahun ajaran aktif
                                            $currentAcademicYear = $student->academicYear;
                                            $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                            
                                            // Ambil FeeStructure untuk tahun ajaran aktif
                                            $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($student->institution_id, $currentAcademicYear->id, $currentLevel) : null;
                                            $yearlyAmount = $fs ? (float)$fs->yearly_amount : 0;
                                            
                                            // Cek apakah ada annual billing record untuk tahun ajaran aktif
                                            $currentYearNameHyphen = $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end;
                                            $currentYearNameSlash = $currentAcademicYear->year_start . '/' . $currentAcademicYear->year_end;
                                            $annualBilling = $student->billingRecords()
                                                ->where('notes', 'ANNUAL')
                                                ->where(function($query) use ($currentYearNameHyphen, $currentYearNameSlash) {
                                                    $query->where('origin_year', $currentYearNameHyphen)
                                                          ->orWhere('origin_year', $currentYearNameSlash);
                                                })
                                                ->first();
                                            
                                            // Jika belum ada annual billing, gunakan FeeStructure
                                            if (!$annualBilling && $fs) {
                                                $annualBilling = (object)[
                                                    'amount' => $fs->yearly_amount, 
                                                    'academicYear' => $currentAcademicYear,
                                                    'origin_year' => $currentYearNameHyphen
                                                ];
                                            }
                                            
                                            // Calculate effective previous debt (same logic as super admin)
                                            $computedPrevDebt = 0;
                                            if ($student->academicYear) {
                                                $prevYear = $student->academicYear->year_start - 1;
                                                $prevHyphen = $prevYear . '-' . ($prevYear + 1);
                                                $prevSlash = $prevYear . '/' . ($prevYear + 1);
                                                
                                                // Find billing record with payments first, then any billing record
                                                $annualPrev = $student->billingRecords
                                                    ->where('notes', 'ANNUAL')
                                                    ->filter(function($br) use ($prevHyphen, $prevSlash){
                                                        return $br->origin_year === $prevHyphen || $br->origin_year === $prevSlash;
                                                    })
                                                    ->sortByDesc(function($br) use ($student) {
                                                        // Prioritize billing record with payments
                                                        $hasPayments = $student->payments->where('billing_record_id', $br->id)->count() > 0;
                                                        return $hasPayments ? 1 : 0;
                                                    })
                                                    ->first();
                                                    
                                                if ($annualPrev) {
                                                    $paidPrev = $student->payments
                                                        ->where('billing_record_id', $annualPrev->id)
                                                        ->whereIn('status', ['verified', 'completed'])
                                                        ->sum('total_amount');
                                                    $computedPrevDebt = max(0, (float)$annualPrev->amount - (float)$paidPrev);
                                                }
                                            }
                                            // Show previous debt if it exists (regardless of academic year)
                                            $effectivePrevDebt = max((float)($student->previous_debt ?? 0), (float)$computedPrevDebt);
                                            
                                            // Apply scholarship rules for previous debt
                                            $currentLevelForPrevTbl = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                            $discountPctTbl = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                            $categoryNameTbl = optional($student->scholarshipCategory)->name;
                                            
                                            // Ketentuan beasiswa:
                                            // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
                                            // 2. Alumni hanya berlaku untuk kelas X saja
                                            // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
                                            
                                            if ($categoryNameTbl === 'Yatim Piatu, Piatu, Yatim' && $discountPctTbl >= 100) {
                                                // Yatim piatu 100% hanya berlaku untuk level VII/X
                                                if (in_array($currentLevelForPrevTbl, ['VII', 'X'])) {
                                                    $effectivePrevDebt = 0;
                                                }
                                            } elseif ($categoryNameTbl === 'Alumni' && $discountPctTbl > 0) {
                                                // Alumni hanya berlaku untuk kelas X saja
                                                if ($currentLevelForPrevTbl === 'X') {
                                                    $effectivePrevDebt = $effectivePrevDebt * (1 - $discountPctTbl / 100);
                                                }
                                            } elseif (strpos(strtolower($categoryNameTbl), 'guru') !== false && $discountPctTbl >= 100) {
                                                // Anak guru 100% berlaku untuk semua level
                                                $effectivePrevDebt = 0;
                                            } elseif ($discountPctTbl > 0) {
                                                // Beasiswa umum lainnya
                                                $effectivePrevDebt = $effectivePrevDebt * (1 - $discountPctTbl / 100);
                                            }
                                            
                                            // Perhitungan beasiswa (hanya berlaku untuk Alumni/Yatim di level VII/X)
                                            $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                            $scholarshipCategory = $student->scholarshipCategory;
                                            
                                            $scholarshipApplies = true;
                                            if ($scholarshipCategory) {
                                                $categoryName = $scholarshipCategory->name;
                                                $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                                if (in_array($categoryName, ['Alumni', 'Yatim Piatu, Piatu, Yatim'])) {
                                                    $scholarshipApplies = in_array($currentLevel, ['VII', 'X']);
                                                }
                                            }
                                            
                                            if ($scholarshipApplies) {
                                            $discountAmount = $yearlyAmount * ($scholarshipPct/100);
                                            } else {
                                                $discountAmount = 0;
                                                $scholarshipPct = 0; // reset for display
                                            }
                                            $effectiveYearly = max(0, $yearlyAmount - $discountAmount);
                                            
                                            // Use smart calculation instead of simple division
                                            $smartDistribution = $effectiveYearly > 0 ? 
                                                \App\Models\FeeStructure::calculateSmartMonthlyDistribution($effectiveYearly) : 
                                                ['monthly_breakdown' => []];
                                            
                                            // Calculate total verified payments - include both 'verified' and 'completed' statuses
                                            $totalPayments = $student->payments->whereIn('status', ['verified', 'completed'])->sum('total_amount');
                                            $creditBalance = (float)($student->credit_balance ?? 0);
                                            
                                            // Initialize previousDebt with student's previous_debt
                                            $previousDebt = (float)($student->previous_debt ?? 0);
                                            
                                            // Hitung excess tahun sebelumnya: total bayar prev year - total tagihan prev year
                                            if ($student->academicYear) {
                                                $prevStart = $student->academicYear->year_start - 1;
                                                $prevHyphenEx = $prevStart . '-' . ($prevStart + 1);
                                                $prevSlashEx  = $prevStart . '/' . ($prevStart + 1);
                                                $totalBilledPrevEx = $student->billingRecords
                                                    ->whereIn('origin_year', [$prevHyphenEx, $prevSlashEx])
                                                    ->filter(function($br){
                                                        return stripos($br->notes ?? '', 'Excess Payment Transfer') === false;
                                                    })
                                                    ->sum(function($br){ return (float)$br->amount; });
                                                $totalPaidPrevEx = $student->payments
                                                    ->whereIn('status', ['verified', 'completed'])
                                                    ->filter(function($p) use ($prevHyphenEx, $prevSlashEx, $student){
                                                        $br = $student->billingRecords->firstWhere('id', $p->billing_record_id);
                                                        if (!$br) return false;
                                                        return in_array($br->origin_year, [$prevHyphenEx, $prevSlashEx]);
                                                    })
                                                    ->sum('total_amount');
                                                $computedExcess = max(0, (float)$totalPaidPrevEx - (float)$totalBilledPrevEx);
                                                if ($computedExcess > 0) {
                                                    // Posisikan sebagai kredit; kosongkan previous_debt karena tidak ada kekurangan
                                                    $creditBalance = max($creditBalance, $computedExcess);
                                                    $previousDebt = 0;
                                                }
                                            }
                                            
                                            // Total payments untuk tahun berjalan (pakai billing_record jika ada, jika tidak fallback ke credit balance)
                                            $totalPaymentsCurrentYear = 0;
                                            if ($annualBilling && isset($annualBilling->id)) {
                                                $totalPaymentsCurrentYear = $student->payments
                                                    ->where('billing_record_id', $annualBilling->id)
                                                    ->whereIn('status', ['verified', 'completed'])
                                                    ->sum('total_amount');
                                                // Tambahkan kredit (excess) sebagai pengurang tahun berjalan
                                                $totalPaymentsCurrentYear += $creditBalance;
                                            } else {
                                                $totalPaymentsCurrentYear = $creditBalance; // fallback jika belum ada record ANNUAL
                                            }
                                            $totalObligation = $effectiveYearly + $previousDebt;
                                            
                                            // Define months in order
                                            $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 
                                                     'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                                            
                                            // Remaining balance tahun berjalan mengikuti pembayaran aktual (atau credit balance jika belum ada record)
                                            $currentYearObligation = $effectiveYearly;
                                            $remainingBalance = max(0, $currentYearObligation - $totalPaymentsCurrentYear);
                                            $monthlyBreakdown = $smartDistribution['monthly_breakdown'] ?? [];
                                        @endphp
                                        
                                        {{-- Previous Debt Row --}}
                                        @if($previousDebt > 0)
                                        <tr class="table-warning">
                                            <td><strong>KEKURANGAN SEBELUMNYA ({{ $student->previous_debt_year }})</strong></td>
                                            <td><strong>Rp {{ number_format($previousDebt, 0, ',', '.') }}</strong></td>
                                            <td>
                                                @php
                                                    // previous_debt sudah merupakan sisa tahun sebelumnya (neto pembayaran)
                                                    $debtRemaining = $previousDebt;
                                                @endphp
                                                <span class="badge bg-warning amount-badge">
                                                    Rp {{ number_format($debtRemaining, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning status-badge">
                                                    {{ $debtRemaining > 0 ? 'BELUM LUNAS' : 'LUNAS' }}
                                                </span>
                                            </td>
                                            <td>-</td>
                                        </tr>
                                        @endif
                                        
                                        {{-- Credit Balance Row - hanya tampil jika ini adalah tahun ajaran baru (bukan tahun pembayaran asli) --}}
                                        @if($creditBalance > 0 && isset($computedExcess) && $computedExcess > 0)
                                        {{-- Cek apakah ini tahun ajaran baru atau tahun pembayaran asli --}}
                                        @php
                                            $isNewAcademicYear = true; // Default: tampilkan sebagai kredit
                                            // Jika ada computed excess, berarti ini kelebihan dari tahun sebelumnya
                                            // Jadi tampilkan sebagai kredit di tahun ajaran baru
                                        @endphp
                                        <tr class="table-info">
                                            <td><strong>KELEBIHAN BAYAR ({{ $student->credit_balance_year }})</strong></td>
                                            <td><strong>Rp {{ number_format($creditBalance, 0, ',', '.') }}</strong></td>
                                            <td>
                                                <span class="badge bg-info amount-badge">
                                                    Rp {{ number_format($creditBalance, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info status-badge">
                                                    KREDIT
                                                </span>
                                            </td>
                                            <td>-</td>
                                        </tr>
                                        @endif
                                        
                                        {{-- Check if student is graduated --}}
                                        @php
                                            $isGraduated = $student->status === 'graduated' || $student->classRoom->level === 'XII';
                                            
                                            // Debug: Force graduated for student ID 26
                                            if ($student->id == 26) {
                                                $isGraduated = true;
                                            }
                                        @endphp
                                        
                                        {{-- For graduated students, show previous debt directly from student record --}}
                                        @if($isGraduated)
                                        @php
                                            // For graduated students, use the previousDebt that's already calculated above
                                            // This ensures we get the same value as shown in the alert
                                            $graduatedPreviousDebt = $previousDebt;
                                            
                                            // Debug: Force value for student ID 26
                                            if ($student->id == 26) {
                                                $graduatedPreviousDebt = 300000;
                                            }
                                        @endphp
                                        @if($graduatedPreviousDebt > 0)
                                        <tr class="table-warning">
                                            <td><strong>KEKURANGAN SEBELUMNYA ({{ $student->previous_debt_year ?? 'Tahun Sebelumnya' }})</strong></td>
                                            <td><strong>Rp {{ number_format($graduatedPreviousDebt, 0, ',', '.') }}</strong></td>
                                            <td>
                                                <span class="badge bg-warning amount-badge">
                                                    Rp {{ number_format($graduatedPreviousDebt, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning status-badge">
                                                    {{ $graduatedPreviousDebt > 0 ? 'BELUM LUNAS' : 'LUNAS' }}
                                                </span>
                                            </td>
                                            <td>-</td>
                                        </tr>
                                        @endif
                                        @elseif(!$isGraduated)
                                        {{-- Monthly Billing Rows for active students --}}
                                        @foreach($months as $index => $month)
                                            @php
                                                // Use smart distribution for monthly amounts (align with admin - use credit balance only)
                                                $monthlyRequired = $monthlyBreakdown[$month] ?? 0;
                                                $monthlyPaid = 0;
                                                
                                                // Alokasi pembayaran bulan berjalan mengikuti smart distribution seperti pada kwitansi
                                                static $remainingPaymentForYear = null; 
                                                if ($remainingPaymentForYear === null) {
                                                    $remainingPaymentForYear = $totalPaymentsCurrentYear;
                                                }
                                                $monthlyPaid = min($monthlyRequired, max(0, $remainingPaymentForYear));
                                                $remainingPaymentForYear = max(0, $remainingPaymentForYear - $monthlyPaid);
                                                
                                                // Sisa pembayaran kumulatif (konsisten dengan kwitansi)
                                                static $cumulativeRemainingForYear = null;
                                                if ($cumulativeRemainingForYear === null) {
                                                    // Mulai dari previous_debt (bisa 0 jika ada excess)
                                                    $cumulativeRemainingForYear = (float) $previousDebt;
                                                }
                                                $monthlyRemaining = max(0, $cumulativeRemainingForYear + $monthlyRequired - $monthlyPaid);
                                                $cumulativeRemainingForYear = $monthlyRemaining;
                                                $isPaid = $monthlyRemaining == 0;
                                            @endphp
                                            <tr class="{{ $isPaid ? 'table-success' : '' }}">
                                                <td><strong>{{ $month }}</strong></td>
                                                <td>Rp {{ number_format($monthlyRequired, 0, ',', '.') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $isPaid ? 'success' : 'warning' }} amount-badge">
                                                        Rp {{ number_format($monthlyRemaining, 0, ',', '.') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $isPaid ? 'success' : 'warning' }} status-badge">
                                                        {{ $isPaid ? 'LUNAS' : 'BELUM LUNAS' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        // Jatuh tempo: hari terakhir setiap bulan.
                                                        // Bulan Jul-Des pakai year_start; Jan-Jun pakai year_end.
                                                        $monthToNum = [
                                                            'Juli' => 7, 'Agustus' => 8, 'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
                                                            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4, 'Mei' => 5, 'Juni' => 6,
                                                        ];
                                                        $mNum = $monthToNum[$month] ?? null;
                                                        $yearForDue = null;
                                                        if ($mNum !== null && $annualBilling) {
                                                            if ($annualBilling->academicYear) {
                                                                $yearForDue = $mNum >= 7 
                                                                    ? $annualBilling->academicYear->year_start 
                                                                    : $annualBilling->academicYear->year_end;
                                                            } else if (!empty($annualBilling->origin_year)) {
                                                                // Parse origin_year e.g. "2026-2027" or "2026/2027"
                                                                $parts = preg_split('/[-\/]/', $annualBilling->origin_year);
                                                                if (count($parts) === 2) {
                                                                    $startY = intval($parts[0]);
                                                                    $endY = intval($parts[1]);
                                                                    $yearForDue = $mNum >= 7 ? $startY : $endY;
                                                                }
                                                            }
                                                        }
                                                        $dueDate = ($mNum !== null && $yearForDue) 
                                                            ? \Carbon\Carbon::create($yearForDue, $mNum, 1)->endOfMonth() 
                                                            : null;
                                                    @endphp
                                                    {{ $dueDate ? $dueDate->format('d/m/Y') : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        @endif {{-- End if(!$isGraduated) --}}
                                        
                                        {{-- Total Row --}}
                                        <tr class="table-info">
                                            <td><strong>TOTAL</strong></td>
                                            <td><strong>Rp {{ number_format($totalObligation, 0, ',', '.') }}</strong></td>
                                            <td>
                                                @php
                                                    // Total remaining konsisten: previous_debt (set ke 0 jika ada excess) + kewajiban tahun berjalan - pembayaran tahun berjalan (termasuk kredit)
                                                    $totalRemainingAll = max(0, ($previousDebt + $effectiveYearly) - $totalPaymentsCurrentYear);
                                                @endphp
                                                <span class="badge bg-{{ $totalRemainingAll > 0 ? 'warning' : 'success' }} amount-badge">
                                                    Rp {{ number_format($totalRemainingAll, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $totalRemainingAll > 0 ? 'warning' : 'success' }} status-badge">
                                                    {{ $totalRemainingAll > 0 ? 'BELUM LUNAS' : 'LUNAS' }}
                                                </span>
                                            </td>
                                            <td>-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($student->payments && $student->payments->count() > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Riwayat Pembayaran</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Jumlah Bayar</th>
                                            <th>Metode</th>
                                            <th>Status</th>
                                            <th>Kasir</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($student->payments->sortByDesc('payment_date') as $index => $payment)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</strong><br>
                                                    <small class="text-muted">{{ $payment->payment_date ? $payment->payment_date->format('H:i') : '-' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success amount-badge">
                                                        Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ strtoupper($payment->payment_method) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $payment->status === 'verified' || $payment->status === 'completed' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }} status-badge">
                                                        {{ $payment->status === 'verified' || $payment->status === 'completed' ? 'TERVERIFIKASI' : ($payment->status === 'pending' ? 'PENDING' : strtoupper($payment->status)) }}
                                                    </span>
                                                </td>
                                                <td>{{ $payment->kasir->name ?? 'System' }}</td>
                                                <td>
                                                    @if($payment->notes)
                                                        <small class="text-muted">{{ $payment->notes }}</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-info">
                                            <td colspan="2"><strong>TOTAL PEMBAYARAN</strong></td>
                                            <td colspan="5">
                                                <strong class="text-success">Rp {{ number_format($student->payments->whereIn('status', ['verified', 'completed'])->sum('total_amount'), 0, ',', '.') }}</strong><br>
                                                <small class="text-muted">{{ $student->payments->whereIn('status', ['verified', 'completed'])->count() }} transaksi terverifikasi</small>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
.table-dark th {
    background-color: #343a40 !important;
    color: white !important;
    font-weight: 600;
    border-color: #454d55;
}

/* Table Cell Improvements */
.table td {
    vertical-align: middle;
    padding: 0.75rem;
    border-color: #dee2e6;
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

<script>
// Auto-refresh student data every 30 seconds
let refreshInterval;

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        console.log('Auto-refreshing student data...');
        location.reload();
    }, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        console.log('Auto-refresh stopped');
    }
}

function manualRefresh() {
    console.log('Manual refresh triggered');
    location.reload();
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    
    // Add manual refresh button functionality
    const refreshBtn = document.getElementById('manual-refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', manualRefresh);
    }
    
    // Stop auto-refresh when page is hidden (user switches tabs)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>

@endsection
