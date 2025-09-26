<?php

namespace App\Filament\Resources\UstawieniaResource\Pages;

use App\Filament\Resources\UstawieniaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUstawienia extends EditRecord
{
    protected static string $resource = UstawieniaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
