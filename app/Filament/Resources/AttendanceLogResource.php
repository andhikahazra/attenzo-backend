<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceLogResource\Pages;
use App\Models\AttendanceLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Info')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('user', 'name'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'matched' => 'Matched',
                                'not_matched' => 'Not Matched',
                            ])
                            ->required(),

                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Attendance Photo')
                            ->image()
                            ->directory('attendance_photos')
                            ->nullable(),

                        Forms\Components\DatePicker::make('attendance_date')
                            ->label('Attendance Date')
                            ->required(),

                        Forms\Components\TimePicker::make('attendance_time')
                            ->label('Attendance Time')
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'check_in' => 'Check In',
                                'check_out' => 'Check Out',
                            ])
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status'),
                Tables\Columns\ImageColumn::make('photo_path')
                    ->disk('public')
                    ->label('Photo'),
                Tables\Columns\TextColumn::make('attendance_date')->date()->label('Date'),
                Tables\Columns\TextColumn::make('attendance_time')->label('Time'),
                Tables\Columns\TextColumn::make('type')->label('Type'),
                // Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                // Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceLogs::route('/'),
            'create' => Pages\CreateAttendanceLog::route('/create'),
            'edit' => Pages\EditAttendanceLog::route('/{record}/edit'),
        ];
    }
}
