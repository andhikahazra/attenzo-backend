<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacePhotoResource\Pages;
use App\Models\FacePhoto;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FacePhotoResource extends Resource
{
    protected static ?string $model = FacePhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Face Photos')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->required()
                            ->searchable() // bisa ketik untuk cari nama
                            ->preload()    // load semua opsi saat dropdown dibuka
                            ->relationship('user', 'name'),
                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Upload Photos')
                            ->multiple()       // bisa upload banyak
                            ->image()
                            ->directory('face_photos')
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
                Tables\Columns\ImageColumn::make('photo_path')->disk('public')->label('Photo'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacePhotos::route('/'),
            'create' => Pages\CreateFacePhoto::route('/create'),
            'edit' => Pages\EditFacePhoto::route('/{record}/edit'),
        ];
    }
}
