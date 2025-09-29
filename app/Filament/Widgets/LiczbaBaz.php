<?php

namespace App\Filament\Widgets;

use App\Models\Bazy; // Import twojego modelu Bazy
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class LiczbaBaz extends BaseWidget
{

    protected static ?int $sort = -4;

    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 1; // zajmuje pełną szerokość
    protected int | string | array $rowSpan = 1;  // 1 wiersz wysokości

    protected static string $view = 'filament.widgets.liczba-baz';



    protected function getStats(): array
    {

        // 1. Sprawdzenie roli użytkownika (zakładamy, że masz metodę isAdmin() w modelu User)
        $isAdmin = auth()->user()->isAdmin();
        
        // 2. Utworzenie bazowego zapytania
        $query = Bazy::query();
        
        // 3. Warunkowe filtrowanie zapytania
        // Jeśli użytkownik NIE jest adminem, filtruj po jego ID (user_id)
        if (!$isAdmin) {
            $query->where('user_id', auth()->id());
        }

        // 4. Pobranie ostatecznej liczby baz
        $totalBazyCount = $query->count();
        
        // 5. Ustawienie dynamicznego tytułu statystyki
        $title = $isAdmin
    ? __('filament.total_bases_globally')   // z pliku filament.php
    : __('filament.your_bases_created');
        
        return [
            Stat::make($title, $totalBazyCount)
            
        ];
    }
}