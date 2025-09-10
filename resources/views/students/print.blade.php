<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Siswa - {{ $student->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 8px; vertical-align: top; }
        .label { width: 160px; font-weight: bold; }
        .header { text-align: center; margin-bottom: 12px; }
        .small { color: #666; }
    </style>
    </head>
<body>
    <div class="header">
        <h2>Data Siswa</h2>
        <div class="small">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <table>
        <tr>
            <td class="label">NIS</td>
            <td>: {{ $student->nis }}</td>
        </tr>
        <tr>
            <td class="label">Nama Lengkap</td>
            <td>: {{ $student->name }}</td>
        </tr>
        <tr>
            <td class="label">Lembaga</td>
            <td>: {{ $student->institution->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td>
            <td>: {{ optional($student->academicYear)->year_start }}-{{ optional($student->academicYear)->year_end }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td>: {{ optional($student->classRoom)->class_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kategori Beasiswa</td>
            <td>: {{ optional($student->scholarshipCategory)->name ?? '-' }}
                @if(optional($student->scholarshipCategory)->discount_percentage)
                    (Diskon {{ (float)$student->scholarshipCategory->discount_percentage }}%)
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td>: {{ $student->email ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">No. HP</td>
            <td>: {{ $student->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>: {{ $student->address ?? '-' }}</td>
        </tr>
        @if($student->previous_debt > 0)
        <tr>
            <td class="label">Tunggakan Sebelumnya</td>
            <td>: Rp {{ number_format($student->previous_debt, 0, ',', '.') }} ({{ $student->previous_debt_year }})</td>
        </tr>
        @endif
    </table>

    @php
        $annualBilling = $student->billingRecords->firstWhere('notes','ANNUAL');
        $yearlyAmount = $annualBilling ? $annualBilling->amount : 0;
        // Terapkan diskon beasiswa bila ada
        $scholarshipPct = (float) (optional($student->scholarshipCategory)->discount_percentage ?? 0);
        $discountAmount = $yearlyAmount * ($scholarshipPct/100);
        $effectiveYearly = max(0, $yearlyAmount - $discountAmount);
        $smart = $effectiveYearly > 0 ? \App\Models\FeeStructure::calculateSmartMonthlyDistribution($effectiveYearly) : ['monthly_breakdown'=>[]];
        $monthlyBreakdown = $smart['monthly_breakdown'] ?? [];
        $months = ['Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'];
        $previousDebt = $student->previous_debt ?? 0;
        $totalPayments = $student->payments->whereIn('status',["verified","completed"]) ->sum('total_amount');
        $totalObligation = ($effectiveYearly) + $previousDebt;
        $remainingBalance = max(0, $totalObligation - $totalPayments);
    @endphp

    <h3 style="margin-top:18px;">Ringkasan Tagihan</h3>
    <table border="1" cellspacing="0" cellpadding="4">
        <tr>
            <td class="label">Total Tahunan</td>
            <td>: Rp {{ number_format($yearlyAmount, 0, ',', '.') }}</td>
        </tr>
        @if($scholarshipPct > 0)
        <tr>
            <td class="label">Diskon Beasiswa</td>
            <td>: {{ $scholarshipPct }}% (Rp {{ number_format($discountAmount, 0, ',', '.') }})</td>
        </tr>
        <tr>
            <td class="label">Setelah Diskon</td>
            <td>: Rp {{ number_format($effectiveYearly, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Tunggakan Sebelumnya</td>
            <td>: Rp {{ number_format($previousDebt, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Total Kewajiban</td>
            <td>: Rp {{ number_format($totalObligation, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Total Pembayaran</td>
            <td>: Rp {{ number_format($totalPayments, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Sisa Tagihan</td>
            <td>: Rp {{ number_format($remainingBalance, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h3 style="margin-top:18px;">Riwayat Tagihan</h3>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
            <tr>
                <td><strong>Bulan</strong></td>
                <td><strong>Jumlah</strong></td>
                <td><strong>Sisa Tagihan</strong></td>
                <td><strong>Status</strong></td>
                <td><strong>Jatuh Tempo</strong></td>
            </tr>
        </thead>
        <tbody>
            @if($previousDebt > 0)
            @php
                $debtRemaining = max(0, $previousDebt - $totalPayments);
            @endphp
            <tr>
                <td><strong>Tunggakan ({{ $student->previous_debt_year }})</strong></td>
                <td>Rp {{ number_format($previousDebt, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($debtRemaining, 0, ',', '.') }}</td>
                <td>{{ $debtRemaining > 0 ? 'BELUM LUNAS' : 'LUNAS' }}</td>
                <td>-</td>
            </tr>
            @endif

            @php $paidForMonths = max(0, $totalPayments - $previousDebt); @endphp
            @foreach($months as $i => $m)
                @php
                    $required = $monthlyBreakdown[$m] ?? 0;
                    $paidBefore = 0;
                    for ($j=0; $j<$i; $j++) { $paidBefore += ($monthlyBreakdown[$months[$j]] ?? 0); }
                    $availableForThis = max(0, $paidForMonths - $paidBefore);
                    $paid = min($required, $availableForThis);
                    $remain = max(0, $required - $paid);
                    $isPaid = $remain == 0;
                    $dueDate = $annualBilling && $annualBilling->academicYear ? \Carbon\Carbon::create($annualBilling->academicYear->year_end, 12, 31)->format('d/m/Y') : '-';
                @endphp
                <tr>
                    <td>{{ $m }}</td>
                    <td>Rp {{ number_format($required, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($remain, 0, ',', '.') }}</td>
                    <td>{{ $isPaid ? 'LUNAS' : 'BELUM LUNAS' }}</td>
                    <td>{{ $dueDate }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td><strong>TOTAL</strong></td>
                <td>Rp {{ number_format($effectiveYearly + $previousDebt, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($remainingBalance, 0, ',', '.') }}</td>
                <td>{{ $remainingBalance > 0 ? 'BELUM LUNAS' : 'LUNAS' }}</td>
                <td>-</td>
            </tr>
        </tfoot>
    </table>

    @if($student->payments && $student->payments->count() > 0)
    <h3 style="margin-top:18px;">Riwayat Pembayaran</h3>
    <table border="1" cellspacing="0" cellpadding="0">
        <tr>
            <td class="label">Tanggal</td>
            <td class="label">Jumlah</td>
            <td class="label">Metode</td>
            <td class="label">Keterangan</td>
        </tr>
        @foreach($student->payments as $pay)
        <tr>
            <td>{{ optional($pay->payment_date)->format('d/m/Y') }}</td>
            <td>Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
            <td>{{ $pay->method ?? '-' }}</td>
            <td>{{ $pay->description ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif
</body>
</html>


