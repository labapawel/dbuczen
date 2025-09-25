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
use Symfony\Component\Process\Process;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BazyResource extends Resource
{
    protected static ?string $model = Bazy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $title = 'Bazy Danych';
    protected static ?string $modelLabel = 'Baza Danych';
    protected static ?string $pluralModelLabel = 'Bazy Danych';
    protected static ?string $navigationLabel = 'Bazy Danych';

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
                ->label('Nazwa użytkownika')
                ->unique(ignoreRecord: true)
                ->maxlength(6),

            Forms\Components\TextInput::make('db')
                ->required()
                ->label('Nazwa bazy danych')
                ->maxlength(6),

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
                Tables\Columns\TextColumn::make('username')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('password') // ← nowa kolumna
                ->label('Hasło')
                ->copyable() // można kliknąć i skopiować
                ->toggleable(), // opcjonalnie żeby ukryć/rozwinąć
                Tables\Columns\TextColumn::make('db'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('host')
                    ->label('Host')              
                    ->getStateUsing(fn ($record) => '10.40.60.165'),
                Tables\Columns\TextColumn::make('data_wygasniacia')->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('generatePassword')
                    ->label('')
                    ->icon('heroicon-m-key')
                    ->modalHeading('Generowanie nowego hasła')
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
                        ->color('danger')
                        ->modalHeading('Usuwanie użytkownika i bazy')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                        // Usuń usera i bazę w MySQL/Postgres
                        Artisan::call('db:manage', [
                            'action'  => 'delete-db',
                            'name'    => $record->username,   // nazwa użytkownika np. naplet_coscot
                            'dbName'  => $record->db,     // nazwa bazy np. naplet_321312
                            'driver'  => $record->type,   // mysql / pgsql
                        ]);
                        // Usuń rekord z tabeli
                            $record->delete();
                        }),
                        Action::make('dumpDatabase')
                            ->label('') // tylko ikona
                            ->icon('heroicon-m-arrow-down-tray')
                            ->color('info')
                            ->requiresConfirmation()
                            ->modalHeading('Eksport bazy')
                            ->modalDescription('Ta akcja wyeksportuje całą bazę danych w formacie .sql i pobierze ją jako plik.')
                            ->modalSubmitActionLabel('Pobierz .sql')
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
    ->color('success')
    ->requiresConfirmation()
    ->modalHeading('Przedłużenie bazy o 14 dni')
    ->action(function ($record) {
        // Dodaj 14 dni
        $record->data_wygasniacia = \Carbon\Carbon::parse($record->data_wygasniacia)->addDays(14);
        $record->save();

    }),
                Action::make('login')
                ->label('')
                ->icon('heroicon-m-arrow-right-on-rectangle') 
                    ->url(fn ($record) => $record->type === 'mysql'
                        ? 'https://db.ptibb.edu.pl/phpmyadmin/'
                        : 'https://db.ptibb.edu.pl/phppgadmin/')
        ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
