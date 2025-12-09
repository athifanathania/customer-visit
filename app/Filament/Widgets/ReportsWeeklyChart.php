<?php

namespace App\Filament\Widgets;

use App\Models\VisitReport;
use Carbon\CarbonPeriod;            // ✅
use Filament\Widgets\ChartWidget;

class ReportsWeeklyChart extends ChartWidget
{
    protected static ?string $heading = 'Report per Hari (Minggu ini)';
    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar'; // ← dari 'line' ke 'bar'
    }

    protected function getData(): array
    {
        $start = now()->startOfWeek();
        $end   = now()->endOfWeek();

        $rows = \App\Models\VisitReport::query()
            ->whereBetween('visit_date', [$start, $end])
            ->selectRaw('DATE(visit_date) as d, COUNT(*) as c')
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
            'datasets' => [[ 'label' => 'Reports', 'data' => $data ]],
            'labels'   => $labels,
        ];
    }

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
                    'ticks' => ['stepSize' => 1],
                ],
            ],
        ];
    }
}
