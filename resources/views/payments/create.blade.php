@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Pembayaran Baru</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.store') }}" method="POST" id="payment-form">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="institution_id">Lembaga <span class="text-danger">*</span></label>
                                    <select class="form-control" id="institution_id" required>
                                        <option value="">Pilih Lembaga</option>
                                        @foreach(($institutions ?? \App\Models\Institution::all()) as $inst)
                                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="student_search">Siswa (ketik NIS)</label>
                                    <input type="text" id="student_search" class="form-control" placeholder="Ketik NIS untuk mencari" autocomplete="off">
                                    <div id="student_suggestions" class="list-group" style="position:absolute; z-index:1000; width:100%; display:none;"></div>
                                    <input type="hidden" id="student_id_hidden" name="student_id" value="{{ old('student_id') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="class_display">Kelas</label>
                                    <input type="text" id="class_display" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_record_id">Tagihan Siswa <span class="text-danger">*</span></label>
                                    <select class="form-control @error('billing_record_id') is-invalid @enderror" 
                                            id="billing_record_id" name="billing_record_id" required>
                                        <option value="">Pilih Tagihan</option>
                                    </select>
                                    @error('billing_record_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Siswa</label>
                                    <input type="text" id="student_name_display" class="form-control" value="" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date">Tanggal & Waktu Pembayaran <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('payment_date') is-invalid @enderror" 
                                           id="payment_date" name="payment_date" 
                                           value="{{ old('payment_date', now()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}" required>
                                    @error('payment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_method">Metode Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-control @error('payment_method') is-invalid @enderror" 
                                            id="payment_method" name="payment_method" required>
                                        <option value="">Pilih Metode</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                                        <option value="qris" {{ old('payment_method') == 'qris' ? 'selected' : '' }}>QRIS</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="total_amount">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('total_amount') is-invalid @enderror" 
                                           id="total_amount" name="total_amount" 
                                           value="{{ old('total_amount') }}" placeholder="Contoh: 300000" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Catatan</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            <small class="form-text text-muted">Catatan tambahan tentang pembayaran ini</small>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Billing Info Display -->
                        <div class="row mt-4" id="billing-info" style="display: none;">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">Informasi Tagihan</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Tagihan:</strong> <span id="billing-month">-</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Jumlah Tagihan:</strong> <span id="billing-amount">-</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Sisa Tagihan:</strong> <span id="billing-remaining">-</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Jatuh Tempo:</strong> <span id="billing-due-date">-</span>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Total Tagihan Tahun Ini:</strong> <span id="total-current-year">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Sisa Tahun Berjalan:</strong> <span id="outstanding-current">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Total Sisa Semua Tahun:</strong> <span id="outstanding-grand">-</span>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <strong>Kategori Beasiswa:</strong> <span id="scholarship-name">-</span>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Diskon Beasiswa:</strong> <span id="scholarship-discount">0%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Pembayaran
                            </button>
                            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
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
document.addEventListener('DOMContentLoaded', function() {
    const institutionSelect = document.getElementById('institution_id');
    const studentSearch = document.getElementById('student_search');
    const studentSuggest = document.getElementById('student_suggestions');
    const classDisplay = document.getElementById('class_display');
    const billingSelect = document.getElementById('billing_record_id');
    const billingInfo = document.getElementById('billing-info');
    const studentIdHidden = document.getElementById('student_id_hidden');
    const studentNameDisplay = document.getElementById('student_name_display');
    const paymentAmountInput = document.getElementById('total_amount');
    const paymentForm = document.getElementById('payment-form');
    let outstandingSummary = null; // cache ringkasan sisa (tahun berjalan & sebelumnya)
    let scholarshipPct = 0; // persentase diskon
    let effectiveBaseRemaining = 0; // sisa tahun berjalan setelah diskon
    
    // Debug form submission
    paymentForm.addEventListener('submit', function(e) {
        console.log('Form submitted!');
        
        // Format total_amount before submission (remove dots and convert to integer)
        const totalAmountInput = document.getElementById('total_amount');
        const rawValue = totalAmountInput.value.replace(/\./g, '');
        totalAmountInput.value = rawValue;
        
        console.log('Form data:', {
            student_id: studentIdHidden.value,
            billing_record_id: billingSelect.value,
            payment_date: document.getElementById('payment_date').value,
            total_amount: rawValue,
            payment_method: document.getElementById('payment_method').value,
            notes: document.getElementById('notes').value
        });
        
        // Check if required fields are filled
        if (!studentIdHidden.value) {
            e.preventDefault();
            alert('Pilih siswa terlebih dahulu!');
            return false;
        }
        
        if (!billingSelect.value) {
            e.preventDefault();
            alert('Pilih tagihan terlebih dahulu!');
            return false;
        }
        
        if (!rawValue) {
            e.preventDefault();
            alert('Isi jumlah pembayaran!');
            return false;
        }
        
        if (!document.getElementById('payment_method').value) {
            e.preventDefault();
            alert('Pilih metode pembayaran!');
            return false;
        }
        
        console.log('Form validation passed, submitting...');
    });

    // Format number with thousand separators
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Parse formatted number back to integer
    function parseFormattedNumber(formattedNum) {
        return parseInt(formattedNum.replace(/\./g, '')) || 0;
    }

    // Auto-format payment amount input
    paymentAmountInput.addEventListener('input', function() {
        let value = this.value.replace(/[^\d]/g, ''); // Remove non-digits
        
        if (value) {
            // Format with thousand separators
            this.value = formatNumber(value);
            
            // Update remaining balance when payment amount changes
            if (outstandingSummary) {
                const paymentAmount = parseFormattedNumber(this.value);
                const newRemaining = Math.max(0, effectiveBaseRemaining - paymentAmount);
                
                // Update the billing remaining balance
                document.getElementById('billing-remaining').textContent = `Rp ${newRemaining.toLocaleString('id-ID')}`;
                
                // Update the outstanding summary
                document.getElementById('outstanding-current').textContent = `Rp ${newRemaining.toLocaleString('id-ID')}`;
                
                // Update grand total (current year + previous years)
                const newGrandTotal = newRemaining + Number(outstandingSummary.total_previous_years || 0);
                document.getElementById('outstanding-grand').textContent = `Rp ${newGrandTotal.toLocaleString('id-ID')}`;
            }
        } else {
            // Reset to original values if payment amount is empty
            if (outstandingSummary) {
                document.getElementById('billing-remaining').textContent = `Rp ${effectiveBaseRemaining.toLocaleString('id-ID')}`;
                document.getElementById('outstanding-current').textContent = `Rp ${effectiveBaseRemaining.toLocaleString('id-ID')}`;
                const baseGrand = effectiveBaseRemaining + Number(outstandingSummary.total_previous_years || 0);
                document.getElementById('outstanding-grand').textContent = `Rp ${baseGrand.toLocaleString('id-ID')}`;
            }
        }
    });

    // Show billing info when billing record is selected
    billingSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            // set billing info if available
            if (selectedOption.dataset.billing) {
                const billing = JSON.parse(selectedOption.dataset.billing);
            
                document.getElementById('billing-month').textContent = billing.billing_month || '-';
                document.getElementById('billing-amount').textContent = `Rp ${Number(billing.amount||0).toLocaleString('id-ID')}`;
                document.getElementById('billing-remaining').textContent = `Rp ${Number(billing.remaining_balance||0).toLocaleString('id-ID')}`;
                document.getElementById('billing-due-date').textContent = billing.due_date ? new Date(billing.due_date).toLocaleDateString('id-ID') : '-';
                billingInfo.style.display = 'block';
            } else {
                billingInfo.style.display = 'none';
            }
        } else {
            billingInfo.style.display = 'none';
            studentIdHidden.value = '';
            classDisplay.value = '';
            studentNameDisplay.value = '';
        }
    });

    // Search students by NIS within institution
    let searchTimeout;
    function hideSuggest(){ studentSuggest.style.display='none'; studentSuggest.innerHTML=''; }
    studentSearch.addEventListener('input', function(){
        const instId = institutionSelect.value;
        const q = this.value.trim();
        hideSuggest();
        if(!instId || q.length < 1){ return; }
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(()=>{
            fetch(`/api/institutions/${instId}/students?query=${encodeURIComponent(q)}`)
              .then(r=>r.json())
              .then(data=>{
                  if(data.students && data.students.length){
                      studentSuggest.innerHTML = '';
                      data.students.forEach(s=>{
                          const a = document.createElement('a');
                          a.href = '#';
                          a.className = 'list-group-item list-group-item-action';
                          a.textContent = `${s.nis} - ${s.name}`;
                          a.addEventListener('click', function(e){
                              e.preventDefault();
                              studentIdHidden.value = s.id;
                              studentSearch.value = `${s.nis} - ${s.name}`;
                              classDisplay.value = s.class_name || '';
                              studentNameDisplay.value = s.name || '';
                              loadBillingsForStudent(s.id);
                              hideSuggest();
                          });
                          studentSuggest.appendChild(a);
                      });
                      studentSuggest.style.display = 'block';
                  }
              })
              .catch(()=>{});
        }, 250);
    });

    document.addEventListener('click', function(evt){
        if(!studentSuggest.contains(evt.target) && evt.target !== studentSearch){ hideSuggest(); }
    });

    function updateOutstandingSummary(studentId){
        fetch(`/api/students/${studentId}/outstanding-summary`)
          .then(r=>r.json())
          .then(sum=>{
            const fmt = n=>`Rp ${Number(n||0).toLocaleString('id-ID')}`;
            outstandingSummary = sum;
            
            document.getElementById('total-current-year').textContent = fmt(sum.total_current_year_amount);
            const yearly = Number(sum.total_current_year_amount || 0);
            const prevYears = Number(sum.total_previous_years || 0);
            const discountAmount = yearly * (scholarshipPct/100);
            effectiveBaseRemaining = Math.max(0, yearly - discountAmount);
            document.getElementById('outstanding-current').textContent = fmt(effectiveBaseRemaining);
            document.getElementById('outstanding-grand').textContent = fmt(effectiveBaseRemaining + prevYears);
          })
          .catch(()=>{});

        // Ambil info siswa untuk beasiswa
        fetch(`/api/institutions/${institutionSelect.value}/students?query=`) // reuse endpoint, lalu cari id
          .then(()=>{
            // fallback: fetch detail siswa dari server-side via simple JSON endpoint bisa ditambah nanti
          })
    }

    function loadBillingsForStudent(studentId){
        billingSelect.innerHTML = '<option value="">Pilih Tagihan</option>';
        fetch(`/api/students/${studentId}/billing-records`)
          .then(r=>r.json())
          .then(data=>{
            if(data.billing_records && data.billing_records.length){
                data.billing_records.forEach(b=>{
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = `${b.billing_month} - Sisa: Rp ${Number(b.remaining_balance||0).toLocaleString('id-ID')}`;
                    opt.dataset.billing = JSON.stringify(b);
                    billingSelect.appendChild(opt);
                });
                // Ambil beasiswa dulu, lalu update ringkasan agar konsisten
                fetch(`/api/students/${studentId}/scholarship`)
                  .then(r=>r.json())
                  .then(info=>{
                      document.getElementById('scholarship-name').textContent = info.name || '-';
                      scholarshipPct = Number(info.discount_percentage || 0);
                      document.getElementById('scholarship-discount').textContent = `${scholarshipPct}%`;
                      updateOutstandingSummary(studentId);
                  })
                  .catch(()=>{
                      scholarshipPct = 0;
                      document.getElementById('scholarship-name').textContent = '-';
                      document.getElementById('scholarship-discount').textContent = '0%';
                      updateOutstandingSummary(studentId);
                  });
                billingInfo.style.display = 'block';
            } else {
                const opt = document.createElement('option');
                opt.value='';
                opt.textContent='Tidak ada tagihan tersedia';
                opt.disabled = true;
                billingSelect.appendChild(opt);
            }
          })
          .catch(()=>{});
    }
});
</script>
@endsection
