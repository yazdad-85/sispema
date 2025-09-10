@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Detail Kategori Beasiswa</h3>
                    <div>
                        <a href="{{ route('scholarship-categories.edit', $scholarshipCategory->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('scholarship-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Nama Kategori:</strong></td>
                                    <td>{{ $scholarshipCategory->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Persentase Diskon:</strong></td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $scholarshipCategory->discount_percentage }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $scholarshipCategory->is_active ? 'success' : 'danger' }}">
                                            {{ $scholarshipCategory->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Dibuat:</strong></td>
                                    <td>{{ $scholarshipCategory->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Terakhir Update:</strong></td>
                                    <td>{{ $scholarshipCategory->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah Siswa:</strong></td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ $scholarshipCategory->students->count() }} siswa
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Deskripsi</h5>
                            <p>{{ $scholarshipCategory->description ?? 'Tidak ada deskripsi' }}</p>
                        </div>
                    </div>

                    @if($scholarshipCategory->students->count() > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Daftar Siswa dengan Kategori Ini</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>NIS</th>
                                            <th>Nama</th>
                                            <th>Kelas</th>
                                            <th>Institusi</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($scholarshipCategory->students as $index => $student)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $student->nis }}</td>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $student->classRoom->class_name ?? '-' }}</td>
                                                <td>{{ $student->institution->name ?? '-' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'danger' }} text-dark">
                                                        {{ ucfirst($student->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
