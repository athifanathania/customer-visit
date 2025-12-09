<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VisitSchedule;
use App\Models\User;
use App\Notifications\VisitScheduleReminderNotification;

class SendVisitScheduleReminders extends Command
{
    /**
     * Nama perintah di Artisan.
     */
    protected $signature = 'reminders:visit-schedules';

    /**
     * Deskripsi singkat (muncul di php artisan list).
     */
    protected $description = 'Kirim email reminder untuk jadwal visit (H-1, 1 jam lagi, dan hari H).';

    public function handle(): int
    {
        $now = now();

        // ========== H-1 (besok) ==========
        $tomorrow = $now->copy()->addDay()->toDateString();

        $dayMinus1 = VisitSchedule::with('customer')
            ->where('status', 'planned')
            ->whereDate('scheduled_at', $tomorrow)
            ->whereNull('reminded_1d_at')       
            ->get();

        foreach ($dayMinus1 as $s) {
            $this->notifyAllUsers($s, 'H-1');
            $s->forceFill(['reminded_1d_at' => now()])->saveQuietly();
            $this->info("H-1 reminder dikirim untuk jadwal ID {$s->id}");
        }

        // ========== 1 jam lagi ==========
        $oneHourWindowFrom = $now;
        $oneHourWindowTo   = $now->copy()->addHour();

        $inOneHour = VisitSchedule::with('customer')
            ->where('status', 'planned')
            ->whereBetween('scheduled_at', [$oneHourWindowFrom, $oneHourWindowTo])
            ->whereNull('reminded_1h_at')
            ->get();

        foreach ($inOneHour as $s) {
            $this->notifyAllUsers($s, 'H-1h');
            $s->forceFill(['reminded_1h_at' => now()])->saveQuietly();
            $this->info("H-1h reminder dikirim untuk jadwal ID {$s->id}");
        }

        // ========== Hari ini (H0) ==========
        $today = $now->toDateString();

        $dayZero = VisitSchedule::with('customer')
            ->where('status', 'planned')
            ->whereDate('scheduled_at', $today)
            ->where('scheduled_at', '>', $oneHourWindowTo)   
            ->whereNull('reminded_h0_at')                    
            ->get();

        foreach ($dayZero as $s) {
            $this->notifyAllUsers($s, 'H0');
            $s->forceFill(['reminded_h0_at' => now()])->saveQuietly();
            $this->info("H0 reminder dikirim untuk jadwal ID {$s->id}");
        }

        return static::SUCCESS;
    }

    /**
     * Kirim notifikasi ke semua user yang punya email.
     * (nanti kalau mau hanya role tertentu bisa difilter di sini).
     */
    protected function notifyAllUsers(VisitSchedule $schedule, string $type): void
    {
        $users = User::query()
            ->whereNotNull('email')
            ->whereIn('role', ['admin', 'marketing'])
            ->get();

        foreach ($users as $user) {
            $user->notify(new VisitScheduleReminderNotification($schedule, $type));
        }
    }
}
