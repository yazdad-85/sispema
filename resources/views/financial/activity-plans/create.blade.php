@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-plus me-2"></i>Tambah Rencana Kegiatan
                    </h3>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('activity-plans.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="academic_year_id" class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Pilih Tahun Ajaran</option>
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                                {{ $year->year_start }}/{{ $year->year_end }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }} ({{ ucfirst($category->type) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Perhitungan Budget -->
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Perhitungan Budget</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="unit_price" class="form-label">Harga Satuan <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control @error('unit_price') is-invalid @enderror" 
                                                       id="unit_price" name="unit_price" value="{{ old('unit_price') }}" 
                                                       min="0" step="0.01" required>
                                            </div>
                                            @error('unit_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="equivalent_1" class="form-label">Ekuivalen 1</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('equivalent_1') is-invalid @enderror" 
                                                       id="equivalent_1" name="equivalent_1" value="{{ old('equivalent_1', 1) }}" 
                                                       min="0" step="0.01">
                                                <input type="text" class="form-control" id="unit_1" name="unit_1" 
                                                       value="{{ old('unit_1', 'pax') }}" placeholder="Unit">
                                            </div>
                                            @error('equivalent_1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="equivalent_2" class="form-label">Ekuivalen 2</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('equivalent_2') is-invalid @enderror" 
                                                       id="equivalent_2" name="equivalent_2" value="{{ old('equivalent_2') }}" 
                                                       min="0" step="0.01">
                                                <input type="text" class="form-control" id="unit_2" name="unit_2" 
                                                       value="{{ old('unit_2', 'orang') }}" placeholder="Unit">
                                            </div>
                                            @error('equivalent_2')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="equivalent_3" class="form-label">Ekuivalen 3</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('equivalent_3') is-invalid @enderror" 
                                                       id="equivalent_3" name="equivalent_3" value="{{ old('equivalent_3') }}" 
                                                       min="0" step="0.01">
                                                <input type="text" class="form-control" id="unit_3" name="unit_3" 
                                                       value="{{ old('unit_3', 'kegiatan') }}" placeholder="Unit">
                                            </div>
                                            @error('equivalent_3')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hasil Perhitungan -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="alert alert-info">
                                            <h6 class="mb-2">Rumus Perhitungan:</h6>
                                            <p class="mb-1" id="calculation-formula">Harga Satuan × Ekuivalen 1 × Ekuivalen 2 × Ekuivalen 3</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-success">
                                            <h6 class="mb-2">Total Budget:</h6>
                                            <h4 class="mb-0" id="total-budget">Rp 0</h4>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden field untuk budget_amount -->
                                <input type="hidden" id="budget_amount" name="budget_amount" value="{{ old('budget_amount') }}">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('activity-plans.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Set minimum end date when start date changes
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            endDateInput.min = this.value;
        }
    });
    
    // Set maximum start date when end date changes
    endDateInput.addEventListener('change', function() {
        if (this.value) {
            startDateInput.max = this.value;
        }
    });
    
    // Budget calculation
    const unitPriceInput = document.getElementById('unit_price');
    const equivalent1Input = document.getElementById('equivalent_1');
    const equivalent2Input = document.getElementById('equivalent_2');
    const equivalent3Input = document.getElementById('equivalent_3');
    const unit1Input = document.getElementById('unit_1');
    const unit2Input = document.getElementById('unit_2');
    const unit3Input = document.getElementById('unit_3');
    const budgetAmountInput = document.getElementById('budget_amount');
    const totalBudgetDisplay = document.getElementById('total-budget');
    const calculationFormula = document.getElementById('calculation-formula');
    
    function calculateBudget() {
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const eq1 = parseFloat(equivalent1Input.value) || 0;
        const eq2 = parseFloat(equivalent2Input.value) || 0;
        const eq3 = parseFloat(equivalent3Input.value) || 0;
        
        // Calculate total
        let total = unitPrice;
        if (eq1 > 0) total *= eq1;
        if (eq2 > 0) total *= eq2;
        if (eq3 > 0) total *= eq3;
        
        // Update display
        totalBudgetDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
        budgetAmountInput.value = total;
        
        // Update formula display
        let formula = 'Harga Satuan';
        if (eq1 > 0) formula += ` × ${Math.floor(eq1)} ${unit1Input.value}`;
        if (eq2 > 0) formula += ` × ${Math.floor(eq2)} ${unit2Input.value}`;
        if (eq3 > 0) formula += ` × ${Math.floor(eq3)} ${unit3Input.value}`;
        calculationFormula.textContent = formula;
    }
    
    // Add event listeners
    [unitPriceInput, equivalent1Input, equivalent2Input, equivalent3Input, unit1Input, unit2Input, unit3Input].forEach(input => {
        input.addEventListener('input', calculateBudget);
    });
    
    // Initial calculation
    calculateBudget();
});
</script>
@endsection
