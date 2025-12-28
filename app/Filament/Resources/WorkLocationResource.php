<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkLocationResource\Pages;
use App\Models\WorkLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Dotswan\MapPicker\Fields\Map;

class WorkLocationResource extends Resource
{
    protected static ?string $model = WorkLocation::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('latitude')
                    ->required()
                    ->numeric(),

                Forms\Components\TextInput::make('longitude')
                    ->required()
                    ->numeric(),

                Forms\Components\TextInput::make('allowed_radius_meters')
                    ->required()
                    ->numeric(),

                Map::make('location')
                    ->label('Location')
                    ->columnSpanFull()

                    // Map Config
                    ->defaultLocation(latitude: -6.200000, longitude: 106.816666)
                    ->zoom(15)
                    ->minZoom(0)
                    ->maxZoom(28)
                    ->draggable(true)
                    ->clickable(true)
                    ->detectRetina(true)
                    ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")

                    // Marker Config (stabil)
                    ->showMarker(true)
                    ->markerIconUrl('https://www.svgrepo.com/show/476623/map-marker.svg')
                    ->markerIconSize([32, 32])
                    ->markerIconAnchor([16, 32])

                    // Controls
                    ->showFullscreenControl(true)
                    ->showZoomControl(true)

                    // Fix map ketarik-tarik
                    ->liveLocation(false)   // WAJIB dimatikan
                    ->showMyLocationButton(true)

                    // Fix GeoMan conflict
                    ->geoMan(true)
                    ->geoManEditable(false)
                    ->drawMarker(false)
                    ->drawCircleMarker(false)
                    ->drawPolygon(false)
                    ->drawPolyline(false)
                    ->drawCircle(false)
                    ->drawRectangle(false)
                    ->cutPolygon(false)
                    ->editPolygon(false)
                    ->deleteLayer(false)
                    ->dragMode(false)
                    ->rotateMode(false)

                    // Styles
                    ->extraStyles([
                        'min-height: 50vh',
                        'border-radius: 20px',
                    ])

                    // State Handling
                    ->afterStateUpdated(function (Set $set, ?array $state): void {
                        if (!$state) return;

                        if (isset($state['lat'])) {
                            $set('latitude', $state['lat']);
                        }

                        if (isset($state['lng'])) {
                            $set('longitude', $state['lng']);
                        }

                        if (isset($state['geojson']) && $state['geojson'] !== null) {
                            $set('geojson', json_encode($state['geojson']));
                        }
                    })

                    ->afterStateHydrated(function ($state, $record, Set $set): void {
                        if (!$record) return;

                        $set('location', [
                            'lat' => $record->latitude,
                            'lng' => $record->longitude,
                            'geojson' => $record->geojson ? json_decode($record->geojson) : null,
                        ]);
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')->searchable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('allowed_radius_meters')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
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
            'index' => Pages\ListWorkLocations::route('/'),
            'create' => Pages\CreateWorkLocation::route('/create'),
            'edit' => Pages\EditWorkLocation::route('/{record}/edit'),
        ];
    }
}
