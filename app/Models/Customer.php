<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function schedules() { return $this->hasMany(VisitSchedule::class, 'cust_id'); }
    public function reports()   { return $this->hasMany(VisitReport::class, 'cust_id'); }
}
