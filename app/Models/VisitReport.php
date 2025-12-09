<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitReport extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'visit_date' => 'datetime', 
        'discussion_points' => 'array',
        'attachments'  => 'array'
    ];

    public function customer() { return $this->belongsTo(Customer::class, 'cust_id'); }
    public function schedule() { return $this->belongsTo(VisitSchedule::class, 'schedule_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }

    protected static function booted()
    {
        // Report baru dibuat
        static::created(function (VisitReport $report) {
            if ($report->schedule && $report->schedule->status !== 'canceled') {
                $report->schedule->update(['status' => 'done']);
            }
        });

        // Jika user mengganti "Jadwal Terkait" di report
        static::updated(function (VisitReport $report) {
            if ($report->wasChanged('schedule_id')) {
                $oldId = $report->getOriginal('schedule_id');
                $new   = $report->schedule;

                // Jadwal baru -> done
                if ($new && $new->status !== 'canceled') {
                    $new->update(['status' => 'done']);
                }

                // (opsional) Jadwal lama -> planned, kalau memang tidak ada report lain yang menempel
                if ($oldId) {
                    $old = \App\Models\VisitSchedule::find($oldId);
                    if ($old && $old->status !== 'canceled' && ! $old->report()->whereKeyNot($report->getKey())->exists()) {
                        $old->update(['status' => 'planned']);
                    }
                }
            }
        });

        // Jika report dihapus, pulihkan planned bila tak ada report lain
        static::deleted(function (VisitReport $report) {
            if ($report->schedule && $report->schedule->status !== 'canceled'
                && ! $report->schedule->report()->whereKeyNot($report->getKey())->exists()) {
                $report->schedule->update(['status' => 'planned']);
            }
        });
    }
}
