<?php

namespace App\Filament\Resources\VisitScheduleResource\Pages;

use App\Filament\Resources\VisitScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitSchedule extends EditRecord
{
    protected static string $resource = VisitScheduleResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
