@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Tahun Ajaran</h1>
        <a href="{{ route('academic-years.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Tahun Ajaran
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Tahun Ajaran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tahun Ajaran</th>
                            <th>Status</th>
                            <th>Tahun Aktif</th>
                            <th>Deskripsi</th>
                            <th>Total Siswa</th>
                            <th>Total Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($academicYears as $index => $academicYear)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $academicYear->name }}</strong>
                                @if($academicYear->is_current)
                                    <span class="badge badge-success ml-2">Aktif</span>
                                @endif
                            </td>
                            <td>
                                @if($academicYear->status === 'active')
                                    <span class="badge bg-success text-white">Aktif</span>
                                @else
                                    <span class="badge bg-danger text-white">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                @if($academicYear->is_current)
                                    <span class="badge bg-primary text-white">Tahun Aktif</span>
                                @else
                                    <span class="badge bg-secondary text-white">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>{{ $academicYear->description ?? '-' }}</td>
                            <td>{{ $academicYear->students_count ?? 0 }}</td>
                            <td>{{ $academicYear->classes_count ?? 0 }}</td>
                            <td>
                                <a href="{{ route('academic-years.show', $academicYear->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('academic-years.edit', $academicYear->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if(!$academicYear->is_current)
                                    <form action="{{ route('academic-years.set-current', $academicYear->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Set tahun ajaran ini sebagai tahun aktif?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('academic-years.destroy', $academicYear->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus tahun ajaran ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data tahun ajaran</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
