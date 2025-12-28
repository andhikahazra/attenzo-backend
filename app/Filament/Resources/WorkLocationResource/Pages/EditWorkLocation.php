<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkLocation extends EditRecord
{
    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
