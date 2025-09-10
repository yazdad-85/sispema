@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Detail Siswa</h3>
                    <div>
                        <a href="{{ route('students.print', $student->id) }}" target="_blank" class="btn btn-success">
                            <i class="fas fa-print"></i> Print PDF
                        </a>
                        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-warning ms-1">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('students.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
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
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Institusi:</strong></td>
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
                                    <td>
                                        {{ $student->scholarshipCategory->name ?? '-' }}
                                        @if(optional($student->scholarshipCategory)->discount_percentage)
                                            <small class="text-muted">(Diskon {{ (float)$student->scholarshipCategory->discount_percentage }}%)</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'danger' }} text-dark">
                                            {{ ucfirst($student->status) }}
                                        </span>
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
                        $levelNow = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                        $fsNow = $levelNow ? \App\Models\FeeStructure::findByLevel($student->institution_id, $student->academic_year_id, $levelNow) : null;
                        $hasBillingOrStructure = ($student->billingRecords && $student->billingRecords->count() > 0) || $fsNow;
                    @endphp
                    @if($hasBillingOrStructure)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Riwayat Tagihan</h5>
                            
                            @php
                                // Fallback hitung tunggakan sebelumnya jika field previous_debt belum terisi saat promosi
                                $computedPrevDebt = 0;
                                $prevYearStart = optional($student->academicYear)->year_start ? (optional($student->academicYear)->year_start - 1) : null;
                                $prevYearEnd = optional($student->academicYear)->year_end ? (optional($student->academicYear)->year_end - 1) : null;
                                if ($prevYearStart && $prevYearEnd) {
                                    $prevHyphen = $prevYearStart.'-'.$prevYearEnd;
                                    $prevSlash = $prevYearStart.'/'.$prevYearEnd;
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
                                $effectivePrevDebt = max((float)($student->previous_debt ?? 0), (float)$computedPrevDebt);

                                // Jika tahun sebelumnya adalah level awal (VII atau X):
                                // - Yatim 100%: tunggakan sebelumnya = 0
                                // - Alumni X%: tunggakan sebelumnya dikurangi X%
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
                                        $effectivePrevDebt = 0;
                                    } elseif ($isAlumniCategory) {
                                        $effectivePrevDebt = max(0, $effectivePrevDebt * (1 - ($discountPctPrev/100)));
                                    }
                                }
                            @endphp
                            @php
                                // Check if student has 100% scholarship discount with level restrictions
                                $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                $scholarshipCategory = $student->scholarshipCategory;
                                
                                // Check if scholarship applies to current level (diskon hanya untuk level VII/X)
                                $scholarshipApplies = true;
                                if ($scholarshipCategory) {
                                    $categoryName = $scholarshipCategory->name;
                                    $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                    // Alumni dan Yatim Piatu hanya berlaku di level awal (VII/X)
                                    if (in_array($categoryName, ['Alumni', 'Yatim Piatu, Piatu, Yatim'])) {
                                        $scholarshipApplies = in_array($currentLevel, ['VII','X']);
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
                                        // Calculate total obligation using real-time data
                                        $billingRecord = $student->billingRecords->where('notes', 'ANNUAL')->first();
                                        
                                        // Check if student is in graduated class - if so, structure amount should be 0
                                        $isGraduated = optional($student->classRoom)->is_graduated_class ?? false;
                                        
                                        if ($isGraduated) {
                                            // For graduated students, structure amount is 0 (same as staff view)
                                            $billingAmount = 0;
                                        } else {
                                            // For active students, use FeeStructure amount
                                            $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                            $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($student->institution_id, $student->academic_year_id, $currentLevel) : null;
                                            $billingAmount = $fs ? (float)$fs->yearly_amount : ($billingRecord ? (float)$billingRecord->amount : 0);
                                        }
                                        
                                        $totalObligation = $billingAmount + $effectivePrevDebt;
                                        
                                        // Get real-time payment data - include both 'verified' and 'completed' statuses
                                        $totalPayments = $student->payments->whereIn('status', ['verified', 'completed'])->sum('total_amount');
                                        $remainingBalance = max(0, $totalObligation - $totalPayments);
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
                                @elseif(($student->credit_balance ?? 0) > 0)
                                    <strong>⚠️ Kelebihan bayar Sebelumnya:</strong>
                                    <br>
                                    @php
                                        // Check if student is in graduated class - if so, structure amount should be 0
                                        $isGraduated = optional($student->classRoom)->is_graduated_class ?? false;
                                        
                                        if ($isGraduated) {
                                            // For graduated students, structure amount is 0 (same as staff view)
                                            $billingAmount = 0;
                                        } else {
                                            // For active students, use FeeStructure amount
                                            $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                            $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($student->institution_id, $student->academic_year_id, $currentLevel) : null;
                                            $billingAmount = $fs ? (float)$fs->yearly_amount : 0;
                                        }
                                        
                                        $creditBalance = (float)($student->credit_balance ?? 0);
                                        $remainingBalance = max(0, $billingAmount - $creditBalance);
                                    @endphp
                                    <span class="font-weight-bold">Rp {{ number_format($creditBalance, 0, ',', '.') }}</span>
                                    <small class="text-muted">(Tahun {{ $student->credit_balance_year }})</small>
                                    <br>
                                    <small>
                                        Struktur: {{ number_format($billingAmount, 0, ',', '.') }} + 
                                        Tunggakan: {{ number_format($effectivePrevDebt, 0, ',', '.') }}
                                    </small>
                                    <br>
                                    <small>Total kewajiban siswa termasuk tunggakan historis</small>
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
                                            // Pastikan hanya menggunakan data tahun ajaran aktif (2026/2027)
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
                                            
                                            // Calculate scholarship discount with level restrictions
                                            $scholarshipPct = (float)(optional($student->scholarshipCategory)->discount_percentage ?? 0);
                                            $scholarshipCategory = $student->scholarshipCategory;
                                            
                                            // Check if scholarship applies to current level
                                            $scholarshipApplies = true;
                                            if ($scholarshipCategory) {
                                                $categoryName = $scholarshipCategory->name;
                                                $currentLevel = optional($student->classRoom)->safe_level ?? optional($student->classRoom)->level;
                                                
                                                // Alumni and Yatim Piatu discounts only apply to VII and X levels
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
                                            
                                            // Use smart calculation instead of simple division
                                            $smartDistribution = $effectiveYearly > 0 ? 
                                                \App\Models\FeeStructure::calculateSmartMonthlyDistribution($effectiveYearly) : 
                                                ['monthly_breakdown' => []];
                                            
                                            // Calculate total verified payments - include both 'verified' and 'completed' statuses
                                            $totalPayments = $student->payments->whereIn('status', ['verified', 'completed'])->sum('total_amount');
                                            $creditBalance = (float)($student->credit_balance ?? 0);
                                            
                                            // Calculate previous debt - use same logic as alert
                                            $previousDebt = $effectivePrevDebt; // Use calculated value from alert
                                            
                                            // Check if student has 100% scholarship discount
                                            $hasFullDiscount = $scholarshipPct >= 100;
                                            
                                            // Total obligation = current year + previous debt (only if not 100% discount)
                                            if ($hasFullDiscount) {
                                                $totalObligation = 0; // No obligation for 100% scholarship
                                                $previousDebt = 0; // No previous debt for 100% scholarship
                                            } else {
                                                $totalObligation = $effectiveYearly + $previousDebt;
                                            }
                                            
                                            // Define months in order
                                            $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 
                                                     'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                                            
                                            // Calculate remaining balance - only for current year obligation
                                            // Credit balance reduces current year's obligation
                                            $currentYearObligation = $effectiveYearly;
                                            $remainingBalance = max(0, $currentYearObligation - $creditBalance);
                                            $monthlyBreakdown = $smartDistribution['monthly_breakdown'] ?? [];
                                        @endphp
                                        
                                        {{-- Previous Debt Row --}}
                                        @if($previousDebt > 0 && !$hasFullDiscount)
                                        <tr class="table-warning">
                                            <td><strong>KEKURANGAN SEBELUMNYA ({{ $student->previous_debt_year }})</strong></td>
                                            <td><strong>Rp {{ number_format($previousDebt, 0, ',', '.') }}</strong></td>
                                            <td>
                                                @php
                                                    $debtPaid = min($totalPayments, $previousDebt);
                                                    $debtRemaining = max(0, $previousDebt - $totalPayments);
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
                                        
                                        {{-- Credit Balance Row --}}
                                        @if($creditBalance > 0)
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
                                        
                                        {{-- Monthly Billing Rows --}}
                                        @foreach($months as $index => $month)
                                            @php
                                                // Get monthly amount from smart distribution
                                                $monthlyRequired = $monthlyBreakdown[$month] ?? 0;
                                                $monthlyPaid = 0;
                                                
                                                // Calculate payment allocation for this month
                                                // For current year, only use credit balance (not total payments)
                                                $availableForCurrentYear = $creditBalance;
                                                
                                                // Calculate cumulative required amount up to this month
                                                $cumulativeRequired = 0;
                                                for ($i = 0; $i <= $index; $i++) {
                                                    $cumulativeRequired += $monthlyBreakdown[$months[$i]] ?? 0;
                                                }
                                                
                                                // Calculate how much has been paid for previous months
                                                $previousMonthsPaid = 0;
                                                for ($i = 0; $i < $index; $i++) {
                                                    $previousMonthsPaid += $monthlyBreakdown[$months[$i]] ?? 0;
                                                }
                                                
                                                // Calculate payment for this month using credit balance
                                                $availableForThisMonth = max(0, $availableForCurrentYear - $previousMonthsPaid);
                                                $monthlyPaid = min($monthlyRequired, $availableForThisMonth);
                                                
                                                $monthlyRemaining = max(0, $monthlyRequired - $monthlyPaid);
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
                                        
                                        {{-- Total Row --}}
                                        <tr class="table-info">
                                            <td><strong>TOTAL</strong></td>
                                            <td><strong>Rp {{ number_format($totalObligation, 0, ',', '.') }}</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $remainingBalance > 0 ? 'warning' : 'success' }} amount-badge">
                                                    Rp {{ number_format($remainingBalance, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $remainingBalance > 0 ? 'warning' : 'success' }} status-badge">
                                                    {{ $remainingBalance > 0 ? 'BELUM LUNAS' : 'LUNAS' }}
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
                                                    <strong>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $payment->payment_date ? $payment->payment_date->format('H:i') : '-' }}
                                                    </small>
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
                                        
                                        {{-- Summary Row --}}
                                        <tr class="table-info">
                                            <td colspan="2"><strong>TOTAL PEMBAYARAN</strong></td>
                                            <td colspan="5">
                                                <strong class="text-success">
                                                    Rp {{ number_format($student->payments->whereIn('status', ['verified', 'completed'])->sum('total_amount'), 0, ',', '.') }}
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $student->payments->whereIn('status', ['verified', 'completed'])->count() }} transaksi terverifikasi
                                                </small>
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
// Real-time data synchronization
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh data every 30 seconds
    setInterval(function() {
        refreshStudentData();
    }, 30000);
    
    // Refresh button functionality
    const refreshBtn = document.getElementById('refreshData');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            refreshStudentData();
        });
    }
});

function refreshStudentData() {
    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    
    // Reload page to get fresh data
    window.location.reload();
}

// Real-time calculation functions
function calculateRemainingBalance(totalObligation, totalPayments) {
    return Math.max(0, totalObligation - totalPayments);
}

function updateBillingStatus(monthlyData, totalPayments, previousDebt) {
    // Update billing status based on real-time payment data
    Object.keys(monthlyData).forEach(month => {
        const monthData = monthlyData[month];
        const isPaid = monthData.remaining === 0;
        
        // Update UI elements if they exist
        const statusElement = document.querySelector(`[data-month="${month}"] .status-badge`);
        if (statusElement) {
            statusElement.className = `badge bg-${isPaid ? 'success' : 'warning'}`;
            statusElement.textContent = isPaid ? 'LUNAS' : 'BELUM LUNAS';
        }
    });
}

// Export functions for external use
window.StudentBillingHelper = {
    calculateRemainingBalance,
    updateBillingStatus,
    refreshStudentData
};
</script>

@endsection
