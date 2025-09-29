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

    // Tytuł widgetu, odwołanie do klucza w pliku filament.php
    protected static ?string $heading = 'filament.password_change_widget_title';

    // Dopasowane do nazwy pliku Blade
    protected static string $view = 'filament.widgets.zmien-haslo';

    public ?array $data = [];

    // Definicja formularza Filament
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('stare_haslo')
                    // Tłumaczenie etykiety: filament.current_password
                    ->label(__('filament.current_password'))
                    ->password()
                    ->required()
                    ->currentPassword(),

                TextInput::make('nowe_haslo')
                    // Tłumaczenie etykiety: filament.new_password
                    ->label(__('filament.new_password'))
                    ->password()
                    ->required()
                    ->rule(Password::min(8)),

                TextInput::make('potwierdz_haslo')
                    // Tłumaczenie etykiety: filament.confirm_new_password
                    ->label(__('filament.confirm_new_password'))
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
                // Tłumaczenie tytułu notyfikacji: filament.notification_password_invalid
                ->title(__('filament.notification_password_invalid'))
                ->danger()
                ->send();
            return;
        }

        // Zmiana hasła
        $user->password = $this->data['nowe_haslo'];
        $user->save();

        Notification::make()
            // Tłumaczenie tytułu notyfikacji: filament.notification_password_changed
            ->title(__('filament.notification_password_changed'))
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