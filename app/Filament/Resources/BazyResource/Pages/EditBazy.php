<?php

namespace App\Filament\Resources\BazyResource\Pages;

use App\Filament\Resources\BazyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBazy extends EditRecord
{
    protected static string $resource = BazyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
