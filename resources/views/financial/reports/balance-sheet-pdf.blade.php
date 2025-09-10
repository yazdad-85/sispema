<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neraca Keuangan</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 2cm;
        }
        .no-break {
            page-break-inside: avoid;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #333;
            font-weight: normal;
        }
        .header p {
            margin: 8px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .balance-section {
            margin-bottom: 25px;
        }
        .balance-header {
            background-color: #f0f0f0;
            padding: 12px;
            font-weight: bold;
            border: 2px solid #000;
            margin-bottom: 10px;
            text-align: center;
            font-size: 12px;
        }
        .balance-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }
        .balance-item:last-child {
            border-bottom: 2px solid #000;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .balance-item.total {
            border-top: 2px solid #000;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: #000;
            text-align: center;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-success {
            color: #28a745;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-muted {
            color: #6c757d;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        // Perhitungan otomatis untuk neraca
        $totalPiutang = 0; // Total tagihan SPP yang belum dibayar
        $totalHutang = 0; // Total hutang yang belum dibayar
        $totalModal = ($entries->last()->balance ?? 0) + ($totalCredit - $totalDebit); // Modal = Kas + Laba Bersih
        
        // Hitung hutang jangka pendek dari realisasi yang belum dibayar
        $unpaidRealizations = \App\Models\ActivityRealization::where('status', 'pending')
            ->orWhere('status', 'approved')
            ->where('payment_date', null)
            ->get();
        
        foreach($unpaidRealizations as $realization) {
            $totalHutang += $realization->amount;
        }
        
        // Hitung modal dari akumulasi laba bersih
        $totalModal = ($entries->last()->balance ?? 0) + ($totalCredit - $totalDebit);
        
        // Hitung piutang SPP dari tagihan yang belum dibayar
        $students = \App\Models\Student::with(['billingRecords' => function($query) {
            $query->where('academic_year_id', request('academic_year_id'));
        }])->get();
        
        foreach($students as $student) {
            foreach($student->billingRecords as $billing) {
                $totalObligation = $billing->effectiveYearly; // Total kewajiban tahunan
                $totalPaid = $student->payments()
                    ->where('status', 'verified')
                    ->sum('total_amount');
                $remaining = $totalObligation - $totalPaid;
                if($remaining > 0) {
                    $totalPiutang += $remaining;
                }
            }
        }
    @endphp

    <div class="header">
        <h1>Neraca Keuangan</h1>
        <h2>Sistem Informasi SPP Online</h2>
        <p>Periode: {{ request('start_date') ? date('d/m/Y', strtotime(request('start_date'))) : 'Semua' }} - {{ request('end_date') ? date('d/m/Y', strtotime(request('end_date'))) : 'Semua' }}</p>
        <p>Tanggal Cetak: {{ date('d F Y, H:i') }}</p>
    </div>


    <!-- Neraca dalam Format Formal -->
    <div style="margin-bottom: 30px;">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                    <div class="balance-header">
                        AKTIVA
                    </div>
                    <div class="balance-item">
                        <span>Kas dan Bank</span>
                        <span class="text-right">Rp {{ number_format($entries->last()->balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="balance-item">
                        <span>Piutang SPP</span>
                        <span class="text-right">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</span>
                    </div>
                    <div class="balance-item total">
                        <span>TOTAL AKTIVA</span>
                        <span class="text-right">Rp {{ number_format(($entries->last()->balance ?? 0) + $totalPiutang, 0, ',', '.') }}</span>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 15px;">
                    <div class="balance-header">
                        PASIVA
                    </div>
                    <div class="balance-item">
                        <span>Hutang Jangka Pendek</span>
                        <span class="text-right">Rp {{ number_format($totalHutang, 0, ',', '.') }}</span>
                    </div>
                    <div class="balance-item">
                        <span>Modal</span>
                        <span class="text-right">Rp {{ number_format($totalModal, 0, ',', '.') }}</span>
                    </div>
                    <div class="balance-item total">
                        <span>TOTAL PASIVA</span>
                        <span class="text-right">Rp {{ number_format($totalHutang + $totalModal, 0, ',', '.') }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Laporan Laba Rugi -->
    <div class="balance-section">
        <div class="balance-header">
            LAPORAN LABA RUGI
        </div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                    <div style="background-color: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000; margin-bottom: 10px;">
                        PENDAPATAN
                    </div>
                    @forelse($pemasukanCategories as $category)
                        @php
                            $categoryTotal = $entries->where('reference_type', 'payment')
                                ->filter(function($entry) use ($category) {
                                    if (!$entry->payment) return false;
                                    $realization = App\Models\ActivityRealization::where('proof', $entry->payment->receipt_number)
                                        ->whereHas('plan', function($query) use ($category) {
                                            $query->where('category_id', $category->id);
                                        })
                                        ->first();
                                    return $realization !== null;
                                })
                                ->sum('credit');
                        @endphp
                        <div class="balance-item">
                            <span>{{ $category->name }}</span>
                            <span class="text-right">Rp {{ number_format($categoryTotal, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="balance-item">
                            <span>Tidak ada pendapatan</span>
                            <span class="text-right">Rp 0</span>
                        </div>
                    @endforelse
                    <div class="balance-item total">
                        <span>TOTAL PENDAPATAN</span>
                        <span class="text-right">Rp {{ number_format($totalCredit, 0, ',', '.') }}</span>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 15px;">
                    <div style="background-color: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000; margin-bottom: 10px;">
                        BEBAN
                    </div>
                    @forelse($pengeluaranCategories as $category)
                        @php
                            $categoryTotal = $entries->where('reference_type', 'realization')
                                ->filter(function($entry) use ($category) {
                                    if (!$entry->realization) return false;
                                    $plan = $entry->realization->plan;
                                    return $plan && $plan->category_id == $category->id;
                                })
                                ->sum('debit');
                        @endphp
                        <div class="balance-item">
                            <span>{{ $category->name }}</span>
                            <span class="text-right">Rp {{ number_format($categoryTotal, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="balance-item">
                            <span>Tidak ada beban</span>
                            <span class="text-right">Rp 0</span>
                        </div>
                    @endforelse
                    <div class="balance-item total">
                        <span>TOTAL BEBAN</span>
                        <span class="text-right">Rp {{ number_format($totalDebit, 0, ',', '.') }}</span>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Laba Rugi Bersih -->
        <div style="border-top: 2px solid #000; padding-top: 15px; margin-top: 20px;">
            <div class="balance-item total" style="background-color: #f0f0f0; padding: 15px; border: 2px solid #000;">
                <span style="font-size: 14px; font-weight: bold;">
                    {{ ($totalCredit - $totalDebit) >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                </span>
                <span class="text-right" style="font-size: 16px; font-weight: bold;">
                    Rp {{ number_format($totalCredit - $totalDebit, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>


    @php
        $appSetting = \App\Models\AppSetting::first();
        $cityName = $appSetting ? $appSetting->app_city : 'Kota';
    @endphp

    <div class="signature-section no-break">
        <!-- Baris Pertama: Bendahara (kiri) dan Admin Sistem (kanan) -->
        <table style="width: 100%; border: none; border-collapse: separate; border-spacing: 0; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; border: none; padding: 0; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 10px 0; font-size: 10px;">&nbsp;</p>
                    <p style="margin: 0 0 40px 0; font-size: 11px;">Bendahara,</p>
                    <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; width: 150px; margin-left: auto; margin-right: auto;"></div>
                    <p style="margin: 0; font-size: 10px; font-weight: bold;">Bendahara Sekolah</p>
                </td>
                <td style="width: 50%; border: none; padding: 0; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 10px 0; font-size: 10px;">{{ $cityName }}, {{ date('d F Y') }}</p>
                    <p style="margin: 0 0 40px 0; font-size: 11px;">Dibuat oleh,</p>
                    <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; width: 150px; margin-left: auto; margin-right: auto;"></div>
                    <p style="margin: 0; font-size: 10px; font-weight: bold;">Admin Sistem</p>
                </td>
            </tr>
        </table>
        
        <!-- Baris Kedua: Mengetahui (tengah) -->
        <table style="width: 100%; border: none; border-collapse: separate; border-spacing: 0;">
            <tr>
                <td style="width: 100%; border: none; padding: 0; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 40px 0; font-size: 11px;">Mengetahui,</p>
                    <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; width: 200px; margin-left: auto; margin-right: auto;"></div>
                    <p style="margin: 0; font-size: 10px; font-weight: bold;">Kepala Sekolah</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer no-break">
        <p>Dicetak pada {{ date('d F Y, H:i:s') }} | Halaman 1 dari 1 | Sistem Informasi SPP Online</p>
    </div>
</body>
</html>
