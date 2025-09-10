<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API for classes by institution
Route::get('/classes', function (Request $request) {
    $query = \App\Models\ClassModel::with('institution', 'academicYear')
        ->where('is_active', true)
        ->where('is_graduated_class', false);
    
    $nextLevel = null;
    
    if ($request->has('student_id')) {
        // Get next level for student
        $student = \App\Models\Student::with('classRoom')->find($request->student_id);
        if ($student && $student->classRoom) {
            $currentLevel = $student->classRoom->level;
            
            $levelMap = [
                'VII' => 'VIII',
                'VIII' => 'IX',
                'IX' => null,
                'X' => 'XI',
                'XI' => 'XII',
                'XII' => null
            ];
            
            $nextLevel = $levelMap[$currentLevel] ?? null;
            
            if ($nextLevel) {
                $query->where('level', $nextLevel);
                // Filter by student's institution
                $query->where('institution_id', $student->classRoom->institution_id);
                
                // Filter by next academic year
                $currentAcademicYear = \App\Models\AcademicYear::where('is_current', true)->first();
                if ($currentAcademicYear) {
                    $nextAcademicYear = \App\Models\AcademicYear::where('year_start', $currentAcademicYear->year_start + 1)
                        ->where('year_end', $currentAcademicYear->year_end + 1)
                        ->first();
                    
                    if ($nextAcademicYear) {
                        $query->where('academic_year_id', $nextAcademicYear->id);
                    }
                }
            }
        }
    } else {
        // Direct filters
        if ($request->has('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }
        
        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        
        if ($request->has('level')) {
            $query->where('level', $request->level);
            $nextLevel = $request->level;
        }
    }
    
    $classes = $query->orderBy('class_name')->get(['id', 'class_name', 'level', 'institution_id', 'academic_year_id']);
    
    return response()->json([
        'success' => true,
        'classes' => $classes,
        'nextLevel' => $nextLevel
    ]);
});

