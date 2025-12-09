<?php

namespace App\Filament\Widgets;

use App\Models\VisitSchedule;
use Carbon\CarbonPeriod;            
use Filament\Widgets\ChartWidget;

class SchedulesThisWeekChart extends ChartWidget
{
    protected static ?string $heading = 'Jadwal per Hari (Minggu ini)';
    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar'; // ← dari 'line' ke 'bar'
    }

    protected function getData(): array
    {
        $start = now()->startOfWeek();
        $end   = now()->endOfWeek();

        $rows = VisitSchedule::query()
            ->whereBetween('scheduled_at', [$start, $end])
            ->where('status', '!=', 'canceled')   // ← hitung planned + done
            ->selectRaw('DATE(scheduled_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        $labels = [];
        $data   = [];
        foreach (\Carbon\CarbonPeriod::create($start, $end) as $day) {
            $key = $day->toDateString();
            $labels[] = $day->isoFormat('ddd');
            $data[]   = (int)($rows[$key] ?? 0);
        }

        return [
            'datasets' => [[ 'label' => 'Planned', 'data' => $data ]],
            'labels'   => $labels,
        ];
    }

    // Opsi visual agar lebih rapi
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1], // hitungan per hari
                ],
            ],
        ];
    }
}
