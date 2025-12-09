<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\VisitSchedule;
use App\Models\VisitReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\Schema;

class DashboardStats extends BaseWidget
{
    protected ?string $heading = 'Ringkasan';
    protected int | string | array $columnSpan = 'full';

    protected function getCards(): array
    {
        $now     = now();
        $weekBeg = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        $activeCustomers = Schema::hasColumn('customers', 'status')
            ? Customer::where('status', 'active')->count()
            : Customer::count();

        $schedulesThisWeek = VisitSchedule::whereBetween('scheduled_at', [$weekBeg, $weekEnd])
            ->where('status', '!=', 'canceled')
            ->count();

        $overdue = VisitSchedule::where('status', 'planned')
            ->where('scheduled_at', '<', $now)
            ->count();

        $reportsThisMonth = VisitReport::whereBetween('visit_date', [
            $now->copy()->startOfMonth(),
            $now->copy()->endOfMonth(),
        ])->count();

        return [
            Card::make('Customer Aktif', number_format($activeCustomers))
                ->icon('heroicon-m-users')
                ->color('success'),

            Card::make('Jadwal Minggu Ini', number_format($schedulesThisWeek))
                ->icon('heroicon-m-calendar-days')
                ->color('primary')
                ->description('Termasuk yang selesai'),

            Card::make('Overdue', number_format($overdue))
                ->icon('heroicon-m-exclamation-triangle')
                ->color($overdue ? 'danger' : 'gray')
                ->description($overdue ? 'Butuh follow-up' : 'Tidak ada'),

            Card::make('Laporan Bulan Ini', number_format($reportsThisMonth))
                ->icon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}
