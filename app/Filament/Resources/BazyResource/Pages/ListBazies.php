<?php

namespace App\Filament\Resources\BazyResource\Pages;

use App\Filament\Resources\BazyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBazies extends ListRecords
{
    protected static string $resource = BazyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
