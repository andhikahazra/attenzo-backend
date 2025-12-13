<?php

namespace App\Filament\Resources\FacePhotoResource\Pages;

use App\Filament\Resources\FacePhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacePhoto extends EditRecord
{
    protected static string $resource = FacePhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
