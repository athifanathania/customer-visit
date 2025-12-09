<?php

namespace App\Filament\Resources\VisitScheduleResource\Pages;

use App\Filament\Resources\VisitScheduleResource;
use Filament\Resources\Pages\Page;
use App\Models\VisitSchedule;
use App\Models\Customer;

class TimelineVisitSchedules extends Page
{
    protected static string $resource = VisitScheduleResource::class;

    protected static string $view = 'filament.resources.visit-schedule-resource.pages.timeline-visit-schedules';

    protected static ?string $title = 'Schedule Timeline';

    public array $years = [];
    public ?int $selectedYear = null;

    public array $customers = [];
    public ?int $selectedCustomerId = null;

    public string $viewMode = 'month';

    public ?int $selectedMonthForWeek = null;

    public array $months = [];        
    public array $timelineRows = [];  

    public array $weeklyRows = [];    

    public function mount(): void
    {
        $this->years = VisitSchedule::query()
            ->selectRaw('YEAR(scheduled_at) as year')
            ->whereNotNull('scheduled_at')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->toArray();

        if (empty($this->years)) {
            $this->years = [now()->year];
        }

        $this->selectedYear = in_array(now()->year, $this->years, true)
            ? now()->year
            : $this->years[0];

        $this->customers = Customer::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->selectedCustomerId = null; 

        $this->months = [
            1  => 'Jan',
            2  => 'Feb',
            3  => 'Mar',
            4  => 'Apr',
            5  => 'Mei',
            6  => 'Jun',
            7  => 'Jul',
            8  => 'Agu',
            9  => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        $this->viewMode = 'month';

        $this->selectedMonthForWeek = now()->month;
        if ($this->selectedMonthForWeek < 1 || $this->selectedMonthForWeek > 12) {
            $this->selectedMonthForWeek = 1;
        }

        $this->buildTimeline();
    }

    public function updatedSelectedYear(): void
    {
        $this->buildTimeline();
    }

    public function updatedSelectedCustomerId(): void
    {
        $this->buildTimeline();
    }

    public function updatedViewMode(): void
    {
        if ($this->viewMode === 'week' && ! $this->selectedMonthForWeek) {
            $this->selectedMonthForWeek = now()->month;
        }

        $this->buildWeeklyRows();
    }

    public function updatedSelectedMonthForWeek(): void
    {
        $this->buildWeeklyRows();
    }

    protected function buildTimeline(): void
    {
        $query = VisitSchedule::query()
            ->with('customer')
            ->whereYear('scheduled_at', $this->selectedYear)
            ->whereNotNull('scheduled_at');

        if ($this->selectedCustomerId) {
            $query->where('cust_id', $this->selectedCustomerId);
        }

        $schedules = $query
            ->orderBy('cust_id')
            ->orderBy('scheduled_at')
            ->get();

        $rows = [];

        foreach ($schedules as $schedule) {
            $custId   = $schedule->cust_id;
            $custName = $schedule->customer->name ?? '—';

            if (! isset($rows[$custId])) {
                $rows[$custId] = [
                    'customer_name' => $custName,
                    'months' => array_fill(1, 12, []), 
                ];
            }

            $month = (int) $schedule->scheduled_at->month;
            $day   = (int) $schedule->scheduled_at->day;

            $isOverdue = $schedule->status === 'planned'
                && optional($schedule->scheduled_at)->isPast();

            $weekOfMonth = $schedule->scheduled_at->weekOfMonth;

            $rows[$custId]['months'][$month][] = [
                'id'          => $schedule->id,
                'day'         => $day,
                'title'       => $schedule->title,
                'status'      => $schedule->status,
                'is_overdue'  => $isOverdue,
                'week'        => $weekOfMonth,
            ];
        }

        $this->timelineRows = array_values($rows);

        $this->buildWeeklyRows();
    }

    protected function buildWeeklyRows(): void
    {
        $weekly = [];

        $month = $this->selectedMonthForWeek ?: now()->month;
        if ($month < 1 || $month > 12) {
            $month = 1;
        }

        foreach ($this->timelineRows as $row) {
            $customerName = $row['customer_name'];

            $weeks = [
                1 => [],
                2 => [],
                3 => [],
                4 => [],
                5 => [],
            ];

            $itemsThisMonth = $row['months'][$month] ?? [];

            foreach ($itemsThisMonth as $item) {
                $w = (int) ($item['week'] ?? 1);
                if ($w < 1) $w = 1;
                if ($w > 5) $w = 5;

                $weeks[$w][] = $item;
            }

            $weekly[] = [
                'customer_name' => $customerName,
                'weeks'         => $weeks,
            ];
        }

        $this->weeklyRows = $weekly;
    }
}
