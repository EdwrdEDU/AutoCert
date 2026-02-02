<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'template_id',
        'recipient_data',
        'recipient_name',
        'generated_pdf_path',
        'status',
        'generated_at',
    ];

    protected $casts = [
        'recipient_data' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_GENERATED = 'generated';
    const STATUS_FAILED = 'failed';

    /**
     * Get the template used for this certificate
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get recipient name
     */
    public function getRecipientNameAttribute(): ?string
    {
        return $this->recipient_data['name'] ?? 
               $this->recipient_data['full_name'] ?? 
               null;
    }

    /**
     * Get full PDF path
     */
    public function getFullPdfPathAttribute(): ?string
    {
        if (!$this->generated_pdf_path) {
            return null;
        }

        return storage_path('app/' . $this->generated_pdf_path);
    }

    /**
     * Check if certificate is generated
     */
    public function isGenerated(): bool
    {
        return $this->status === self::STATUS_GENERATED && 
               $this->generated_pdf_path !== null;
    }

    /**
     * Mark as generated
     */
    public function markAsGenerated(string $pdfPath): void
    {
        $this->update([
            'generated_pdf_path' => $pdfPath,
            'status' => self::STATUS_GENERATED,
            'generated_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }
}
