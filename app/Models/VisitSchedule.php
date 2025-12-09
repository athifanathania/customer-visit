<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitSchedule extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'scheduled_at'  => 'datetime',
        'reminded_1d_at'=> 'datetime',
        'reminded_1h_at'=> 'datetime',
        'reminded_h0_at' => 'datetime',
    ];

    public function customer() { return $this->belongsTo(Customer::class, 'cust_id'); }
    public function report()   { return $this->hasOne(VisitReport::class, 'schedule_id'); }

    protected static function booted()
    {
        static::updating(function (VisitSchedule $schedule) {
            $reschedule = $schedule->isDirty('scheduled_at');
            $backToPlanned = $schedule->isDirty('status') && $schedule->status === 'planned';

            if ($reschedule || $backToPlanned) {
                $schedule->reminded_1d_at = null;
                $schedule->reminded_1h_at = null;
                $schedule->reminded_h0_at = null;
            }
        });
    }

}
