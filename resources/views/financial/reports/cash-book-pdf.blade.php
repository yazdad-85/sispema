<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Kas Umum</title>
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
        .badge-info {
            background-color: #fff;
            color: #000;
        }
        .text-success {
            color: #000;
        }
        .text-danger {
            color: #000;
        }
        .text-muted {
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
        <h1>Buku Kas Umum</h1>
        <p>Periode: {{ request('start_date') ? date('d/m/Y', strtotime(request('start_date'))) : 'Semua' }} - {{ request('end_date') ? date('d/m/Y', strtotime(request('end_date'))) : 'Semua' }}</p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>


    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 30%;">Keterangan</th>
                <th style="width: 12%;">Debit</th>
                <th style="width: 12%;">Kredit</th>
                <th style="width: 12%;">Saldo</th>
                <th style="width: 8%;">Ref</th>
                <th style="width: 12%;">Keterangan Ref</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $entry->date->format('d/m/Y') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="text-right">
                        @if($entry->debit > 0)
                            <strong>Rp {{ number_format($entry->debit, 0, ',', '.') }}</strong>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($entry->credit > 0)
                            <strong>Rp {{ number_format($entry->credit, 0, ',', '.') }}</strong>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <strong>Rp {{ number_format($entry->balance, 0, ',', '.') }}</strong>
                    </td>
                    <td class="text-center">
                        @if($entry->reference_type && $entry->reference_id)
                            <span class="badge badge-info">
                                {{ ucfirst($entry->reference_type) }}
                            </span>
                        @else
                            <span>Manual</span>
                        @endif
                    </td>
                    <td>
                        @if($entry->reference_type && $entry->reference_id)
                            <small>
                                @if($entry->reference_type == 'payment')
                                    Pembayaran SPP
                                @elseif($entry->reference_type == 'activity_realization')
                                    Realisasi Kegiatan
                                @else
                                    {{ ucfirst($entry->reference_type) }}
                                @endif
                            </small>
                        @else
                            <span>Manual</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        <em>Tidak ada transaksi ditemukan.</em>
                    </td>
                </tr>
            @endforelse
            
            <!-- Total Row -->
            @if($entries->count() > 0)
                @php
                    $totalDebit = $entries->sum('debit');
                    $totalCredit = $entries->sum('credit');
                    $finalBalance = $entries->last()->balance ?? 0;
                @endphp
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" class="text-right">TOTAL:</td>
                    <td class="text-right">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($finalBalance, 0, ',', '.') }}</td>
                    <td colspan="2" class="text-center">-</td>
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
