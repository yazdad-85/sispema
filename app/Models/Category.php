<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function activityPlans()
    {
        return $this->hasMany(ActivityPlan::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePemasukan($query)
    {
        return $query->where('type', 'pemasukan');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('type', 'pengeluaran');
    }
}
