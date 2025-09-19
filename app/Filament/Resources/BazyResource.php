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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BazyResource extends Resource
{
    protected static ?string $model = Bazy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    
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
                    'postgre' => 'PostgreSQL',
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
                Tables\Columns\TextColumn::make('host'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('data_wygasniacia')->date(),
                Tables\Columns\TextColumn::make('user.name')->label('User'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('login')
                    ->label('Zaloguj')
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
