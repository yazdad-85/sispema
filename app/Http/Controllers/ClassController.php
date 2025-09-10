<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Institution;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $institutions = null;
        
        // Get institutions for filter dropdown (role aware)
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $institutions = Institution::orderBy('name')->get();
        } elseif ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            $institutions = $user->institutions()->orderBy('name')->get();
        } else {
            $institutions = Institution::orderBy('name')->get();
        }
        
        // Build query with filters
        $query = ClassModel::with(['institution', 'academicYear']);
        
        // Apply institution filter
        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        } elseif ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            // Staff: limit to mapped institutions
            $allowed = $user->institutions()->pluck('institutions.id');
            if ($allowed->isNotEmpty()) {
                $query->whereIn('institution_id', $allowed);
            }
            // If staff has no institution mapping, show all data (no filter applied)
        }
        
        // Apply status filter (optional - keep for future use)
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Apply academic year filter
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('class_name', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%");
            });
        }
        
        // Get paginated results
        $classes = $query->orderBy('grade_level')
                        ->orderBy('class_name')
                        ->paginate(10)
                        ->withQueryString();
        
        // Load academic years for filter dropdown
        $academicYears = AcademicYear::orderBy('year_start', 'desc')->get();

        return view('classes.index', compact('classes', 'institutions', 'academicYears'));
    }

    public function create()
    {
        $user = Auth::user();
        $institutions = null;
        $academicYears = AcademicYear::all();
        
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $institutions = Institution::orderBy('name')->get();
        } elseif ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            $institutions = $user->institutions()->orderBy('name')->get();
        } else {
            $institutions = Institution::orderBy('name')->get();
        }
        
        return view('classes.create', compact('institutions', 'academicYears'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:50',
            'institution_id' => 'required|exists:institutions,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'capacity' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        // Pastikan field level terisi otomatis bila tidak dikirim dari form
        if (empty($data['level'])) {
            $data['level'] = ClassModel::getLevelFromClassName($data['class_name']);
        }

        ClassModel::create($data);

        return redirect()->route('classes.index')->with('success', 'Kelas berhasil ditambahkan');
    }

    public function show(ClassModel $class)
    {
        $class->load(['institution', 'academicYear']);
        return view('classes.show', compact('class'));
    }

    public function edit(ClassModel $class)
    {
        $user = Auth::user();
        $institutions = null;
        $academicYears = AcademicYear::all();
        
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $institutions = Institution::orderBy('name')->get();
        } elseif ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            $institutions = $user->institutions()->orderBy('name')->get();
        } else {
            $institutions = Institution::orderBy('name')->get();
        }
        
        return view('classes.edit', compact('class', 'institutions', 'academicYears'));
    }

    public function update(Request $request, ClassModel $class)
    {
        $request->validate([
            'class_name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:50',
            'institution_id' => 'required|exists:institutions,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'capacity' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        if (empty($data['level'])) {
            $data['level'] = ClassModel::getLevelFromClassName($data['class_name']);
        }

        $class->update($data);

        return redirect()->route('classes.index')->with('success', 'Data kelas berhasil diperbarui');
    }

    public function destroy(ClassModel $class)
    {
        $class->delete();

        return redirect()->route('classes.index')->with('success', 'Kelas berhasil dihapus');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048'
        ]);

        try {
            $file = $request->file('file');
            \Log::info('Classes import started', [
                'user_id' => auth()->id(),
                'original_name' => $file ? $file->getClientOriginalName() : null,
                'mime' => $file ? $file->getClientMimeType() : null,
                'size' => $file ? $file->getSize() : null,
            ]);
            $inputFileName = $file->getPathname();
            
            // Load Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            \Log::info('Classes import file loaded', [
                'total_rows_in_sheet' => is_array($rows) ? count($rows) : 0,
            ]);
            
            $errors = [];
            $imported = 0;
            
            // Skip title and header rows (rows 1-3)
            $dataRows = array_slice($rows, 3);
            
            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 4; // Actual row number in Excel
                
                // Skip empty rows
                if (empty(array_filter($row))) continue;
                
                try {
                    // Validate data
                    if (count($row) < 6) {
                        $errors[] = "Baris {$rowNumber}: Data tidak lengkap";
                        continue;
                    }
                    
                    $namaKelas = trim($row[0]);
                    $jenjang = trim($row[1]);
                    $lembaga = trim($row[2]);
                    $tahunAjaran = trim($row[3]);
                    $kapasitas = (int) $row[4];
                    $status = trim($row[5]);

                    // Skip if nama kelas is empty
                    if (empty($namaKelas)) continue;

                    // Find institution by ID
                    $institutionId = (int) $lembaga;
                    $institution = Institution::find($institutionId);
                    if (!$institution) {
                        $errors[] = "Baris {$rowNumber}: Lembaga ID '{$lembaga}' tidak ditemukan";
                        \Log::warning('Classes import: institution not found', [
                            'row' => $rowNumber,
                            'institution_id' => $institutionId,
                        ]);
                        continue;
                    }

                    // Find academic year by ID
                    $academicYearId = (int) $tahunAjaran;
                    $academicYear = AcademicYear::find($academicYearId);
                    if (!$academicYear) {
                        $errors[] = "Baris {$rowNumber}: Tahun ajaran ID '{$tahunAjaran}' tidak ditemukan";
                        \Log::warning('Classes import: academic year not found', [
                            'row' => $rowNumber,
                            'academic_year_id' => $academicYearId,
                        ]);
                        continue;
                    }

                    // Check if class already exists
                    $existingClass = ClassModel::where('class_name', $namaKelas)
                                            ->where('institution_id', $institution->id)
                                            ->where('academic_year_id', $academicYear->id)
                                            ->first();
                    
                    if ($existingClass) {
                        $errors[] = "Baris {$rowNumber}: Kelas '{$namaKelas}' sudah ada di lembaga dan tahun ajaran yang sama";
                        \Log::info('Classes import: duplicate skipped', [
                            'row' => $rowNumber,
                            'class_name' => $namaKelas,
                            'institution_id' => $institution->id,
                            'academic_year_id' => $academicYear->id,
                        ]);
                        continue;
                    }

                    // Create new class
                    ClassModel::create([
                        'class_name' => $namaKelas,
                        'grade_level' => $jenjang,
                        'level' => ClassModel::getLevelFromClassName($namaKelas),
                        'institution_id' => $institution->id,
                        'academic_year_id' => $academicYear->id,
                        'capacity' => $kapasitas,
                        'is_active' => strtolower($status) === 'aktif' ? true : false,
                    ]);
                    
                    $imported++;
                    \Log::info('Classes import: created', [
                        'row' => $rowNumber,
                        'class_name' => $namaKelas,
                        'institution_id' => $institution->id,
                        'academic_year_id' => $academicYear->id,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: Error - " . $e->getMessage();
                    \Log::error('Classes import: exception on row', [
                        'row' => $rowNumber,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
            
            if (!empty($errors)) {
                \Log::warning('Classes import finished with errors', [
                    'imported' => $imported,
                    'errors_count' => count($errors),
                ]);
                return redirect()->route('classes.index')
                    ->with('error', 'Import selesai dengan beberapa error: ' . implode(', ', $errors));
            }

            \Log::info('Classes import finished', [
                'imported' => $imported,
                'errors_count' => 0,
            ]);
            return redirect()->route('classes.index')
                ->with('success', "Data kelas berhasil diimport! Total: {$imported} data");

        } catch (\Exception $e) {
            \Log::error('Classes import failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('classes.index')
                ->with('error', 'Error saat import: ' . $e->getMessage());
        }
    }

    public function exportTemplate()
    {
        // Debug logging
        \Log::info('Export template called', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'no user',
            'session_id' => session()->getId(),
            'url' => request()->url()
        ]);
        
        // Get sample data
        $institutions = Institution::orderBy('name')->pluck('name')->toArray();
        $academicYears = AcademicYear::orderBy('year_start')->get()->map(function($ay){
            return $ay->year_start . '/' . $ay->year_end;
        })->toArray();
        
        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA KELAS');
        $sheet->mergeCells('A1:F1');
        
        // Style title
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($titleStyle);
        
        // Set headers (gunakan ID untuk Lembaga dan Tahun Ajaran)
        $headers = ['Nama Kelas*', 'Jenjang*', 'Lembaga ID*', 'Tahun Ajaran ID*', 'Kapasitas*', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }
        
        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A3:F3')->applyFromArray($headerStyle);
        
        // Sample data (biarkan kolom ID kosong agar diisi berdasarkan Data Referensi)
        $sampleData = [
            ['X IPA 1', 'SMA', '', '', '40', 'Aktif'],
            ['X IPA 2', 'SMA', '', '', '35', 'Aktif'],
            ['XI IPA 1', 'SMA', '', '', '38', 'Aktif']
        ];
        
        $row = 4;
        foreach ($sampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Style sample data
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A4:F6')->applyFromArray($dataStyle);
        
        // Add notes ringkas di sheet utama
        $notesRow = $row + 2;
        $sheet->setCellValue('A' . $notesRow, 'CATATAN: Isi Lembaga ID dan Tahun Ajaran ID sesuai sheet Data Referensi.');
        $sheet->getStyle('A' . $notesRow)->getFont()->setBold(true);
        
        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Tambahkan Sheet Petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->setCellValue('A1', 'PETUNJUK IMPORT DATA KELAS');
        $guide->getStyle('A1')->getFont()->setBold(true);
        $guideRows = [
            '',
            '1) Gunakan file .xlsx dan jangan ubah header kolom.',
            '2) Kolom bertanda * wajib diisi.',
            '3) Isi Lembaga ID dan Tahun Ajaran ID menggunakan referensi pada sheet Data Referensi.',
            '4) Jenjang contoh: MI, SD, MTs, SMP, MA, SMA, SMK.',
            '5) Status: Aktif atau Tidak Aktif.',
        ];
        $r = 2;
        foreach ($guideRows as $gr) {
            $guide->setCellValue('A' . $r, $gr);
            $r++;
        }
        $guide->getColumnDimension('A')->setWidth(100);

        // Tambahkan Sheet Data Referensi (ID & Nama)
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Data Referensi');
        // Lembaga
        $ref->setCellValue('A1', 'LEMBAGA');
        $ref->setCellValue('A2', 'ID');
        $ref->setCellValue('B2', 'Nama');
        $rowRef = 3;
        $allInstitutions = Institution::where('is_active', true)->orderBy('name')->get(['id','name']);
        foreach ($allInstitutions as $inst) {
            $ref->setCellValue('A' . $rowRef, $inst->id);
            $ref->setCellValue('B' . $rowRef, $inst->name);
            $rowRef++;
        }
        // Tahun Ajaran
        $ref->setCellValue('D1', 'TAHUN AJARAN');
        $ref->setCellValue('D2', 'ID');
        $ref->setCellValue('E2', 'Tahun');
        $rowYear = 3;
        $allYears = AcademicYear::where('status', 'active')->orderBy('year_start')->get(['id','year_start','year_end']);
        foreach ($allYears as $ay) {
            $ref->setCellValue('D' . $rowYear, $ay->id);
            $ref->setCellValue('E' . $rowYear, $ay->year_start . '/' . $ay->year_end);
            $rowYear++;
        }
        foreach (['A','B','D','E'] as $c) { $ref->getColumnDimension($c)->setAutoSize(true); }

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        $filename = 'template_kelas.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Output file
        $writer->save('php://output');
        exit;
    }
}
