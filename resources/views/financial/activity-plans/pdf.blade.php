<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rencana Kegiatan</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
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
        .text-bold {
            font-weight: bold;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #000;
            display: inline-block;
        }
        .badge-primary {
            background-color: #e3f2fd;
            color: #000;
        }
        .badge-success {
            background-color: #e8f5e8;
            color: #000;
        }
        .badge-danger {
            background-color: #ffebee;
            color: #000;
        }
        .badge-warning {
            background-color: #fff3e0;
            color: #000;
        }
        .badge-secondary {
            background-color: #f5f5f5;
            color: #000;
        }
        .badge-info {
            background-color: #e1f5fe;
            color: #000;
        }
        .total-row {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 10px;
            page-break-inside: avoid;
        }
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .progress-bar {
            width: 100%;
            height: 15px;
            background-color: #e0e0e0;
            border: 1px solid #000;
            border-radius: 2px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #4caf50;
            transition: width 0.3s ease;
        }
        .progress-fill.warning {
            background-color: #ff9800;
        }
        .progress-fill.danger {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rencana Kegiatan</h1>
        <h2>Sistem Informasi SPP Online</h2>
        <p>Periode: {{ request('academic_year_id') ? $activityPlans->first()->academicYear->year_start ?? 'Semua Tahun' : 'Semua Tahun' }} - {{ request('academic_year_id') ? $activityPlans->first()->academicYear->year_end ?? 'Semua Tahun' : 'Semua Tahun' }}</p>
        <p>Tanggal Cetak: {{ date('d F Y, H:i') }}</p>
    </div>

    @php
        $totalBudget = $activityPlans->sum('budget_amount');
        $totalRealization = $activityPlans->sum('total_realization');
        $totalRemaining = $activityPlans->sum('remaining_budget');
        $sppPlans = $activityPlans->where('category.name', 'like', '%SPP%');
        $otherPlans = $activityPlans->where('category.name', 'not like', '%SPP%');
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 20%;">Nama Kegiatan</th>
                <th style="width: 8%;">Tahun Ajaran</th>
                <th style="width: 10%;">Lembaga</th>
                <th style="width: 6%;">Level</th>
                <th style="width: 10%;">Kategori</th>
                <th style="width: 12%;">Budget (Rp)</th>
                <th style="width: 12%;">Realisasi (Rp)</th>
                <th style="width: 12%;">Sisa (Rp)</th>
                <th style="width: 7%;">Progress</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activityPlans as $plan)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="text-bold">{{ $plan->name }}</div>
                        @if($plan->description)
                            <small style="color: #666;">{{ Str::limit($plan->description, 60) }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-primary">{{ $plan->academicYear->year_start }}/{{ $plan->academicYear->year_end }}</span>
                    </td>
                    <td class="text-center">
                        @if($plan->institution)
                            <span class="badge badge-info">{{ $plan->institution->name }}</span>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($plan->level)
                            <span class="badge badge-secondary">{{ $plan->level }}</span>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $plan->category->type === 'pemasukan' ? 'badge-success' : 'badge-warning' }}">
                            {{ $plan->category->name }}
                        </span>
                    </td>
                    <td class="text-right text-bold">
                        Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        Rp {{ number_format($plan->total_realization, 0, ',', '.') }}
                    </td>
                    <td class="text-right {{ $plan->remaining_budget >= 0 ? 'text-bold' : '' }}" style="color: {{ $plan->remaining_budget >= 0 ? '#28a745' : '#dc3545' }};">
                        Rp {{ number_format($plan->remaining_budget, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @php
                            $percentage = $plan->budget_amount > 0 ? ($plan->total_realization / $plan->budget_amount) * 100 : 0;
                            $progressClass = $percentage > 100 ? 'danger' : ($percentage > 80 ? 'warning' : '');
                        @endphp
                        <div style="margin-bottom: 2px;">{{ number_format($percentage, 1) }}%</div>
                        <div class="progress-bar">
                            <div class="progress-fill {{ $progressClass }}" style="width: {{ min($percentage, 100) }}%;"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 30px;">
                        <em>Tidak ada rencana kegiatan ditemukan.</em>
                    </td>
                </tr>
            @endforelse
            
            <!-- Total Row -->
            @if($activityPlans->count() > 0)
                <tr class="total-row">
                    <td colspan="6" class="text-right text-bold">TOTAL:</td>
                    <td class="text-right text-bold">Rp {{ number_format($totalBudget, 0, ',', '.') }}</td>
                    <td class="text-right text-bold">Rp {{ number_format($totalRealization, 0, ',', '.') }}</td>
                    <td class="text-right text-bold" style="color: {{ $totalRemaining >= 0 ? '#28a745' : '#dc3545' }};">Rp {{ number_format($totalRemaining, 0, ',', '.') }}</td>
                    <td class="text-center text-bold">{{ $totalBudget > 0 ? number_format(($totalRealization / $totalBudget) * 100, 1) : 0 }}%</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($sppPlans->count() > 0)
        <div style="margin-top: 25px;">
            <h3 style="font-size: 14px; margin-bottom: 10px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                Ringkasan Penerimaan SPP
            </h3>
            <table style="font-size: 9px;">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 25%;">Lembaga</th>
                        <th style="width: 15%;">Level</th>
                        <th style="width: 20%;">Budget SPP</th>
                        <th style="width: 20%;">Realisasi SPP</th>
                        <th style="width: 15%;">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sppPlans->groupBy('institution.name') as $institutionName => $plans)
                        @foreach($plans->groupBy('level') as $level => $levelPlans)
                            @php
                                $levelBudget = $levelPlans->sum('budget_amount');
                                $levelRealization = $levelPlans->sum('total_realization');
                                $levelProgress = $levelBudget > 0 ? ($levelRealization / $levelBudget) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $institutionName }}</td>
                                <td class="text-center">{{ $level }}</td>
                                <td class="text-right">Rp {{ number_format($levelBudget, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($levelRealization, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($levelProgress, 1) }}%</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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
