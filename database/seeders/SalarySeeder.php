<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Database\Seeder;

final class SalarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->info('Nessun utente trovato. Crea prima un utente.');
            return;
        }

        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Crea stipendi per gli ultimi 12 mesi
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            // Evita duplicati
            if (Salary::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists()
            ) {
                continue;
            }

            $baseSalary = fake()->numberBetween(2500, 3500);
            $bonus = $i % 3 === 0 ? fake()->numberBetween(200, 800) : 0; // Bonus ogni 3 mesi
            $overtimeHours = fake()->numberBetween(0, 20);
            $overtimeRate = 25.50;
            $taxAmount = $baseSalary * 0.23; // 23% di tasse
            $deductions = fake()->numberBetween(50, 200);

            $salary = new Salary([
                'user_id' => $user->id,
                'base_salary' => $baseSalary,
                'bonus' => $bonus,
                'overtime_hours' => $overtimeHours,
                'overtime_rate' => $overtimeRate,
                'deductions' => $deductions,
                'tax_amount' => $taxAmount,
                'month' => $month,
                'year' => $year,
                'notes' => $i === 0 ? 'Stipendio corrente' : null,
            ]);

            $salary->gross_salary = $salary->calculateGrossSalary();
            $salary->net_salary = $salary->calculateNetSalary();
            $salary->save();
        }

        $this->command->info('Creati stipendi di esempio per l\'utente: ' . $user->email);
    }
}
