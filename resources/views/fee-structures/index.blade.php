@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Struktur Biaya</h3>
                    <div>
                        <button type="button" class="btn btn-success mr-2" data-bs-toggle="modal" data-bs-target="#copyFeeStructureModal">
                            <i class="fas fa-copy"></i> Salin dari Tahun Sebelumnya
                        </button>
                        <a href="{{ route('fee-structures.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Struktur Biaya
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <form method="GET" action="{{ route('fee-structures.index') }}" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="institution_id" class="form-label">Lembaga</label>
                            <select class="form-control" id="institution_id" name="institution_id">
                                <option value="">Semua Lembaga</option>
                                @isset($institutions)
                                    @foreach($institutions as $institution)
                                        <option value="{{ $institution->id }}" {{ request('institution_id') == $institution->id ? 'selected' : '' }}>
                                            {{ $institution->name }}
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="academic_year_id" class="form-label">Tahun Ajaran</label>
                            <select class="form-control" id="academic_year_id" name="academic_year_id">
                                <option value="">Semua Tahun Ajaran</option>
                                @isset($academicYears)
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->year_start }}-{{ $ay->year_end }}
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                                <a href="{{ route('fee-structures.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                            </div>
                        </div>
                    </form>
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismissible="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Lembaga</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Level</th>
                                    <th>Biaya Tahunan</th>
                                    <th>Diskon Beasiswa</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feeStructures as $index => $feeStructure)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $feeStructure->institution->name ?? '-' }}</td>
                                        <td>{{ $feeStructure->academicYear->year_start ?? '' }}-{{ $feeStructure->academicYear->year_end ?? '' }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                {{ $feeStructure->class->safe_level ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($feeStructure->yearly_amount, 0, ',', '.') }}</td>
                                        <td>{{ $feeStructure->scholarship_discount ?? 0 }}%</td>
                                        <td>
                                            <span class="badge bg-{{ $feeStructure->is_active ? 'success' : 'danger' }} text-white">
                                                {{ $feeStructure->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('fee-structures.show', $feeStructure->id) }}" 
                                                   class="btn btn-sm btn-info" title="Lihat">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('fee-structures.edit', $feeStructure->id) }}" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('fee-structures.destroy', $feeStructure->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            title="Hapus" 
                                                            onclick="return confirm('Yakin ingin menghapus struktur biaya ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">Tidak ada data struktur biaya</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Salin Struktur Biaya dari Tahun Sebelumnya -->
<div class="modal fade" id="copyFeeStructureModal" tabindex="-1" aria-labelledby="copyFeeStructureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copyFeeStructureModalLabel">
                    <i class="fas fa-copy"></i> Salin Struktur Biaya dari Tahun Sebelumnya
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('fee-structures.copy-from-previous') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="copy_institution_id" class="form-label">Lembaga *</label>
                        <select class="form-control" id="copy_institution_id" name="institution_id" required>
                            <option value="">Pilih Lembaga</option>
                            @foreach(\App\Models\Institution::orderBy('name')->get() as $institution)
                                <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="source_academic_year_id" class="form-label">Tahun Ajaran Sumber *</label>
                        <select class="form-control" id="source_academic_year_id" name="source_academic_year_id" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach(\App\Models\AcademicYear::orderBy('year_start','desc')->get() as $academicYear)
                                <option value="{{ $academicYear->id }}">{{ $academicYear->year_start }}-{{ $academicYear->year_end }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="target_academic_year_id" class="form-label">Tahun Ajaran Target *</label>
                        <select class="form-control" id="target_academic_year_id" name="target_academic_year_id" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach(\App\Models\AcademicYear::orderBy('year_start','desc')->get() as $academicYear)
                                <option value="{{ $academicYear->id }}">{{ $academicYear->year_start }}-{{ $academicYear->year_end }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        Sistem akan menyalin semua struktur biaya per level dari tahun ajaran sumber ke tahun ajaran target hanya jika belum ada di target.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Salin Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
    
</div>

<!-- Modal: Buat Struktur Biaya Berdasarkan Level -->
<div class="modal fade" id="createForAllLevelsModal" tabindex="-1" aria-labelledby="createForAllLevelsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createForAllLevelsModalLabel">
                    <i class="fas fa-layer-group"></i> Buat Struktur Biaya Berdasarkan Level
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('fee-structures.create-for-all-levels') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="institution_id" class="form-label">Lembaga *</label>
                        <select class="form-control" id="institution_id" name="institution_id" required>
                            <option value="">Pilih Lembaga</option>
                            @foreach(\App\Models\Institution::all() as $institution)
                                <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="academic_year_id" class="form-label">Tahun Ajaran *</label>
                        <select class="form-control" id="academic_year_id" name="academic_year_id" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach(\App\Models\AcademicYear::all() as $academicYear)
                                <option value="{{ $academicYear->id }}">{{ $academicYear->year_start }}-{{ $academicYear->year_end }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="base_monthly_amount" class="form-label">Biaya Bulanan Dasar (Rp) *</label>
                        <input type="number" class="form-control" id="base_monthly_amount" name="base_monthly_amount" 
                               placeholder="500000" required min="0">
                        <small class="form-text text-muted">
                            Biaya untuk tingkat 1 (VII/X). Tingkat 2 (+10%), Tingkat 3 (+20%)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Deskripsi struktur biaya berdasarkan level"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Sistem akan membuat struktur biaya untuk:</h6>
                        <ul class="mb-0">
                            <li><strong>VII, X:</strong> Biaya dasar (Tingkat 1)</li>
                            <li><strong>VIII, XI:</strong> Biaya dasar + 10% (Tingkat 2)</li>
                            <li><strong>IX, XII:</strong> Biaya dasar + 20% (Tingkat 3)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Buat Semua Level
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
