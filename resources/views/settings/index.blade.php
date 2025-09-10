@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-cog me-2"></i>Pengaturan Aplikasi</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row">
                        @if(auth()->user()->role === 'admin_pusat' || auth()->user()->role === 'super_admin')
                        <!-- Logo Settings -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-image me-2"></i>Logo Aplikasi</h5>
                                </div>
                                <div class="card-body text-center">
                                    @if($institution && $institution->logo)
                                        <img src="{{ Storage::url($institution->logo) }}" 
                                             alt="Logo Lembaga" 
                                             class="img-fluid mb-3" 
                                             style="max-width: 200px; max-height: 200px;">
                                    @else
                                        <div class="bg-light border rounded p-5 mb-3">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                            <p class="text-muted mt-2">Belum ada logo</p>
                                        </div>
                                    @endif
                                    
                                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="logo" class="form-label">Upload Logo Baru</label>
                                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                            <div class="form-text">
                                                Format: JPG, PNG, GIF. Maksimal 2MB.
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Update Logo
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Staff/Admin Personal Info -->
                        <div class="{{ auth()->user()->role === 'admin_pusat' || auth()->user()->role === 'super_admin' ? 'col-md-6' : 'col-md-12' }}">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-user me-2"></i>Data Diri Staff/Admin</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('settings.update') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="staff_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="staff_name" name="staff_name" 
                                                   value="{{ auth()->user()->name ?? '' }}" required>
                                            <div class="form-text">Nama ini akan muncul di kwitansi pembayaran</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="staff_role" class="form-label">Jabatan/Role</label>
                                            <input type="text" class="form-control" id="staff_role" name="staff_role" 
                                                   value="{{ auth()->user()->role ?? '' }}" readonly>
                                            <div class="form-text">Role otomatis dari sistem</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="staff_email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="staff_email" name="staff_email" 
                                                   value="{{ auth()->user()->email ?? '' }}" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="staff_phone" class="form-label">Telepon</label>
                                            <input type="text" class="form-control" id="staff_phone" name="staff_phone" 
                                                   value="{{ auth()->user()->phone ?? '' }}" placeholder="08123456789">
                                            <div class="form-text">Nomor telepon untuk kontak darurat</div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-lock me-2"></i>Ubah Password</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('settings.change-password') }}" method="POST">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label">Password Lama <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                                           id="current_password" name="current_password" required>
                                                    @error('current_password')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                                           id="new_password" name="new_password" required minlength="8">
                                                    @error('new_password')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <div class="form-text">Minimal 8 karakter</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control @error('new_password_confirmation') is-invalid @enderror" 
                                                           id="new_password_confirmation" name="new_password_confirmation" required>
                                                    @error('new_password_confirmation')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">&nbsp;</label>
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-warning">
                                                            <i class="fas fa-key me-2"></i>Ubah Password
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->role === 'admin_pusat' || auth()->user()->role === 'super_admin')
                    <!-- App Settings -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-palette me-2"></i>Pengaturan Aplikasi</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('settings.app') }}" method="POST">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="app_name" class="form-label">Nama Aplikasi</label>
                                                    <input type="text" class="form-control" id="app_name" name="app_name" 
                                                           value="{{ $appSettings->app_name ?? 'SISPEMA YASMU' }}" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="app_city" class="form-label">Kota</label>
                                                    <input type="text" class="form-control" id="app_city" name="app_city" 
                                                           value="{{ $appSettings->app_city ?? 'Kota' }}" required>
                                                    <div class="form-text">Kota yang akan muncul di laporan PDF</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="app_description" class="form-label">Deskripsi Aplikasi</label>
                                                    <textarea class="form-control" id="app_description" name="app_description" rows="3">{{ $appSettings->app_description ?? 'Sistem Pembayaran Akademik Yayasan Mu\'allimin Mu\'allimat YASMU' }}</textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="primary_color" class="form-label">Warna Utama</label>
                                                    <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" 
                                                           value="{{ $appSettings->primary_color ?? '#2563eb' }}" title="Pilih warna utama">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="secondary_color" class="form-label">Warna Sekunder</label>
                                                    <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" 
                                                           value="{{ $appSettings->secondary_color ?? '#1e40af' }}" title="Pilih warna sekunder">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Simpan Pengaturan Aplikasi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Versi Aplikasi:</strong></td>
                                                    <td>1.0.0</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Framework:</strong></td>
                                                    <td>Laravel 8</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>PHP Version:</strong></td>
                                                    <td>{{ PHP_VERSION }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Database:</strong></td>
                                                    <td>MySQL</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');
    
    function validatePasswordMatch() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Show/hide password functionality
    function togglePasswordVisibility(inputId, buttonId) {
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        
        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = 'password';
            button.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }
    
    // Add show/hide password buttons
    const passwordFields = ['current_password', 'new_password', 'new_password_confirmation'];
    passwordFields.forEach(function(fieldId) {
        const field = document.getElementById(fieldId);
        const wrapper = field.parentNode;
        
        // Create button
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2';
        button.style.zIndex = '10';
        button.innerHTML = '<i class="fas fa-eye"></i>';
        button.onclick = function() {
            togglePasswordVisibility(fieldId, button.id);
        };
        button.id = fieldId + '_toggle';
        
        // Make wrapper relative
        wrapper.style.position = 'relative';
        
        // Add button
        wrapper.appendChild(button);
    });
});
</script>
@endpush
