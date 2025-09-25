<?php

namespace App\Filament\Widgets;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\View\View;

class ZmienHaslo extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 100;

    // Dopasowane do nazwy pliku Blade
    protected static string $view = 'filament.widgets.zmien-haslo';

    public ?array $data = [];

    // Definicja formularza Filament
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('stare_haslo')
                    ->label('Aktualne hasło')
                    ->password()
                    ->required()
                    ->currentPassword(), // Laravel sprawdza aktualne hasło

                TextInput::make('nowe_haslo')
                    ->label('Nowe hasło')
                    ->password()
                    ->required()
                    ->rule(Password::min(8)),

                TextInput::make('potwierdz_haslo')
                    ->label('Potwierdź nowe hasło')
                    ->password()
                    ->required()
                    ->same('nowe_haslo'),
            ])
            ->statePath('data');
    }

    // Akcja zmiany hasła
    public function zmienHaslo(): void
    {
        $this->form->validate();

        $user = auth()->user();

        // Sprawdzenie aktualnego hasła
        if (!Hash::check($this->data['stare_haslo'], $user->password)) {
            Notification::make()
                ->title('Aktualne hasło jest nieprawidłowe')
                ->danger()
                ->send();
            return;
        }

        // Zmiana hasła
        $user->password = $this->data['nowe_haslo'];
        $user->save();

        Notification::make()
            ->title('Hasło zostało zmienione')
            ->success()
            ->send();

        // Reset formularza
        $this->form->fill();
    }

    // Renderowanie widgetu – zwracamy View
    public function render(): View
    {
        return view(static::$view, [
            'form' => $this->form,
            'submitAction' => 'zmienHaslo',
        ]);
    }
}
