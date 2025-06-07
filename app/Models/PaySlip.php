<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class PaySlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'file_size',
        'processed',
        'processed_at',
        'extracted_data',
        'processing_error',
        'month',
        'year',
    ];

    protected $casts = [
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'extracted_data' => 'array',
        'month' => 'integer',
        'year' => 'integer',
        'file_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salary(): HasOne
    {
        return $this->hasOne(Salary::class, 'pay_slip_id');
    }

    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function getFileUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function markAsProcessed(array $extractedData): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
            'extracted_data' => $extractedData,
            'processing_error' => null,
        ]);
    }

    public function markAsError(string $error): void
    {
        $this->update([
            'processed' => false,
            'processing_error' => $error,
        ]);
    }
}
