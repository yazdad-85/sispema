<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rencana Kegiatan</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #000;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #000;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: #000;
            text-align: center;
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
        .badge {
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #000;
        }
        .badge-primary {
            background-color: #fff;
            color: #000;
        }
        .badge-success {
            background-color: #fff;
            color: #000;
        }
        .badge-danger {
            background-color: #fff;
            color: #000;
        }
        .badge-warning {
            background-color: #fff;
            color: #000;
        }
        .badge-secondary {
            background-color: #fff;
            color: #000;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
        .no-break {
            page-break-inside: avoid;
        }
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rencana Kegiatan</h1>
        <p>Periode: {{ request('academic_year_id') ? $activityPlans->first()->academicYear->name ?? 'Semua Tahun' : 'Semua Tahun' }}</p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>


    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 18%;">Nama Kegiatan</th>
                <th style="width: 8%;">Tahun Ajaran</th>
                <th style="width: 10%;">Kategori</th>
                <th style="width: 12%;">Periode</th>
                <th style="width: 10%;">Pemasukan</th>
                <th style="width: 10%;">Pengeluaran</th>
                <th style="width: 8%;">Perhitungan</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activityPlans as $plan)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $plan->name }}</strong>
                        @if($plan->description)
                            <br><small>{{ Str::limit($plan->description, 50) }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-primary">{{ $plan->academicYear->name }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $plan->category->type == 'pemasukan' ? 'badge-success' : 'badge-danger' }}">
                            {{ $plan->category->name }}
                        </span>
                    </td>
                    <td>
                        <small>
                            Mulai: {{ $plan->start_date->format('d/m/Y') }}<br>
                            Selesai: {{ $plan->end_date->format('d/m/Y') }}
                        </small>
                    </td>
                    <td class="text-right">
                        @if($plan->category->type == 'pemasukan')
                            <strong>Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}</strong>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($plan->category->type == 'pengeluaran')
                            <strong>Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}</strong>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td>
                        @if($plan->unit_price)
                            <small>
                                @php
                                    $formula = 'Rp ' . number_format($plan->unit_price, 0, ',', '.');
                                    if($plan->equivalent_1) $formula .= ' × ' . number_format($plan->equivalent_1, 0, ',', '.') . ' ' . ($plan->unit_1 ?? 'pax');
                                    if($plan->equivalent_2) $formula .= ' × ' . number_format($plan->equivalent_2, 0, ',', '.') . ' ' . ($plan->unit_2 ?? 'orang');
                                    if($plan->equivalent_3) $formula .= ' × ' . number_format($plan->equivalent_3, 0, ',', '.') . ' ' . ($plan->unit_3 ?? 'kegiatan');
                                @endphp
                                {{ $formula }}
                            </small>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $now = now();
                            $isActive = $now->between($plan->start_date, $plan->end_date);
                            $isUpcoming = $now->lt($plan->start_date);
                            $isCompleted = $now->gt($plan->end_date);
                        @endphp
                        
                        @if($isActive)
                            <span class="badge badge-success">Aktif</span>
                        @elseif($isUpcoming)
                            <span class="badge badge-warning">Akan Datang</span>
                        @elseif($isCompleted)
                            <span class="badge badge-secondary">Selesai</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">
                        <em>Tidak ada rencana kegiatan ditemukan.</em>
                    </td>
                </tr>
            @endforelse
            
            <!-- Total Row -->
            @if($activityPlans->count() > 0)
                @php
                    $totalPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('budget_amount');
                    $totalPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('budget_amount');
                    $netBudget = $totalPemasukan - $totalPengeluaran;
                @endphp
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="5" class="text-right">TOTAL:</td>
                    <td class="text-right">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">
                        NET: Rp {{ number_format($netBudget, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

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
        <p>Dicetak pada {{ date('d/m/Y H:i:s') }} | Halaman 1</p>
    </div>
</body>
</html>
