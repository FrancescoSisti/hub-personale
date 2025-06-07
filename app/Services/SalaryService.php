<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class SalaryService
{
    public function createSalary(User $user, array $data): Salary
    {
        return DB::transaction(function () use ($user, $data) {
            $salary = new Salary($data);
            $salary->user_id = $user->id;
            $salary->gross_salary = $salary->calculateGrossSalary();
            $salary->net_salary = $salary->calculateNetSalary();
            $salary->save();

            return $salary;
        });
    }

    public function updateSalary(Salary $salary, array $data): Salary
    {
        return DB::transaction(function () use ($salary, $data) {
            $salary->fill($data);
            $salary->gross_salary = $salary->calculateGrossSalary();
            $salary->net_salary = $salary->calculateNetSalary();
            $salary->save();

            return $salary;
        });
    }

    public function getMonthlyStatistics(User $user, int $year): array
    {
        $salaries = Salary::forUser($user->id)
            ->forYear($year)
            ->orderBy('month')
            ->get();

        return [
            'total_gross' => $salaries->sum('gross_salary'),
            'total_net' => $salaries->sum('net_salary'),
            'total_taxes' => $salaries->sum('tax_amount'),
            'total_deductions' => $salaries->sum('deductions'),
            'total_overtime' => $salaries->sum(function ($salary) {
                return $salary->overtime_hours * $salary->overtime_rate;
            }),
            'monthly_data' => $salaries->map(function ($salary) {
                return [
                    'month' => $salary->month,
                    'gross_salary' => $salary->gross_salary,
                    'net_salary' => $salary->net_salary,
                    'tax_amount' => $salary->tax_amount,
                    'deductions' => $salary->deductions,
                    'overtime_pay' => $salary->overtime_hours * $salary->overtime_rate,
                ];
            }),
        ];
    }

    public function getYearlyTrends(User $user, int $startYear, int $endYear): array
    {
        $yearlyData = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearSalaries = Salary::forUser($user->id)
                ->forYear($year)
                ->get();

            $yearlyData[] = [
                'year' => $year,
                'total_gross' => $yearSalaries->sum('gross_salary'),
                'total_net' => $yearSalaries->sum('net_salary'),
                'average_monthly_gross' => $yearSalaries->avg('gross_salary') ?? 0,
                'average_monthly_net' => $yearSalaries->avg('net_salary') ?? 0,
                'months_recorded' => $yearSalaries->count(),
            ];
        }

        return $yearlyData;
    }

    public function getCurrentMonthSalary(User $user): ?Salary
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return Salary::forUser($user->id)
            ->forMonth($currentMonth, $currentYear)
            ->first();
    }

    public function getTopEarningMonths(User $user, int $limit = 5): Collection
    {
        return Salary::forUser($user->id)
            ->orderBy('net_salary', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateTaxRate(Salary $salary): float
    {
        if ($salary->gross_salary <= 0) {
            return 0;
        }

        return ($salary->tax_amount / $salary->gross_salary) * 100;
    }
}
