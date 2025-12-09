<?php

namespace App\Filament\Resources\VisitScheduleResource\Pages;

use App\Filament\Resources\VisitScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVisitSchedule extends CreateRecord
{
    protected static string $resource = VisitScheduleResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
