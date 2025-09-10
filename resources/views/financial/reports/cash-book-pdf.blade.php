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
        .summary-success { color: #28a745; }
        .summary-danger { color: #dc3545; }
        .summary-primary { color: #007bff; }
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

    <div class="footer">
        <p>Dicetak pada {{ date('d/m/Y H:i:s') }} | Halaman 1</p>
    </div>
</body>
</html>
