@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-plus me-2"></i>Tambah Transaksi Manual
                    </h3>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('financial-reports.cash-book.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
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
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Keterangan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                           id="description" name="description" value="{{ old('description') }}" required>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="debit" class="form-label">Debit (Pengeluaran)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('debit') is-invalid @enderror" 
                                               id="debit" name="debit" value="{{ old('debit') }}" 
                                               min="0" step="0.01">
                                    </div>
                                    @error('debit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="credit" class="form-label">Kredit (Pemasukan)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('credit') is-invalid @enderror" 
                                               id="credit" name="credit" value="{{ old('credit') }}" 
                                               min="0" step="0.01">
                                    </div>
                                    @error('credit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan:</strong> Isi salah satu field (Debit atau Kredit), tidak keduanya. 
                            Debit untuk pengeluaran, Kredit untuk pemasukan.
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('financial-reports.cash-book') }}" class="btn btn-secondary">
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
    const debitInput = document.getElementById('debit');
    const creditInput = document.getElementById('credit');
    
    // Ensure only one field is filled at a time
    debitInput.addEventListener('input', function() {
        if (this.value > 0) {
            creditInput.value = '';
        }
    });
    
    creditInput.addEventListener('input', function() {
        if (this.value > 0) {
            debitInput.value = '';
        }
    });
});
</script>
@endsection
