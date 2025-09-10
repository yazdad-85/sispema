<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran - {{ $payment->student->name ?? 'Siswa' }}</title>
    <style>
        @media print {
            @page {
                size: 16cm 10.5cm landscape;
                margin: 0.5cm;
            }
            body {
                width: 15cm;
                height: 9.5cm;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            margin: 0;
            padding: 10px;
            background: white;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 15cm;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            line-height: 1.1;
        }
        
        .receipt-number {
            font-size: 10px;
            font-weight: bold;
        }
        
        .student-info {
            margin-bottom: 10px;
            padding: 6px;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }
        
        .student-info table {
            width: 100%;
            font-size: 8px;
        }
        
        .student-info td {
            padding: 1px 3px;
            vertical-align: top;
        }
        
        .student-info .label {
            font-weight: bold;
            width: 30%;
        }
        
        .content-row {
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
        }
        
        .left-section {
            flex: 1;
        }
        
        .right-section {
            flex: 1;
        }
        
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            background: #e0e0e0;
            padding: 4px;
            margin-bottom: 6px;
            border: 1px solid #999;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            margin-bottom: 8px;
        }
        
        .payment-table th,
        .payment-table td {
            border: 1px solid #999;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
        }
        
        .payment-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 7px;
        }
        
        .payment-table .month {
            text-align: left;
            font-weight: bold;
        }
        
        .payment-table .amount {
            text-align: right;
        }
        
        .payment-table .status {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            background: #e8f4fd;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            margin-bottom: 8px;
        }
        
        .transaction-table th,
        .transaction-table td {
            border: 1px solid #999;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
        }
        
        .transaction-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 7px;
        }
        
        .transaction-table .no {
            width: 10%;
        }
        
        .transaction-table .date {
            width: 20%;
        }
        
        .transaction-table .amount {
            width: 25%;
            text-align: right;
        }
        
        .transaction-table .status {
            width: 20%;
            text-align: center;
        }
        
        .transaction-table .notes {
            width: 25%;
            text-align: left;
        }
        
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 8px;
        }
        
        .signature-section {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }
        
        .signature-box {
            text-align: center;
            flex: 0 1 auto;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 3px;
            font-weight: bold;
            font-size: 9px;
            margin-top: 20px;
        }
        
        .signature-role {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        .amount-highlight {
            background: #fff3cd;
            padding: 3px;
            border: 1px solid #ffeaa7;
            border-radius: 3px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin: 6px 0;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 6px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">
        <i class="fas fa-print"></i> Cetak Kwitansi
    </button>
    <a href="{{ url()->previous() }}" class="no-print" style="position: fixed; top: 20px; right: 170px; z-index: 1000; background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px;">
        ← Kembali
    </a>

    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <h1>BUKTI PEMBAYARAN BIAYA PENDIDIKAN<br>(MTS / MA / SMP / SMA / SMK) YASMU</h1>
            <div class="receipt-number">
                Kuitansi No.: {{ $payment->receipt_number ?? 'N/A' }}<br>
                Kelas: {{ $payment->student->classRoom->class_name ?? '-' }}<br>
                No. Induk: {{ $payment->student->nis ?? '-' }}
            </div>
        </div>

        <!-- Student Info -->
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">Telah terima dari:</td>
                    <td><strong>{{ $payment->student->name ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Uang sejumlah:</td>
                    <td>
                        <div class="amount-highlight">
                            Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="label">Untuk pembayaran:</td>
                    <td>Biaya pendidikan</td>
                </tr>
            </table>
        </div>

        <!-- Content Row -->
        <div class="content-row">
            <!-- Left Section: Rekap Pembayaran -->
            <div class="left-section">
                <div class="section-title">REKAP PEMBAYARAN</div>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>BULAN</th>
                            <th>WAJIB BAYAR</th>
                            <th>JUMLAH BAYAR</th>
                            <th>SISA PEMBAYARAN</th>
                            <th>KET</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Ambil struktur biaya tahun berjalan seperti di halaman detail
                            $currentAcademicYear = $payment->student->academicYear;
                            $currentLevel = optional($payment->student->classRoom)->safe_level ?? optional($payment->student->classRoom)->level;
                            $fs = $currentLevel ? \App\Models\FeeStructure::findByLevel($payment->student->institution_id, optional($currentAcademicYear)->id, $currentLevel) : null;
                            $baseYearlyAmount = $fs ? (float)$fs->yearly_amount : (float)($payment->billingRecord->amount ?? 0);

                            // Terapkan aturan beasiswa sama seperti di detail
                            $scholarshipPct = (float)(optional($payment->student->scholarshipCategory)->discount_percentage ?? 0);
                            $scholarshipCategory = $payment->student->scholarshipCategory;
                            $scholarshipApplies = true;
                            if ($scholarshipCategory) {
                                $categoryName = $scholarshipCategory->name;
                                $currentLevelNow = $currentLevel;
                                if (in_array($categoryName, ['Alumni', 'Yatim Piatu, Piatu, Yatim'])) {
                                    $scholarshipApplies = in_array($currentLevelNow, ['VII', 'X']);
                                }
                            }
                            if ($scholarshipApplies) {
                                $discountAmount = $baseYearlyAmount * ($scholarshipPct/100);
                            } else {
                                $discountAmount = 0;
                                $scholarshipPct = 0; // reset untuk konsistensi tampilan jika dibutuhkan
                            }
                            $effectiveYearly = max(0, $baseYearlyAmount - $discountAmount);

                            $months = ['JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI'];
                            
                            // Gunakan smart distribution berdasarkan effectiveYearly agar sama dengan detail
                            $smart = \App\Models\FeeStructure::calculateSmartMonthlyDistribution($effectiveYearly);
                            $breakdown = $smart['monthly_breakdown'] ?? [];
                            $mapReceiptToBreakdown = [
                                'JULI' => 'Juli', 'AGUSTUS' => 'Agustus', 'SEPTEMBER' => 'September', 'OKTOBER' => 'Oktober',
                                'NOVEMBER' => 'November', 'DESEMBER' => 'Desember', 'JANUARI' => 'Januari', 'FEBRUARI' => 'Februari',
                                'MARET' => 'Maret', 'APRIL' => 'April', 'MEI' => 'Mei', 'JUNI' => 'Juni',
                            ];
                            
                            // Get total payments for this billing record
                            $totalPayments = \App\Models\Payment::where('student_id', $payment->student_id)
                                ->where('billing_record_id', $payment->billing_record_id)
                                ->sum('total_amount');
                            
                            // Get previous debt from student record with scholarship adjustments for starting level (VII/X)
                            $previousDebt = $payment->student->previous_debt ?? 0;
                            $previousDebtYear = $payment->student->previous_debt_year ?? '';
                            
                            // Determine previous level from current level (VIII->VII, IX->VIII, XI->X, XII->XI)
                            $currentLevelForPrev = optional($payment->student->classRoom)->safe_level ?? optional($payment->student->classRoom)->level;
                            $previousLevel = $currentLevelForPrev;
                            if ($currentLevelForPrev === 'VIII') { $previousLevel = 'VII'; }
                            elseif ($currentLevelForPrev === 'IX') { $previousLevel = 'VIII'; }
                            elseif ($currentLevelForPrev === 'XI') { $previousLevel = 'X'; }
                            elseif ($currentLevelForPrev === 'XII') { $previousLevel = 'XI'; }
                            
                            $discountPctPrev = (float)(optional($payment->student->scholarshipCategory)->discount_percentage ?? 0);
                            $categoryNamePrev = optional($payment->student->scholarshipCategory)->name;
                            $isYatimPrev = $categoryNamePrev === 'Yatim Piatu, Piatu, Yatim' && $discountPctPrev >= 100;
                            $isAlumniPrev = $categoryNamePrev === 'Alumni' && $discountPctPrev > 0;
                            if (in_array($previousLevel, ['VII','X'])) {
                                if ($isYatimPrev) {
                                    $previousDebt = 0;
                                } elseif ($isAlumniPrev) {
                                    $previousDebt = max(0, $previousDebt * (1 - ($discountPctPrev/100)));
                                }
                            }
                            
                            // Hitung alokasi pembayaran per bulan menggunakan nominal smart distribution
                            $monthlyData = [];
                            $remainingPayment = $totalPayments;
                            $cumulativeRemaining = 0; // Track cumulative remaining balance
                            
                            // First, allocate to previous debt
                            if ($previousDebt > 0) {
                                $paymentForPreviousDebt = min($remainingPayment, $previousDebt);
                                $remainingPayment -= $paymentForPreviousDebt;
                                $cumulativeRemaining = $previousDebt - $paymentForPreviousDebt;
                            }
                            
                            // Then allocate to monthly payments
                            foreach ($months as $index => $month) {
                                $key = $mapReceiptToBreakdown[$month] ?? null;
                                $monthlyRequired = $key ? (float)($breakdown[$key] ?? 0) : 0;
                                $monthlyPaid = 0;
                                
                                if ($remainingPayment > 0) {
                                    $paymentForThisMonth = min($remainingPayment, $monthlyRequired);
                                    $monthlyPaid = $paymentForThisMonth;
                                    $remainingPayment -= $paymentForThisMonth;
                                }
                                
                                // Calculate remaining for this month (cumulative)
                                $monthlyRemaining = $cumulativeRemaining + $monthlyRequired - $monthlyPaid;
                                $cumulativeRemaining = $monthlyRemaining; // Update for next month
                                
                                $monthlyData[$month] = [
                                    'required' => $monthlyRequired,
                                    'paid' => $monthlyPaid,
                                    'remaining' => $monthlyRemaining,
                                    'isPaid' => $monthlyRemaining == 0
                                ];
                            }
                        @endphp
                        
                        @if($previousDebt > 0)
                        <!-- Kekurangan Sebelumnya -->
                        @php
                            $paymentForPreviousDebt = min($totalPayments, $previousDebt);
                            $previousDebtRemaining = $previousDebt - $paymentForPreviousDebt;
                        @endphp
                        <tr>
                            <td class="month">KEKURANGAN SEBELUMNYA ({{ $previousDebtYear }})</td>
                            <td class="amount">{{ number_format($previousDebt, 0, ',', '.') }}</td>
                            <td class="amount">
                                @if($paymentForPreviousDebt > 0)
                                    {{ number_format($paymentForPreviousDebt, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="amount">{{ number_format($previousDebtRemaining, 0, ',', '.') }}</td>
                            <td class="status">
                                @if($previousDebtRemaining == 0)
                                    LUNAS
                                @endif
                            </td>
                        </tr>
                        @endif
                        
                        @foreach($months as $month)
                            @php
                                $data = $monthlyData[$month];
                            @endphp
                            <tr>
                                <td class="month">{{ $month }}</td>
                                <td class="amount">{{ number_format($data['required'], 0, ',', '.') }}</td>
                                <td class="amount">
                                    @if($data['paid'] > 0)
                                        {{ number_format($data['paid'], 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="amount">{{ number_format($data['remaining'], 0, ',', '.') }}</td>
                                <td class="status">
                                    @if($data['isPaid'])
                                        LUNAS
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        
                        <!-- Total Row -->
                        @php
                            $totalRequired = $effectiveYearly + $previousDebt;
                            // Calculate total remaining directly to avoid rounding errors
                            $totalRemaining = $totalRequired - $totalPayments;
                        @endphp
                        <tr class="total-row">
                            <td class="month"><strong>JUMLAH</strong></td>
                            <td class="amount"><strong>{{ number_format($totalRequired, 0, ',', '.') }}</strong></td>
                            <td class="amount"><strong>{{ number_format($totalPayments, 0, ',', '.') }}</strong></td>
                            <td class="amount"><strong>{{ number_format($totalRemaining, 0, ',', '.') }}</strong></td>
                            <td class="status"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Right Section: Riwayat Transaksi -->
            <div class="right-section">
                <div class="section-title">RIWAYAT TRANSAKSI PEMBAYARAN</div>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th class="no">NO</th>
                            <th class="date">TGL BAYAR</th>
                            <th class="amount">JUMLAH BAYAR</th>
                            <th class="status">STATUS</th>
                            <th class="notes">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $paymentHistory = \App\Models\Payment::where('student_id', $payment->student_id)
                                ->where('billing_record_id', $payment->billing_record_id)
                                ->orderBy('payment_date')
                                ->get();
                        @endphp
                        
                        @foreach($paymentHistory as $index => $histPayment)
                            <tr>
                                <td class="no">{{ $index + 1 }}</td>
                                <td class="date">{{ $histPayment->payment_date ? $histPayment->payment_date->format('d-M-y') : '-' }}</td>
                                <td class="amount">{{ number_format($histPayment->total_amount, 0, ',', '.') }}</td>
                                <td class="status">
                                    <span class="badge badge-{{ $histPayment->status_class }}">
                                        {{ $histPayment->status_text }}
                                    </span>
                                </td>
                                <td class="notes">
                                    @php
                                        $originalNotes = $histPayment->notes ?? '';
                                        // Remove verification logs and system messages
                                        if (str_contains($originalNotes, '[VERIFIKASI]')) {
                                            $originalNotes = '';
                                        }
                                        if (str_contains($originalNotes, 'Status:')) {
                                            $originalNotes = '';
                                        }
                                        // Clean up the notes to show only user input
                                        $originalNotes = trim($originalNotes);
                                    @endphp
                                    {{ $originalNotes }}
                                </td>
                            </tr>
                        @endforeach
                        
                        <!-- Total Row -->
                        <tr class="total-row">
                            <td colspan="2"><strong>TOTAL BAYAR</strong></td>
                            <td class="amount"><strong>{{ number_format($paymentHistory->sum('total_amount'), 0, ',', '.') }}</strong></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Keterangan:</strong> Jika ada kelebihan bayar maka akan dimasukkan ke bulan berikutnya atau ke jenjang kelas berikutnya</p>
            <p>Manyar, {{ $payment->payment_date ? $payment->payment_date->format('d F Y') : now()->format('d F Y') }}</p>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">{{ $payment->kasir->name ?? 'Kasir' }}</div>
                <div class="signature-role">{{ ucfirst($payment->kasir->role ?? 'Staff') }}</div>
            </div>
        </div>
    </div>
</body>
</html>
