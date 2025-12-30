<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Laravolt\Indonesia\Models\City;
use Filament\Forms\Components\Select;
use Laravolt\Indonesia\Models\District;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Validation\Rule;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    // Ikon: pelanggan (user group / office)
    protected static ?string $navigationIcon  = 'heroicon-o-building-office';
    protected static ?int    $navigationSort  = 10;

    // Label menu (ID)
    protected static ?string $navigationLabel = 'Customer';
    protected static ?string $modelLabel       = 'Customer';
    protected static ?string $pluralModelLabel = 'Customer';
    protected static ?string $navigationGroup = 'Data Customer';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('KonsumenID')
                ->required()
                ->maxLength(50)
                ->rule('regex:/^[\pL\pN ._-]+$/u')
                ->validationAttribute('KonsumenID')
                ->unique(ignoreRecord: true)
                ->placeholder('Contoh: Suzuki 4W / Yamaha'),
            
            Forms\Components\TextInput::make('name')
                ->label('Nama Konsumen')
                ->placeholder('PT Yamaha Indonesia Motor Manufacturing')
                ->required()
                ->maxLength(200),

            Forms\Components\Textarea::make('address')
                ->label('Alamat')
                ->rows(3)
                ->placeholder('Jl. KRT Radjiman Widyodiningrat, Pulogadung')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('phone')
                ->label('Nomor Telp')
                ->placeholder('021-4612222 / 08xxxxxxxxxx')
                ->maxLength(50),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->recordUrl(fn () => null)
            ->recordAction('view')
            ->defaultSort('code', 'asc')
            ->persistSortInSession(false)
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('KonsumenID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Konsumen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(60)
                    ->tooltip(fn ($state ) => $state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Telp')
                    ->searchable(),
            ])
            ->filters([])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()->label('')->icon('heroicon-m-eye'),
                \Filament\Tables\Actions\EditAction::make()->label(''),
                \Filament\Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
