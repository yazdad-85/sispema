@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>Rencana Kegiatan
                    </h3>
                    <a href="{{ route('activity-plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tambah Rencana
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="academic_year_id" class="form-label">Tahun Ajaran</label>
                                <select class="form-select" id="academic_year_id" name="academic_year_id">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_start }}/{{ $year->year_end }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Semua Kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="institution_id" class="form-label">Lembaga</label>
                                <select class="form-select" id="institution_id" name="institution_id">
                                    <option value="">Semua Lembaga</option>
                                    @php
                                        $institutions = \App\Models\Institution::where('is_active', true)->get();
                                    @endphp
                                    @foreach($institutions as $institution)
                                        <option value="{{ $institution->id }}" {{ request('institution_id') == $institution->id ? 'selected' : '' }}>
                                            {{ $institution->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="level" class="form-label">Level</label>
                                <select class="form-select" id="level" name="level">
                                    <option value="">Semua Level</option>
                                    <option value="VII" {{ request('level') == 'VII' ? 'selected' : '' }}>VII</option>
                                    <option value="VIII" {{ request('level') == 'VIII' ? 'selected' : '' }}>VIII</option>
                                    <option value="IX" {{ request('level') == 'IX' ? 'selected' : '' }}>IX</option>
                                    <option value="X" {{ request('level') == 'X' ? 'selected' : '' }}>X</option>
                                    <option value="XI" {{ request('level') == 'XI' ? 'selected' : '' }}>XI</option>
                                    <option value="XII" {{ request('level') == 'XII' ? 'selected' : '' }}>XII</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="per_page" class="form-label">Per Halaman</label>
                                <select class="form-select" id="per_page" name="per_page">
                                    <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="{{ route('activity-plans.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kegiatan</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Lembaga</th>
                                    <th>Level</th>
                                    <th>Kategori</th>
                                    <th>Budget</th>
                                    <th>Realisasi</th>
                                    <th>Sisa</th>
                                    <th>Progress</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activityPlans as $index => $plan)
                                <tr>
                                    <td>{{ $activityPlans->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $plan->name }}</strong>
                                        @if($plan->description)
                                            <br><small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $plan->academicYear->year_start }}/{{ $plan->academicYear->year_end }}</td>
                                    <td>
                                        @if($plan->institution)
                                            <span class="badge bg-info">{{ $plan->institution->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->level)
                                            <span class="badge bg-secondary">{{ $plan->level }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $plan->category->type === 'pemasukan' ? 'bg-success' : 'bg-warning' }}">
                                            {{ $plan->category->name }}
                                        </span>
                                    </td>
                                    <td>Rp {{ number_format($plan->budget_amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($plan->total_realization, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="{{ $plan->remaining_budget >= 0 ? 'text-success' : 'text-danger' }}">
                                            Rp {{ number_format($plan->remaining_budget, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 100px;">
                                            <div class="progress-bar {{ $plan->realization_percentage > 100 ? 'bg-danger' : ($plan->realization_percentage > 80 ? 'bg-warning' : 'bg-success') }}" 
                                                 role="progressbar" style="width: {{ min($plan->realization_percentage, 100) }}%">
                                                {{ number_format($plan->realization_percentage, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('activity-plans.show', $plan) }}" class="btn btn-sm btn-outline-info" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('activity-plans.edit', $plan) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('activity-plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rencana kegiatan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada rencana kegiatan</p>
                                        <a href="{{ route('activity-plans.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Tambah Rencana Pertama
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($activityPlans->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Menampilkan {{ $activityPlans->firstItem() }} sampai {{ $activityPlans->lastItem() }} 
                                dari {{ $activityPlans->total() }} data
                            </div>
                            <div>
                                {{ $activityPlans->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
