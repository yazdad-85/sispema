<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neraca Keuangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
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
        .summary-info { color: #17a2b8; }
        .category-section {
            margin-bottom: 30px;
        }
        .category-header {
            background-color: #f8f9fa;
            padding: 10px;
            font-weight: bold;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        .category-success { background-color: #d4edda; border-color: #c3e6cb; }
        .category-danger { background-color: #f8d7da; border-color: #f5c6cb; }
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        .category-item:last-child {
            border-bottom: none;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
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
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Neraca Keuangan</h1>
        <p>Periode: {{ request('start_date') ? date('d/m/Y', strtotime(request('start_date'))) : 'Semua' }} - {{ request('end_date') ? date('d/m/Y', strtotime(request('end_date'))) : 'Semua' }}</p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>


    <div class="category-section">
        <div class="category-header category-success">
            <i class="fas fa-arrow-up"></i> Pemasukan per Kategori
        </div>
        @forelse($pemasukanCategories as $category)
            @php
                $categoryTotal = $entries->where('reference_type', 'payment')
                    ->filter(function($entry) use ($category) {
                        if (!$entry->payment) return false;
                        // Cek apakah pembayaran ini memiliki realisasi dengan kategori yang sesuai
                        $realization = App\Models\ActivityRealization::where('proof', $entry->payment->receipt_number)
                            ->whereHas('plan', function($query) use ($category) {
                                $query->where('category_id', $category->id);
                            })
                            ->first();
                        return $realization !== null;
                    })
                    ->sum('credit');
            @endphp
            <div class="category-item">
                <span>{{ $category->name }}</span>
                <span class="text-success fw-bold">Rp {{ number_format($categoryTotal, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="category-item">
                <span class="text-muted">Tidak ada kategori pemasukan</span>
                <span class="text-muted">Rp 0</span>
            </div>
        @endforelse
    </div>

    <div class="category-section">
        <div class="category-header category-danger">
            <i class="fas fa-arrow-down"></i> Pengeluaran per Kategori
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
            <div class="category-item">
                <span>{{ $category->name }}</span>
                <span class="text-danger fw-bold">Rp {{ number_format($categoryTotal, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="category-item">
                <span class="text-muted">Tidak ada kategori pengeluaran</span>
                <span class="text-muted">Rp 0</span>
            </div>
        @endforelse
    </div>

    <div class="category-section">
        <div class="category-header">
            <i class="fas fa-history"></i> Transaksi Terbaru
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 40%;">Keterangan</th>
                    <th style="width: 15%;">Debit</th>
                    <th style="width: 15%;">Kredit</th>
                    <th style="width: 15%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries->take(10) as $entry)
                    <tr>
                        <td>{{ $entry->date->format('d/m/Y') }}</td>
                        <td>{{ Str::limit($entry->description, 50) }}</td>
                        <td class="text-right">
                            @if($entry->debit > 0)
                                <span class="text-danger fw-bold">Rp {{ number_format($entry->debit, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($entry->credit > 0)
                                <span class="text-success fw-bold">Rp {{ number_format($entry->credit, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <strong>Rp {{ number_format($entry->balance, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 20px;">
                            <em>Tidak ada transaksi</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Dicetak pada {{ date('d/m/Y H:i:s') }} | Halaman 1</p>
    </div>
</body>
</html>
