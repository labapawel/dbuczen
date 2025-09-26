<?php

namespace App\Filament\Resources\UstawieniaResource\Pages;

use App\Filament\Resources\UstawieniaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUstawienias extends ListRecords
{
    protected static string $resource = UstawieniaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
