<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazyResource\Pages;
use App\Filament\Resources\BazyResource\RelationManagers;
use App\Models\Bazy;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Str;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan; 
use Illuminate\Support\Facades\Response;
use Filament\Tables\Columns\CheckboxColumn;
use Symfony\Component\Process\Process;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BazyResource extends Resource
{
    protected static ?string $model = Bazy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $title = null;

    public static function getTitle(): string
    {
        return __('filament.bazy'); // jeden klucz, tłumaczenie zależne od języka
    }
    
    public static function getModelLabel(): string
    {
        return __('filament.bazy');
    }
    
    public static function getPluralModelLabel(): string
    {
        return __('filament.bazy');
    }
    
    public static function getNavigationLabel(): string
    {
        return __('filament.bazy');
    }

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->isAdmin()) {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
    
    
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('username')
                ->required()
                ->label(__('filament.username'))
                ->unique(ignoreRecord: true)
                ->maxlength(6),

            Forms\Components\TextInput::make('db')
                ->required()
                ->label(__('filament.database_name'))
                ->maxlength(6)
                ->unique()(ignoreRecord: true),

            Forms\Components\Select::make('type')
                ->options([
                    'mysql'   => 'MySQL',
                    'pgsql' => 'PostgreSQL',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')->sortable()->searchable()->copyable(),
                Tables\Columns\TextColumn::make('password')
                    ->label(__('filament.password')) // PRZETŁUMACZONE
                    ->copyable()
                    ->view('tables.columns.password-toggle'),
                Tables\Columns\TextColumn::make('db'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament.database_type')), // PRZETŁUMACZONE
                Tables\Columns\TextColumn::make('host')
                    ->label('Host')
                    ->copyable()
                    ->getStateUsing(fn ($record) => '10.40.60.165'),
                Tables\Columns\TextColumn::make('data_wygasniacia')
                    ->label(__('filament.expiry_date')) // PRZETŁUMACZONE
                    ->date(),
                    CheckboxColumn::make('niewygasa')
                    ->label(__('filament.niewygasaj'))
                    ->visible(fn ($record) => auth()->user()->isAdmin())
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state) {
                            // Zaznaczenie checkboxa → usuwa datę wygaśnięcia
                            $record->data_wygasniacia = null;
                        } else {
                            // Odznaczenie → ustawia +14 dni od teraz
                            $record->data_wygasniacia = \Carbon\Carbon::now()->addDays(14);
                        }
                        $record->save();
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('generatePassword')
                    ->label('')
                    ->icon('heroicon-m-key')
                    ->tooltip(__('filament.generate_password_tooltip')) // PRZETŁUMACZONE
                    ->modalHeading(__('filament.generate_password_modal_heading')) // PRZETŁUMACZONE
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // 1. Generowanie nowego hasła
                        $newPassword = Str::random(16);

                        // 2. Zapisz w bazie Laravel
                        $record->password = $newPassword;
                        $record->save();

                        // 3. Wywołanie komendy Artisan do zmiany hasła w DB
                        Artisan::call('db:manage', [
                            'action'   => 'change-db-pass',
                            'name'     => $record->username,
                            'password' => $newPassword,
                            'driver'   => $record->type,
                        ]);
                    }),
                Action::make('deleteUserAndDb')
                    ->label('')
                    ->icon('heroicon-m-trash')
                    ->tooltip(__('filament.delete_user_db_tooltip')) // PRZETŁUMACZONE
                    ->color('danger')
                    ->modalHeading(__('filament.delete_user_db_modal_heading')) // PRZETŁUMACZONE
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Usuń usera i bazę w MySQL/Postgres
                        Artisan::call('db:manage', [
                            'action'  => 'delete-db',
                            'name'    => $record->username,
                            'dbName'  => $record->db,
                            'driver'  => $record->type,
                        ]);
                        // Usuń rekord z tabeli
                        $record->delete();
                    }),
                Action::make('dumpDatabase')
                    ->label('') // tylko ikona
                    ->icon('heroicon-m-arrow-down-tray')
                    ->tooltip(__('filament.download_sql_tooltip')) // PRZETŁUMACZONE
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.export_database_heading')) // PRZETŁUMACZONE
                    ->modalDescription(__('filament.export_database_description')) // PRZETŁUMACZONE
                    ->modalSubmitActionLabel(__('filament.download_sql_label')) // PRZETŁUMACZONE
                    ->action(function ($record) {
                        $dbName = $record->db;
                        $username = $record->username;
                        $password = $record->password; // prawdziwe hasło do DB
                        $driver = $record->type;
                        $host = '10.40.60.165'; // Twój zdalny host

                        // Tworzymy katalog, jeśli nie istnieje
                        $exportPath = storage_path('app/exports');
                        if (!file_exists($exportPath)) {
                            mkdir($exportPath, 0755, true);
                        }

                        $filePath = $exportPath . "/{$dbName}.sql";

                        if ($driver === 'mysql') {
                            // MySQL zdalny host
                            $process = Process::fromShellCommandline(
                                "mysqldump -h {$host} -u{$username} -p{$password} {$dbName} > \"$filePath\""
                            );
                        } else {
                            // PostgreSQL zdalny host
                            $process = Process::fromShellCommandline(
                                "PGPASSWORD={$password} pg_dump -h {$host} -U {$username} -d {$dbName} > \"$filePath\""
                            );
                        }

                        $process->run();

                        if (! $process->isSuccessful()) {
                            throw new \Exception('Eksport bazy się nie powiódł: ' . $process->getErrorOutput());
                        }

                        // Zwracamy plik do pobrania i usuwamy po wysłaniu
                        return Response::download($filePath)->deleteFileAfterSend(true);
                    }),
                Action::make('extendExpiry')
                    ->label('')
                    ->icon('heroicon-m-clock')
                    ->tooltip(__('filament.extend_expiry_tooltip')) // PRZETŁUMACZONE
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.extend_expiry_heading')) // PRZETŁUMACZONE
                    ->action(function ($record) {
                        // Dodaj 14 dni
                        $record->data_wygasniacia = \Carbon\Carbon::now()->addDays(14);
                        $record->save();
                    }),
                Action::make('login')
                    ->label('')
                    ->tooltip(__('filament.login_db_tooltip')) // PRZETŁUMACZONE
                    ->icon('heroicon-m-arrow-right-on-rectangle')
                    ->url(fn ($record) => $record->type === 'mysql'
                        ? 'https://db.ptibb.edu.pl/phpmyadmin/'
                        : 'https://db.ptibb.edu.pl/phppgadmin/')
                    ->openUrlInNewTab(), // Zachowano funkcjonalność otwierania w nowej karcie
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make(),]),]);
    }


    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBazies::route('/'),
            'create' => Pages\CreateBazy::route('/create'),
            'edit' => Pages\EditBazy::route('/{record}/edit'),
        ];
    }
}
