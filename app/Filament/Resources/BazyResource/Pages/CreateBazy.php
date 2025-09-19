<?php

namespace App\Filament\Resources\BazyResource\Pages;

use App\Filament\Resources\BazyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBazy extends CreateRecord
{
    protected static string $resource = BazyResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
