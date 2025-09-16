# Perbaikan Filter Halaman Students

## Overview
Perbaikan filter di halaman `http://localhost:8000/students` untuk membuat urutan filter yang lebih logis dan fungsional.

## Perubahan yang Dibuat

### 1. ✅ **Urutan Filter Baru**:
**Sebelumnya:**
- Cari → Lembaga → Kelas → Tahun Ajaran

**Sesudahnya:**
- Cari → Lembaga → Tahun Ajaran → Kelas

### 2. ✅ **Logika Filter Berurutan**:
1. **User memilih Lembaga** → Filter tahun ajaran dan kelas
2. **User memilih Tahun Ajaran** → Filter kelas berdasarkan lembaga + tahun ajaran
3. **User memilih Kelas** → Kelas yang ditampilkan hanya yang ada di lembaga dan tahun ajaran yang dipilih

### 3. ✅ **JavaScript Dinamis**:
- **onchange="updateClasses()"** pada select Lembaga dan Tahun Ajaran
- **AJAX request** ke `/api/classes` untuk mendapatkan kelas secara dinamis
- **Loading state** saat mengambil data kelas
- **Error handling** jika gagal mengambil data

### 4. ✅ **API Endpoint**:
- **Route**: `GET /api/classes`
- **Parameters**: 
  - `institution_id` (optional)
  - `academic_year_id` (optional)
- **Response**: Array of classes dengan format:
```json
[
  {
    "id": 6,
    "class_name": "IX",
    "level": "IX",
    "institution_id": 1,
    "academic_year_id": 1
  }
]
```

### 5. ✅ **Controller Update**:
- **StudentController::index()** diperbaiki untuk mengirim kelas yang sudah difilter
- **Filter berdasarkan**: `is_active = true`, `is_graduated_class = false`
- **Urutan**: `orderBy('class_name')`

## File yang Dimodifikasi

### 1. **View**: `resources/views/students/index.blade.php`
- ✅ Memindahkan posisi Tahun Ajaran ke sebelah Lembaga
- ✅ Menambahkan `onchange="updateClasses()"` pada select Lembaga dan Tahun Ajaran
- ✅ Mengganti JavaScript dengan fungsi `updateClasses()`
- ✅ AJAX request ke API endpoint

### 2. **Controller**: `app/Http/Controllers/StudentController.php`
- ✅ Memperbaiki logika pengambilan kelas
- ✅ Filter kelas berdasarkan lembaga dan tahun ajaran yang dipilih
- ✅ Menambahkan filter `is_active` dan `is_graduated_class`

### 3. **API Route**: `routes/api.php`
- ✅ Memperbaiki response format untuk AJAX requests
- ✅ Return classes langsung untuk filter requests

## Cara Kerja

### 1. **User Memilih Lembaga**:
```javascript
// JavaScript akan memanggil updateClasses()
function updateClasses() {
    const institutionId = document.getElementById('institution_id').value;
    const academicYearId = document.getElementById('academic_year_id').value;
    
    // AJAX request ke /api/classes
    fetch(`/api/classes?institution_id=${institutionId}&academic_year_id=${academicYearId}`)
        .then(response => response.json())
        .then(data => {
            // Update select kelas dengan data baru
        });
}
```

### 2. **User Memilih Tahun Ajaran**:
- Sama seperti memilih lembaga
- Kelas akan diupdate berdasarkan lembaga + tahun ajaran yang dipilih

### 3. **Kelas yang Ditampilkan**:
- Hanya kelas yang ada di lembaga yang dipilih
- Hanya kelas yang ada di tahun ajaran yang dipilih
- Kelas yang aktif (`is_active = true`)
- Bukan kelas lulusan (`is_graduated_class = false`)

## Testing

### 1. **Test API Endpoint**:
```bash
curl "http://localhost:8000/api/classes?institution_id=1&academic_year_id=1"
```

### 2. **Test Halaman Web**:
1. Buka `http://localhost:8000/students`
2. Pilih Lembaga → Kelas akan terupdate
3. Pilih Tahun Ajaran → Kelas akan terupdate lagi
4. Pilih Kelas → Hanya kelas yang sesuai yang ditampilkan

## Keuntungan

### 1. ✅ **User Experience**:
- Filter yang lebih logis dan berurutan
- Kelas yang ditampilkan relevan dengan pilihan sebelumnya
- Loading state yang informatif

### 2. ✅ **Performance**:
- AJAX request yang efisien
- Tidak perlu reload halaman
- Data yang minimal dan relevan

### 3. ✅ **Maintainability**:
- Kode yang terstruktur dan mudah dipahami
- API endpoint yang reusable
- Error handling yang baik

## Troubleshooting

### 1. **Kelas Tidak Terupdate**:
- Periksa console browser untuk error JavaScript
- Periksa network tab untuk AJAX request
- Pastikan API endpoint `/api/classes` berfungsi

### 2. **API Error**:
- Periksa log Laravel untuk error
- Pastikan route API sudah terdaftar
- Periksa parameter yang dikirim

### 3. **JavaScript Error**:
- Periksa console browser
- Pastikan elemen dengan ID yang benar ada
- Periksa syntax JavaScript

## Future Enhancements

### 1. **Caching**:
- Cache hasil API untuk performa yang lebih baik
- Cache kelas berdasarkan lembaga dan tahun ajaran

### 2. **Search**:
- Tambahkan search pada dropdown kelas
- Autocomplete untuk kelas

### 3. **Validation**:
- Validasi pilihan yang tidak valid
- Error message yang lebih informatif
