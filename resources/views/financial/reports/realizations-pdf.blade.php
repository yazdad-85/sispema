<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Realisasi Kegiatan</title>
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
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            font-size: 16px;
            font-weight: bold;
            color: #333;
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
        .badge-info {
            background-color: #fff;
            color: #000;
        }
        .badge-secondary {
            background-color: #fff;
            color: #000;
        }
        .progress-bar {
            height: 12px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 2px 0;
            border: 1px solid #000;
        }
        .progress-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        .progress-success { background-color: #000; }
        .progress-info { background-color: #333; }
        .progress-warning { background-color: #666; }
        .progress-danger { background-color: #999; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Realisasi Kegiatan</h1>
        <p>Periode: {{ request('academic_year_id') ? $activityPlans->first()->academicYear->name ?? 'Semua Tahun' : 'Semua Tahun' }}</p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>


    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 16%;">Nama Kegiatan</th>
                <th style="width: 8%;">Tahun Ajaran</th>
                <th style="width: 10%;">Kategori</th>
                <th style="width: 9%;">Budget Pemasukan</th>
                <th style="width: 9%;">Budget Pengeluaran</th>
                <th style="width: 9%;">Realisasi Pemasukan</th>
                <th style="width: 9%;">Realisasi Pengeluaran</th>
                <th style="width: 8%;">Progress</th>
                <th style="width: 8%;">Status</th>
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
                    <td class="text-right">
                        @if($plan->category->type == 'pemasukan')
                            <strong>Rp {{ number_format($plan->total_realization, 0, ',', '.') }}</strong>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($plan->category->type == 'pengeluaran')
                            <strong>Rp {{ number_format($plan->total_realization, 0, ',', '.') }}</strong>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="progress-bar">
                            <div class="progress-fill 
                                @if($plan->realization_percentage >= 100) progress-success
                                @elseif($plan->realization_percentage >= 75) progress-info
                                @elseif($plan->realization_percentage >= 50) progress-warning
                                @else progress-danger
                                @endif" 
                                style="width: {{ min($plan->realization_percentage, 100) }}%">
                            </div>
                        </div>
                        <small>{{ number_format($plan->realization_percentage, 1) }}%</small>
                    </td>
                    <td class="text-center">
                        @if($plan->realization_percentage >= 100)
                            <span class="badge badge-success">Selesai</span>
                        @elseif($plan->realization_percentage >= 75)
                            <span class="badge badge-info">Hampir Selesai</span>
                        @elseif($plan->realization_percentage >= 50)
                            <span class="badge badge-warning">Sedang Berjalan</span>
                        @elseif($plan->realization_percentage > 0)
                            <span class="badge badge-danger">Baru Dimulai</span>
                        @else
                            <span class="badge badge-secondary">Belum Dimulai</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px;">
                        <em>Tidak ada rencana kegiatan ditemukan.</em>
                    </td>
                </tr>
            @endforelse
            
            <!-- Total Row -->
            @if($activityPlans->count() > 0)
                @php
                    $totalBudgetPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('budget_amount');
                    $totalBudgetPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('budget_amount');
                    $totalRealisasiPemasukan = $activityPlans->where('category.type', 'pemasukan')->sum('total_realization');
                    $totalRealisasiPengeluaran = $activityPlans->where('category.type', 'pengeluaran')->sum('total_realization');
                    $netRealisasi = $totalRealisasiPemasukan - $totalRealisasiPengeluaran;
                @endphp
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="4" class="text-right">TOTAL:</td>
                    <td class="text-right">Rp {{ number_format($totalBudgetPemasukan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalBudgetPengeluaran, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalRealisasiPemasukan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalRealisasiPengeluaran, 0, ',', '.') }}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">
                        NET: Rp {{ number_format($netRealisasi, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada {{ date('d/m/Y H:i:s') }} | Halaman 1</p>
    </div>
</body>
</html>
