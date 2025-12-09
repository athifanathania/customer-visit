<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitScheduleResource\Pages;
use App\Models\VisitSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Maatwebsite\Excel\Excel;
use Filament\Tables\Actions\Action;

class VisitScheduleResource extends Resource
{
    protected static ?string $model = VisitSchedule::class;

    // Sidebar
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?int    $navigationSort  = 20;
    protected static ?string $navigationGroup = 'Data Kunjungan';
    protected static ?string $navigationLabel = 'Visit Schedules';
    protected static ?string $modelLabel       = 'Visit Schedule';
    protected static ?string $pluralModelLabel = 'Visit Schedules';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('cust_id')
                ->label('Customer')
                // pastikan relasi exists di model VisitSchedule: customer()->belongsTo(Customer::class,'cust_id')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('title')
                ->label('Judul/Agenda')
                ->placeholder('Contoh: Kunjungan audit kualitas')
                ->required()
                ->maxLength(160),

            Forms\Components\DateTimePicker::make('scheduled_at')
                    ->required()->seconds(false)->label('Tanggal & Waktu'),

            Forms\Components\TextInput::make('location')
                ->label('Lokasi')
                ->placeholder('Alamat / link meeting online')
                // ->required()
                ->maxLength(180),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'planned'  => 'Direncanakan',
                    'done'     => 'Selesai',
                    'canceled' => 'Dibatalkan',
                ])
                ->default('planned')
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn () => null)
            ->recordAction('view')

            ->defaultSort('scheduled_at', 'desc')
            ->persistSortInSession(false)
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul/Agenda')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Tanggal & Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => (
                        ($state === 'planned' && optional($record->scheduled_at)->isPast())
                            ? 'Overdue' : match ($state) {
                                'planned'  => 'Direncanakan',
                                'done'     => 'Selesai',
                                'canceled' => 'Dibatalkan',
                                default    => $state,
                            }
                    ))
                    ->colors([
                        'danger'  => fn ($state, $record) => $state === 'planned' && optional($record->scheduled_at)->isPast(),
                        'primary' => fn ($state, $record) => $state === 'planned' && optional($record->scheduled_at)->isFuture(),
                        'success' => 'done',
                        'gray'    => 'canceled',
                    ])
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('cust_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'planned'  => 'Planned',
                        'done'     => 'Done',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->headerActions([
                Action::make('timeline')
                    ->label('Lihat Timeline')
                    ->icon('heroicon-m-chart-bar')
                    ->url(fn () => static::getUrl('timeline'))
                    ->color('gray'),

                ExportAction::make('export_all')
                    ->label('Export')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable() 
                            ->withColumns([
                                \pxlrbt\FilamentExcel\Columns\Column::make('customer.name')->heading('Customer'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('title')->heading('Judul/Agenda'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('scheduled_at')->heading('Tanggal & Waktu')
                                    ->formatStateUsing(fn ($state) => optional($state)->format('d/m/Y H:i')),
                                \pxlrbt\FilamentExcel\Columns\Column::make('location')->heading('Lokasi'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('status')->heading('Status')
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($state === 'planned' && optional($record->scheduled_at)->isPast()) {
                                            return 'Overdue';
                                        }
                                        return match ($state) {
                                            'planned'  => 'Direncanakan',
                                            'done'     => 'Selesai',
                                            'canceled' => 'Dibatalkan',
                                            default    => $state,
                                        };
                                    }),
                            ])
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX) 
                            ->withFilename('visit_schedules_'.now()->format('Ymd_His'))
                            ->queue(false), 
                    ]),
            ])
            ->actions([
                ViewAction::make('view')->label('')->icon('heroicon-m-eye'),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
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
            'index'    => Pages\ListVisitSchedules::route('/'),
            'create'   => Pages\CreateVisitSchedule::route('/create'),
            'edit'     => Pages\EditVisitSchedule::route('/{record}/edit'),
            'timeline' => Pages\TimelineVisitSchedules::route('/timeline'),
        ];
    }
}
