<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Level System - SISPEMA YASMU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">🧪 Test Sistem Level Kelas</h1>
        
        <!-- Test 1: Verifikasi Level Kelas -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Test 1: Verifikasi Level Kelas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kelas</th>
                                <th>Lembaga</th>
                                <th>Level Terdeteksi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes->take(10) as $index => $class)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $class->class_name }}</td>
                                    <td>{{ $class->institution->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            {{ $class->level ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($class->level)
                                            <span class="badge bg-success text-white">✅ OK</span>
                                        @else
                                            <span class="badge bg-danger text-white">❌ Error</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-muted">Menampilkan 10 kelas pertama dari total {{ $classes->count() }} kelas</p>
                </div>
            </div>
        </div>
        
        <!-- Test 2: Verifikasi Struktur Biaya -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Test 2: Verifikasi Struktur Biaya Berdasarkan Level</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lembaga</th>
                                <th>Kelas</th>
                                <th>Level</th>
                                <th>Biaya Bulanan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feeStructures->take(10) as $index => $feeStructure)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $feeStructure->institution->name ?? 'N/A' }}</td>
                                                                            <td>{{ $feeStructure->class->class_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                {{ $feeStructure->class->safe_level ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($feeStructure->monthly_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if($feeStructure->class && $feeStructure->class->safe_level)
                                                <span class="badge bg-success text-white">✅ OK</span>
                                            @else
                                                <span class="badge bg-danger text-white">❌ Error</span>
                                            @endif
                                        </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-muted">Menampilkan 10 struktur biaya pertama dari total {{ $feeStructures->count() }} struktur biaya</p>
                </div>
            </div>
        </div>
        
        <!-- Test 3: Test API Find By Level -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Test 3: Test API Find By Level</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Test dengan Academic Year:</h6>
                        <button class="btn btn-primary" onclick="testFindByLevel()">Test findByLevel</button>
                        <div id="result1" class="mt-2"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Test tanpa Academic Year:</h6>
                        <button class="btn btn-success" onclick="testFindByLevelOnly()">Test findByLevelOnly</button>
                        <div id="result2" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="{{ route('fee-structures.index') }}" class="btn btn-secondary">← Kembali ke Struktur Biaya</a>
        </div>
    </div>

    <script>
        function testFindByLevel() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = '<div class="alert alert-info">Testing...</div>';
            
            fetch('/fee-structures/find-by-level', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    institution_id: 1,
                    academic_year_id: 1,
                    level: 'VII'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">
                        <strong>✅ Success!</strong><br>
                        Level: ${data.data.level}<br>
                        Biaya: Rp ${Number(data.data.monthly_amount).toLocaleString('id-ID')}/bulan
                    </div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-warning">
                        <strong>⚠️ Response:</strong> ${data.message}
                    </div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>❌ Error:</strong> ${error.message}
                </div>`;
            });
        }
        
        function testFindByLevelOnly() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = '<div class="alert alert-info">Testing...</div>';
            
            fetch('/fee-structures/find-by-level-only', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    institution_id: 1,
                    level: 'X'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">
                        <strong>✅ Success!</strong><br>
                        Level: ${data.data.level}<br>
                        Biaya: Rp ${Number(data.data.monthly_amount).toLocaleString('id-ID')}/bulan
                    </div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-warning">
                        <strong>⚠️ Response:</strong> ${data.message}
                    </div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>❌ Error:</strong> ${error.message}
                </div>`;
            });
        }
    </script>
</body>
</html>
