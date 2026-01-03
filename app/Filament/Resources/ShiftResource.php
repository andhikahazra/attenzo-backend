<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Shift';
    protected static ?string $pluralLabel = 'Shift Kerja';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Shift')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Shift')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Jam Masuk')
                            ->required()
                            ->seconds(false)
                            ->columnSpanFull(),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Jam Pulang')
                            ->required()
                            ->seconds(false)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Toleransi Waktu (Menit)')
                    ->description('Semua nilai toleransi dihitung dalam menit')
                    ->schema([
                        Forms\Components\TextInput::make('early_checkin_tolerance')
                            ->label('Toleransi Check-in Lebih Awal')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('late_tolerance')
                            ->label('Toleransi Keterlambatan')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('early_leave_tolerance')
                            ->label('Toleransi Pulang Lebih Awal')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1); // 🔥 1 kolom per baris
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Shift')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Masuk')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Pulang')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('early_checkin_tolerance')
                    ->label('Early Check-in (Menit)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('late_tolerance')
                    ->label('Terlambat (Menit)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('early_leave_tolerance')
                    ->label('Pulang Awal (Menit)')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
