<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Models\Salary;
use App\Services\SalaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SalaryController extends Controller
{
    public function __construct(
        private readonly SalaryService $salaryService
    ) {}

    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $user = $request->user();

        $salaries = Salary::forUser($user->id)
            ->forYear($year)
            ->orderBy('month', 'desc')
            ->get();

        $statistics = $this->salaryService->getMonthlyStatistics($user, $year);
        $currentMonthSalary = $this->salaryService->getCurrentMonthSalary($user);

        return Inertia::render('Salary/Index', [
            'salaries' => $salaries,
            'statistics' => $statistics,
            'currentMonthSalary' => $currentMonthSalary,
            'year' => $year,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Salary/Create');
    }

    public function store(StoreSalaryRequest $request): JsonResponse
    {
        $user = $request->user();
        $salary = $this->salaryService->createSalary($user, $request->validated());

        return response()->json([
            'message' => 'Stipendio creato con successo',
            'salary' => $salary,
        ], 201);
    }

    public function show(Salary $salary): Response
    {
        $this->authorize('view', $salary);

        return Inertia::render('Salary/Show', [
            'salary' => $salary,
            'taxRate' => $this->salaryService->calculateTaxRate($salary),
        ]);
    }

    public function edit(Salary $salary): Response
    {
        $this->authorize('update', $salary);

        return Inertia::render('Salary/Edit', [
            'salary' => $salary,
        ]);
    }

    public function update(UpdateSalaryRequest $request, Salary $salary): JsonResponse
    {
        $this->authorize('update', $salary);

        $updatedSalary = $this->salaryService->updateSalary($salary, $request->validated());

        return response()->json([
            'message' => 'Stipendio aggiornato con successo',
            'salary' => $updatedSalary,
        ]);
    }

    public function destroy(Salary $salary): JsonResponse
    {
        $this->authorize('delete', $salary);

        $salary->delete();

        return response()->json([
            'message' => 'Stipendio eliminato con successo',
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $year = $request->integer('year', now()->year);
        $user = $request->user();

        $statistics = $this->salaryService->getMonthlyStatistics($user, $year);

        return response()->json($statistics);
    }

    public function trends(Request $request): JsonResponse
    {
        $startYear = $request->integer('start_year', now()->year - 2);
        $endYear = $request->integer('end_year', now()->year);
        $user = $request->user();

        $trends = $this->salaryService->getYearlyTrends($user, $startYear, $endYear);

        return response()->json($trends);
    }

    public function topEarningMonths(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 5);
        $user = $request->user();

        $topMonths = $this->salaryService->getTopEarningMonths($user, $limit);

        return response()->json($topMonths);
    }
}
