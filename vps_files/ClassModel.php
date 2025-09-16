<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'class_name',
        'grade_level',
        'level',
        'institution_id',
        'academic_year_id',
        'capacity',
        'is_active',
        'is_graduated_class',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'is_graduated_class' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class, 'class_id');
    }

    public function billingRecords()
    {
        return $this->hasMany(BillingRecord::class, 'class_id');
    }

    // Accessor untuk nama kelas
    public function getNameAttribute()
    {
        return $this->class_name;
    }

    // Accessor untuk level
    public function getLevelAttribute()
    {
        // Cek apakah ada nilai level di database
        if ($this->attributes['level']) {
            return $this->attributes['level'];
        }
        
        // Auto-detect level from class_name if level is not set
        $className = strtoupper($this->class_name);
        
        if (strpos($className, 'VII') !== false) return 'VII';
        if (strpos($className, 'VIII') !== false) return 'VIII';
        if (strpos($className, 'IX') !== false) return 'IX';
        if (strpos($className, 'X') !== false && strpos($className, 'XI') === false && strpos($className, 'XII') === false) return 'X';
        if (strpos($className, 'XI') !== false) return 'XI';
        if (strpos($className, 'XII') !== false) return 'XII';
        
        return null;
    }
    
    // Method untuk mendapatkan level berdasarkan nama kelas
    public static function getLevelFromClassName($className)
    {
        $className = strtoupper($className);
        
        // Check from most specific to least specific
        if (strpos($className, 'XII') !== false) return 'XII';
        if (strpos($className, 'XI') !== false) return 'XI';
        if (strpos($className, 'VIII') !== false) return 'VIII';
        if (strpos($className, 'VII') !== false) return 'VII';
        if (strpos($className, 'IX') !== false) return 'IX';
        if (strpos($className, 'X') !== false) return 'X';
        
        return null;
    }
    
    // Method untuk mendapatkan level yang aman (tanpa infinite loop)
    public function getSafeLevelAttribute()
    {
        // Cek apakah ada nilai level di database
        if (isset($this->attributes['level']) && $this->attributes['level']) {
            return $this->attributes['level'];
        }
        
        // Auto-detect level from class_name
        return self::getLevelFromClassName($this->class_name);
    }
}
