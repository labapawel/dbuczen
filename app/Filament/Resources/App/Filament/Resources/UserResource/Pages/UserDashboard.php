<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UserDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.user-dashboard';
    protected static ?string $navigationLabel = 'User Dashboard';
    protected static ?string $title = 'Dashboard';
}
