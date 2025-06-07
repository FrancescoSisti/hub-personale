<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'base_salary',
        'bonus',
        'overtime_hours',
        'overtime_rate',
        'deductions',
        'net_salary',
        'gross_salary',
        'tax_amount',
        'month',
        'year',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'bonus' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateGrossSalary(): float
    {
        $overtimePay = $this->overtime_hours * $this->overtime_rate;
        return $this->base_salary + $this->bonus + $overtimePay;
    }

    public function calculateNetSalary(): float
    {
        return $this->calculateGrossSalary() - $this->tax_amount - $this->deductions;
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
