<?php

namespace App\Http\Controllers;

use App\Models\FinanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

use function to_route;

class FinanceRecordController extends Controller
{
    /**
     * Menampilkan daftar catatan keuangan (dengan pencarian sederhana berdasarkan tanggal)
     */
    public function index(Request $request): Response
    {
        $searchDate = $request->get('date');

        $records = FinanceRecord::query()
            ->when(
                $searchDate,
                fn($q) => $q->whereDate('date', $searchDate)
            )
            ->latest('date')
            ->get()
            ->map(fn($record) => [
                'id' => $record->id,
                'date' => $record->date->format('Y-m-d'),
                'date_display' => $record->date->format('d M Y'),
                'income' => number_format($record->income, 2),
                'expense' => number_format($record->expense, 2),
                'net' => number_format($record->net, 2),
            ]);

        return Inertia::render('finance-records/Index', [
            'records' => $records,
            'filters' => ['date' => $searchDate],
        ]);
    }


    /**
     * Menyimpan catatan keuangan baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'income' => ['required', 'numeric', 'min:0'],
            'expense' => ['required', 'numeric', 'min:0'],
        ]);

        FinanceRecord::create($validated);

        return to_route('finance-records.index')->with('status', 'Catatan keuangan berhasil ditambahkan.');
    }
    /**
     * Menampilkan detail satu catatan keuangan (untuk form edit)
     */
    public function show(FinanceRecord $financeRecord): Response
    {
        return Inertia::render('finance-records/Edit', [
            'record' => [
                'id' => $financeRecord->id,
                // Format ISO 8601 (dipahami <input type="date">)
                'date' => $financeRecord->date
                    ? $financeRecord->date->format('Y-m-d')
                    : now()->format('Y-m-d'),
                'income' => (float) $financeRecord->income,
                'expense' => (float) $financeRecord->expense,
            ],
        ]);
    }


    /**
     * Memperbarui data catatan keuangan yang sudah ada
     */
    public function update(Request $request, FinanceRecord $financeRecord): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'income' => ['required', 'numeric', 'min:0'],
            'expense' => ['required', 'numeric', 'min:0'],
        ]);

        $financeRecord->update($validated);

        return back()->with('status', 'Catatan keuangan berhasil diperbarui.');
    }

    /**
     * Menghapus catatan keuangan
     */
    public function destroy(FinanceRecord $financeRecord): RedirectResponse
    {
        $financeRecord->delete();

        return back()->with('status', 'Catatan keuangan berhasil dihapus.');
    }
}
