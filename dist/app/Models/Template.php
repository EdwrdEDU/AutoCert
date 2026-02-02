<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'file_type',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all fields for this template
     */
    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class);
    }

    /**
     * Get all certificates generated from this template
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Check if template is PDF
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'pdf';
    }

    /**
     * Check if template is Word
     */
    public function isWord(): bool
    {
        return in_array($this->file_type, ['docx', 'doc']);
    }

    /**
     * Check if template is PowerPoint
     */
    public function isPowerPoint(): bool
    {
        return in_array($this->file_type, ['pptx', 'ppt']);
    }

    /**
     * Get full file path
     */
    public function getFullPathAttribute(): string
    {
        // Try app/ first (standard location)
        $standardPath = storage_path('app/' . $this->file_path);
        if (file_exists($standardPath)) {
            return $standardPath;
        }
        
        // Try private/ location (alternative)
        $privatePath = storage_path('private/' . $this->file_path);
        if (file_exists($privatePath)) {
            return $privatePath;
        }
        
        // Return standard path if neither exists
        return $standardPath;
    }
}
