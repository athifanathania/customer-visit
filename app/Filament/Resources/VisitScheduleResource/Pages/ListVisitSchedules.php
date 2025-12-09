<?php

namespace App\Filament\Resources\VisitScheduleResource\Pages;

use App\Filament\Resources\VisitScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Models\VisitSchedule;
use Illuminate\Support\HtmlString;
use App\Notifications\VisitScheduleReminderNotification;
use Illuminate\Support\Facades\Auth;

class ListVisitSchedules extends ListRecords
{
    protected static string $resource = VisitScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
