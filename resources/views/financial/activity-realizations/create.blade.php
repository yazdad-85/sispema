@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-plus me-2"></i>Tambah Realisasi Kegiatan
                    </h3>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('activity-realizations.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="plan_id" class="form-label">Rencana Kegiatan <span class="text-danger">*</span></label>
                                    <select name="plan_id" id="plan_id" class="form-select @error('plan_id') is-invalid @enderror" required>
                                        <option value="">Pilih Rencana Kegiatan</option>
                                        @foreach($activityPlans as $plan)
                                            <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }} ({{ $plan->academicYear->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('plan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                           id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi Kegiatan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="transaction_type" class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                                    <select name="transaction_type" id="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="debit" {{ old('transaction_type') == 'debit' ? 'selected' : '' }}>Debit (Pengeluaran)</option>
                                        <option value="credit" {{ old('transaction_type') == 'credit' ? 'selected' : '' }}>Kredit (Pemasukan)</option>
                                    </select>
                                    @error('transaction_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
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
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="equivalent_1" class="form-label">Ekuivalen 1</label>
                                    <input type="number" class="form-control @error('equivalent_1') is-invalid @enderror" 
                                           id="equivalent_1" name="equivalent_1" value="{{ old('equivalent_1', 1) }}" 
                                           min="0" step="0.01">
                                    @error('equivalent_1')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="equivalent_2" class="form-label">Ekuivalen 2</label>
                                    <input type="number" class="form-control @error('equivalent_2') is-invalid @enderror" 
                                           id="equivalent_2" name="equivalent_2" value="{{ old('equivalent_2') }}" 
                                           min="0" step="0.01">
                                    @error('equivalent_2')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="equivalent_3" class="form-label">Ekuivalen 3</label>
                                    <input type="number" class="form-control @error('equivalent_3') is-invalid @enderror" 
                                           id="equivalent_3" name="equivalent_3" value="{{ old('equivalent_3') }}" 
                                           min="0" step="0.01">
                                    @error('equivalent_3')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Perhitungan Total -->
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Perhitungan Total</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="alert alert-info">
                                            <h6 class="mb-2">Rumus Perhitungan:</h6>
                                            <p class="mb-1" id="calculation-formula">Harga Satuan × Ekuivalen 1 × Ekuivalen 2 × Ekuivalen 3</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-success">
                                            <h6 class="mb-2">Total Nominal:</h6>
                                            <h4 class="mb-0" id="total-amount">Rp 0</h4>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden field untuk total_amount -->
                                <input type="hidden" id="total_amount" name="total_amount" value="{{ old('total_amount') }}">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="proof" class="form-label">Bukti Transaksi</label>
                                    <input type="file" class="form-control @error('proof') is-invalid @enderror" 
                                           id="proof" name="proof" accept="image/*,.pdf">
                                    <div class="form-text">Format yang didukung: JPG, PNG, PDF (Maksimal 2MB)</div>
                                    @error('proof')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('activity-realizations.index') }}" class="btn btn-secondary">
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
    const unitPriceInput = document.getElementById('unit_price');
    const equivalent1Input = document.getElementById('equivalent_1');
    const equivalent2Input = document.getElementById('equivalent_2');
    const equivalent3Input = document.getElementById('equivalent_3');
    const totalAmountInput = document.getElementById('total_amount');
    const totalAmountDisplay = document.getElementById('total-amount');
    const calculationFormula = document.getElementById('calculation-formula');
    
    function calculateTotal() {
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
        totalAmountDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
        totalAmountInput.value = total;
        
        // Update formula display
        let formula = 'Harga Satuan';
        if (eq1 > 0) formula += ` × ${Math.floor(eq1)}`;
        if (eq2 > 0) formula += ` × ${Math.floor(eq2)}`;
        if (eq3 > 0) formula += ` × ${Math.floor(eq3)}`;
        calculationFormula.textContent = formula;
    }
    
    // Add event listeners
    [unitPriceInput, equivalent1Input, equivalent2Input, equivalent3Input].forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Initial calculation
    calculateTotal();
});
</script>
@endsection
