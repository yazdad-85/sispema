<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Pembayaran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            margin: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .subtitle {
            font-size: 12px;
            margin-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 7px;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px;
            text-align: left;
            font-size: 7px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .signature {
            margin-top: 40px;
            text-align: right;
        }
        .month-column {
            width: 45px;
            text-align: center;
        }
        .amount-column {
            width: 60px;
            text-align: right;
        }
        .name-column {
            width: 80px;
        }
        .total-column {
            width: 70px;
            text-align: right;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REKAPITULASI PEMBAYARAN</div>
        <div class="subtitle">{{ $institutionName }}</div>
        <div class="subtitle">{{ $className }}</div>
        <div class="subtitle">{{ $academicYear }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="name-column">Nama Siswa</th>
                <th rowspan="2" class="amount-column">Tagihan Sebelumnya</th>
                <th colspan="12" class="text-center">Bulan</th>
                <th rowspan="2" class="total-column">Total Tagihan</th>
                <th rowspan="2" class="total-column">Jumlah Pembayaran</th>
                <th rowspan="2" class="total-column">Sisa Pembayaran</th>
            </tr>
            <tr>
                <th class="month-column">Juli</th>
                <th class="month-column">Agustus</th>
                <th class="month-column">September</th>
                <th class="month-column">Oktober</th>
                <th class="month-column">November</th>
                <th class="month-column">Desember</th>
                <th class="month-column">Januari</th>
                <th class="month-column">Februari</th>
                <th class="month-column">Maret</th>
                <th class="month-column">April</th>
                <th class="month-column">Mei</th>
                <th class="month-column">Juni</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                @php
                    // Get annual billing record for current academic year
                    $annualBilling = $student->billingRecords->where('notes', 'ANNUAL')->first();
                    $yearlyAmount = $annualBilling ? $annualBilling->amount : 0;
                    $monthlyAmount = $yearlyAmount > 0 ? round($yearlyAmount / 12) : 0;
                    
                    // Get all payments for this student in current academic year
                    $payments = $student->payments->where('billing_record_id', $annualBilling->id ?? 0);
                    $totalPayments = $payments->sum('total_amount');
                    
                    // Get previous debt
                    $previousDebt = $student->previous_debt ?? 0;
                    $totalRequired = $yearlyAmount + $previousDebt;
                    $totalRemaining = $totalRequired - $totalPayments;
                    
                    // Calculate monthly payment allocation based on actual payment dates
                    $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 
                             'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                    
                    $monthlyData = [];
                    $cumulativeRemaining = 0;
                    
                    // First, calculate cumulative remaining considering previous debt
                    if ($previousDebt > 0) {
                        $cumulativeRemaining = $previousDebt;
                    }
                    
                    // Calculate monthly data with actual payment dates
                    foreach ($months as $month) {
                        $monthlyRequired = $monthlyAmount;
                        
                        // Get payments made in this month based on payment_date
                        $monthlyPaid = 0;
                        foreach ($payments as $payment) {
                            if ($payment->payment_date) {
                                $paymentMonth = $payment->payment_date->format('n'); // 1-12
                                $monthIndex = array_search($month, $months) + 7; // Juli = 7, Agustus = 8, etc.
                                
                                // Adjust month index for academic year (Juli = 7, Agustus = 8, ..., Juni = 6)
                                if ($monthIndex > 12) {
                                    $monthIndex -= 12;
                                }
                                
                                if ($paymentMonth == $monthIndex) {
                                    $monthlyPaid += $payment->total_amount;
                                }
                            }
                        }
                        
                        // Calculate remaining for this month (cumulative)
                        $monthlyRemaining = $cumulativeRemaining + $monthlyRequired - $monthlyPaid;
                        $cumulativeRemaining = $monthlyRemaining;
                        
                        $monthlyData[$month] = [
                            'required' => $monthlyRequired,
                            'paid' => $monthlyPaid,
                            'remaining' => $monthlyRemaining
                        ];
                    }
                @endphp
                <tr>
                    <td>{{ $student->name }}</td>
                    <td class="text-right">
                        @if($previousDebt > 0)
                            {{ number_format($previousDebt, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    @foreach($months as $month)
                        @php $data = $monthlyData[$month]; @endphp
                        <td class="text-right">
                            @if($data['paid'] > 0)
                                {{ number_format($data['paid'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    <td class="text-right">{{ number_format($totalRequired, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalPayments, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalRemaining, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>{{ App\Models\AppSetting::getValue('app_city', 'Kota') }}, {{ date('d F Y') }}</div>
        <div class="signature">
            <div>{{ $user->name }}</div>
        </div>
    </div>
</body>
</html>
