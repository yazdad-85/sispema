@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Siswa Baru</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('students.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nis">NIS <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nis') is-invalid @enderror" 
                                           id="nis" name="nis" value="{{ old('nis') }}" required>
                                    @error('nis')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="institution_id">Lembaga <span class="text-danger">*</span></label>
                                    <select class="form-control @error('institution_id') is-invalid @enderror" 
                                            id="institution_id" name="institution_id" required>
                                        <option value="">Pilih Lembaga</option>
                                        @foreach($institutions as $institution)
                                            <option value="{{ $institution->id }}" 
                                                    {{ old('institution_id') == $institution->id ? 'selected' : '' }}>
                                                {{ $institution->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('institution_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year_id">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select class="form-control @error('academic_year_id') is-invalid @enderror" 
                                            id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Pilih Tahun Ajaran</option>
                                        @foreach($academicYears as $academicYear)
                                            <option value="{{ $academicYear->id }}" 
                                                    {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                                {{ $academicYear->year_start }}-{{ $academicYear->year_end }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_id">Kelas <span class="text-danger">*</span></label>
                                    <select class="form-control @error('class_id') is-invalid @enderror" 
                                            id="class_id" name="class_id" required>
                                        <option value="">Pilih Kelas</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" 
                                                    {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->class_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="scholarship_category_id">Kategori Beasiswa</label>
                                    <select class="form-control @error('scholarship_category_id') is-invalid @enderror" 
                                            id="scholarship_category_id" name="scholarship_category_id">
                                        <option value="">Pilih Kategori Beasiswa</option>
                                        @foreach($scholarshipCategories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ old('scholarship_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('scholarship_category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="parent_name">Nama Orang Tua</label>
                                    <input type="text" class="form-control @error('parent_name') is-invalid @enderror" 
                                           id="parent_name" name="parent_name" value="{{ old('parent_name') }}">
                                    @error('parent_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="parent_phone">No. HP Orang Tua</label>
                                    <input type="text" class="form-control @error('parent_phone') is-invalid @enderror" 
                                           id="parent_phone" name="parent_phone" value="{{ old('parent_phone') }}">
                                    @error('parent_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Alamat</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="previous_debt">Tagihan Sebelumnya</label>
                                    <input type="number" class="form-control @error('previous_debt') is-invalid @enderror" 
                                           id="previous_debt" name="previous_debt" value="{{ old('previous_debt', 0) }}" 
                                           placeholder="0" min="0" step="1000">
                                    <small class="form-text text-muted">Masukkan jumlah tunggakan dari tahun sebelumnya (jika ada)</small>
                                    @error('previous_debt')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="previous_debt_year">Tahun Tagihan Sebelumnya</label>
                                    <input type="text" class="form-control @error('previous_debt_year') is-invalid @enderror" 
                                           id="previous_debt_year" name="previous_debt_year" value="{{ old('previous_debt_year') }}" 
                                           placeholder="2024" maxlength="4" pattern="[0-9]{4}">
                                    <small class="form-text text-muted">Tahun tunggakan (contoh: 2024)</small>
                                    @error('previous_debt_year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('students.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Kelas dinamis berdasarkan Lembaga
(function(){
    const institutionSelect = document.getElementById('institution_id');
    const classSelect = document.getElementById('class_id');
    const selectedClass = '{{ old('class_id') }}';

    function setPlaceholder(text){
        classSelect.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = text || 'Pilih Kelas';
        classSelect.appendChild(opt);
    }

    async function loadClasses(institutionId, preselectId){
        if(!institutionId){ setPlaceholder('Pilih Kelas'); return; }
        setPlaceholder('Memuat...');
        try{
            const res = await fetch(`{{ route('api.institutions.classes', ['institution' => 'ID_PLACEHOLDER']) }}`.replace('ID_PLACEHOLDER', institutionId));
            const data = await res.json();
            classSelect.innerHTML = '';
            if(data.classes && data.classes.length){
                const optDefault = document.createElement('option');
                optDefault.value = '';
                optDefault.textContent = 'Pilih Kelas';
                classSelect.appendChild(optDefault);
                data.classes.forEach(function(cls){
                    const opt = document.createElement('option');
                    opt.value = cls.id;
                    opt.textContent = cls.class_name;
                    if(preselectId && String(preselectId) === String(cls.id)) opt.selected = true;
                    classSelect.appendChild(opt);
                });
            } else {
                setPlaceholder('Tidak ada kelas');
                classSelect.firstChild.disabled = true;
            }
        }catch(e){
            console.error('Gagal memuat kelas:', e);
            setPlaceholder('Gagal memuat kelas');
            classSelect.firstChild.disabled = true;
        }
    }

    institutionSelect.addEventListener('change', function(){
        loadClasses(this.value, null);
    });

    if(institutionSelect.value){
        loadClasses(institutionSelect.value, selectedClass);
    }
})();
</script>
@endsection
