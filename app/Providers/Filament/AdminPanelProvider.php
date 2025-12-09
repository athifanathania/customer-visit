<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\SchedulesThisWeekChart;
use App\Filament\Widgets\ReportsWeeklyChart;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use App\Models\VisitSchedule;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Purple,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->sidebarWidth('14em')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                DashboardStats::class,
                SchedulesThisWeekChart::class,
                ReportsWeeklyChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->maxContentWidth('full');
    }
    public function boot(): void
    {
        // 🚫 JANGAN panggil parent::boot()

        Filament::serving(function () {
            if (! auth()->check()) {
                return;
            }

            $now = now();

            // ===== H-1 (besok) =====
            $tomorrow = $now->copy()->addDay()->toDateString();

            $dayMinus1 = VisitSchedule::with('customer')
                ->where('status', 'planned')
                ->whereDate('scheduled_at', $tomorrow)
                ->get();

            foreach ($dayMinus1 as $s) {
                Notification::make()
                    ->title('Reminder: Besok ada visit')
                    ->body(new HtmlString(
                        "<div class='leading-relaxed py-1'>
                            <div class='font-medium mb-1'>".e($s->title ?: 'Visit')."</div>
                            <ul class='list-disc pl-5 space-y-0.5'>
                                <li><span class='font-semibold'>Customer:</span> ".e($s->customer->name ?? '—')."</li>
                                <li><span class='font-semibold'>Waktu:</span> ".e(optional($s->scheduled_at)->format('d M Y, H:i'))."</li>
                                <li><span class='font-semibold'>Lokasi:</span> ".e($s->location ?? '—')."</li>
                            </ul>
                        </div>"
                    ))
                    ->icon('heroicon-m-bell-alert')
                    ->info()
                    ->persistent()
                    ->send();
            }

            // ===== 1 jam lagi =====
            $oneHourWindowFrom = $now;
            $oneHourWindowTo   = $now->copy()->addHour();

            $inOneHour = VisitSchedule::with('customer')
                ->where('status', 'planned')
                ->whereBetween('scheduled_at', [$oneHourWindowFrom, $oneHourWindowTo])
                ->get()
                ->keyBy('id');

            foreach ($inOneHour as $s) {
                Notification::make()
                    ->title('Reminder: 1 jam lagi ada visit')
                    ->body(new HtmlString(
                        "<div class='leading-relaxed py-1'>
                            <div class='font-medium mb-1'>".e($s->title ?: 'Visit')."</div>
                            <ul class='list-disc pl-5 space-y-0.5'>
                                <li><span class='font-semibold'>Customer:</span> ".e($s->customer->name ?? '—')."</li>
                                <li><span class='font-semibold'>Waktu:</span> ".e(optional($s->scheduled_at)->format('d M Y, H:i'))."</li>
                                <li><span class='font-semibold'>Lokasi:</span> ".e($s->location ?? '—')."</li>
                            </ul>
                        </div>"
                    ))
                    ->icon('heroicon-m-bell-alert')
                    ->warning()
                    ->persistent()
                    ->send();
            }

            // ===== Hari ini (H0) =====
            $today = $now->toDateString();

            $dayZero = VisitSchedule::with('customer')
                ->where('status', 'planned')
                ->whereDate('scheduled_at', $today)
                ->where('scheduled_at', '>', $oneHourWindowTo)
                ->get();

            foreach ($dayZero as $s) {
                if ($inOneHour->has($s->id)) {
                    continue;
                }

                Notification::make()
                    ->title('Reminder: Hari ini ada visit')
                    ->body(new HtmlString(
                        "<div class='leading-relaxed py-1'>
                            <div class='font-medium mb-1'>".e($s->title ?: 'Visit')."</div>
                            <ul class='list-disc pl-5 space-y-0.5'>
                                <li><span class='font-semibold'>Customer:</span> ".e($s->customer->name ?? '—')."</li>
                                <li><span class='font-semibold'>Waktu:</span> ".e(optional($s->scheduled_at)->format('d M Y, H:i'))."</li>
                                <li><span class='font-semibold'>Lokasi:</span> ".e($s->location ?? '—')."</li>
                            </ul>
                        </div>"
                    ))
                    ->icon('heroicon-m-bell-alert')
                    ->success()
                    ->persistent()
                    ->send();
            }
        });
    }
}
