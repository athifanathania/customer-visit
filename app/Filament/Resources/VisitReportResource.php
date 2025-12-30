<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitReportResource\Pages;
use App\Filament\Resources\VisitReportResource\RelationManagers;
use App\Models\VisitReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use App\Models\VisitSchedule;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Forms\Set;

class VisitReportResource extends Resource
{
    protected static ?string $model = VisitReport::class;

    // Ikon: dokumen/laporan
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int    $navigationSort = 30;

    protected static ?string $navigationLabel = 'Visit Reports';     
    protected static ?string $modelLabel       = 'Visit Report';
    protected static ?string $pluralModelLabel = 'Visit Reports';
    protected static ?string $navigationGroup = 'Data Kunjungan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cust_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih customer…')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('schedule_id', null)),
                    
                Forms\Components\Select::make('schedule_id')
                    ->label('Jadwal Terkait')
                    ->relationship(
                        name: 'schedule',
                        titleAttribute: 'title',
                        modifyQueryUsing: function (Builder $query, Get $get) {
                            if ($custId = $get('cust_id')) {
                                $query->where('cust_id', $custId);
                            } else {
                                // Jangan tampilkan apa-apa ketika customer belum dipilih
                                $query->whereRaw('1 = 0');
                            }
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (VisitSchedule $s) => sprintf(
                        '%s — %s (%s)',
                        $s->title,
                        optional($s->scheduled_at)->format('d/m/Y H:i'),
                        ucfirst($s->status),
                    ))
                    ->disabled(fn (Get $get) => blank($get('cust_id'))) // kunci sampai customer dipilih
                    ->helperText(fn (Get $get) => blank($get('cust_id'))
                        ? 'Pilih customer terlebih dahulu.'
                        : null)
                    ->required()
                    ->searchable()
                    ->preload(),

                Hidden::make('created_by')
                    ->default(fn () => Auth::id()),

                Forms\Components\DateTimePicker::make('visit_date')
                    ->required()->seconds(false),
                Forms\Components\TextInput::make('location')
                    ->label('Lokasi')
                    ->placeholder('Alamat kantor / lokasi kunjungan / link meeting')
                    ->maxLength(180),
                Forms\Components\TextInput::make('pic_imm')
                    ->label('PIC IMM')->helperText('Gunakan koma (,) untuk auto poin jika PIC lebih dari satu.')
                    ->maxLength(120),

                Forms\Components\TextInput::make('cust_pic')
                    ->label('PIC Customer (Buyer)')->helperText('Gunakan koma (,) untuk auto poin jika PIC lebih dari satu.')
                    ->maxLength(120),
                Forms\Components\Textarea::make('discussion_points')
                    ->label('Point Discuss')
                    ->rows(4)
                    ->placeholder("Tulis satu poin per baris.\nContoh:\nDiskusi harga\nFollow up jadwal trial")
                    ->helperText('Enter = baris baru (auto bullet saat tampil).')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('problem_info')
                    ->label('Problem info')
                    ->rows(5)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('countermeasure')
                    ->label('Countermeasure')
                    ->rows(5)
                    ->columnSpanFull(),
                
                Forms\Components\FileUpload::make('attachments')
                    ->label('Lampiran (foto/file)')
                    ->multiple()
                    ->downloadable()
                    ->openable()
                    ->imagePreviewHeight(100)
                    ->reorderable()
                    ->directory('visit-reports')   
                    ->columnSpanFull(),
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
            ->defaultSort('visit_date', 'desc')
            ->persistSortInSession(false)
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'width:10rem'])
                    ->extraCellAttributes(['style' => 'width:10rem; white-space:normal']),

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Tgl Visit')
                    ->date('d/m/y')                                 
                    ->description(fn ($record) =>
                        optional($record->visit_date)->format('H:i') 
                    , position: 'below')
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'width:6.5rem'])     // ~104px
                    ->extraCellAttributes(['style' => 'width:6.5rem; white-space:normal']),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('pic_imm')
                    ->label('PIC IMM')
                    ->formatStateUsing(function ($state) {
                        $items = preg_split('/\s*,\s*/', (string) $state, -1, PREG_SPLIT_NO_EMPTY);
                        if (empty($items)) return '—';
                        $lis = array_map(fn ($i) => '<li>' . e($i) . '</li>', $items);
                        return '<ul class="list-disc pl-4">' . implode('', $lis) . '</ul>';
                    })
                    ->html()
                    ->wrap()
                    ->toggleable()
                    ->extraAttributes(['class' => 'w-44']),

                Tables\Columns\TextColumn::make('cust_pic')
                    ->label('PIC Cust')
                    ->formatStateUsing(function ($state) {
                        $items = preg_split('/\s*,\s*/', (string) $state, -1, PREG_SPLIT_NO_EMPTY);
                        if (empty($items)) return '—';
                        $lis = array_map(fn ($i) => '<li>' . e($i) . '</li>', $items);
                        return '<ul class="list-disc pl-4">' . implode('', $lis) . '</ul>';
                    })
                    ->html()
                    ->wrap()
                    ->toggleable()
                    ->extraAttributes(['class' => 'w-44']),

                Tables\Columns\TextColumn::make('discussion_points')
                    ->label('Point Discuss')
                    ->formatStateUsing(function ($state) {
                        $text = trim((string) $state);
                        if ($text === '') return '—';
                        $items = preg_split('/\r\n|\r|\n/', $text);
                        $items = array_filter(array_map('trim', $items), fn ($v) => $v !== '');
                        if (empty($items)) return '—';
                        $lis = array_map(fn ($i) => '<li>' . e($i) . '</li>', $items);
                        return '<ul class="list-disc pl-4">' . implode('', $lis) . '</ul>';
                    })
                    ->html()
                    ->wrap()
                    ->extraHeaderAttributes(['style' => 'width:10rem'])
                    ->extraCellAttributes(['style' => 'width:10rem; white-space:normal'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('problem_info')
                    ->label('Problem info')
                    ->formatStateUsing(function ($state) {
                        $text = trim((string) $state);
                        if ($text === '') return '—';
                        $items = preg_split('/\r\n|\r|\n/', $text);
                        $items = array_filter(array_map('trim', $items), fn ($v) => $v !== '');
                        if (empty($items)) return '—';
                        $lis = array_map(fn ($i) => '<li>' . e($i) . '</li>', $items);
                        return '<ul class="list-disc pl-4">' . implode('', $lis) . '</ul>';
                    })
                    ->html()
                    ->wrap()
                    ->extraAttributes(['class' => 'max-w-[1100px]']),

                Tables\Columns\TextColumn::make('countermeasure')
                    ->label('Countermeasure')
                    ->formatStateUsing(function ($state) {
                        $text = trim((string) $state);
                        if ($text === '') return '—';

                        $items = preg_split('/\r\n|\r|\n/', $text);
                        $items = array_filter(array_map('trim', $items), fn ($v) => $v !== '');

                        if (empty($items)) return '—';

                        $lis = array_map(fn ($i) => '<li>' . e($i) . '</li>', $items);
                        return '<ul class="list-disc pl-4">' . implode('', $lis) . '</ul>';
                    })
                    ->html()
                    ->wrap()
                    ->extraHeaderAttributes(['style' => 'width:10rem'])
                    ->extraCellAttributes(['style' => 'width:10rem; white-space:normal'])
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('attachments_flag')
                    ->label('Lampiran')
                    ->state(fn ($record) => filled($record->attachments))
                    ->boolean()
                    ->trueIcon('heroicon-m-paper-clip')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->falseIcon('heroicon-m-minus-small')
                    ->tooltip(fn ($record) => $record->attachments
                        ? count($record->attachments) . ' file'
                        : 'Tidak ada lampiran'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter customer
                SelectFilter::make('cust_id')
                    ->label('Customer')
                    ->relationship('customer', 'name'),

                // Filter rentang tanggal kunjungan
                Filter::make('visit_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('visit_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('visit_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $from  = $data['from']  ?? null;
                        $until = $data['until'] ?? null;
                        if (! $from && ! $until) return null;
                        return 'Tanggal: ' . ($from ? \Carbon\Carbon::parse($from)->format('d M Y') : '…')
                            . ' → ' . ($until ? \Carbon\Carbon::parse($until)->format('d M Y') : '…');
                    }),
            ])
            ->headerActions([
                ExportAction::make('export_all')
                    ->label('Export')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable() // hormati filter & search aktif
                            ->withColumns([
                                Column::make('customer.name')->heading('Customer'),
                                Column::make('visit_date')->heading('Tanggal Kunjungan')
                                    ->formatStateUsing(fn ($state) => optional($state)->format('d/m/Y H:i')),
                                Column::make('location')->heading('Lokasi'),
                                Column::make('pic_imm')->heading('PIC IMM'),
                                Column::make('cust_pic')->heading('PIC Customer'),
                                Column::make('discussion_points')->heading('Point Discuss')
                                    ->formatStateUsing(function ($state) {
                                        $items = preg_split('/\r\n|\r|\n/', (string) $state);
                                        $items = array_filter(array_map('trim', $items), fn ($v) => $v !== '');
                                        return implode(' | ', $items);
                                    }),
                                Column::make('problem_info')->heading('Problem info')
                                    ->formatStateUsing(function ($state) {
                                        $items = preg_split('/\r\n|\r|\n/', (string) $state);
                                        $items = array_filter(array_map('trim', $items), fn ($v) => $v !== '');
                                        return implode(' | ', $items); // Bisa ganti ' | ' jadi "\n" kalau ingin line-break di Excel
                                    }),

                                Column::make('countermeasure')->heading('Countermeasure')
                                    ->formatStateUsing(function ($state) {
                                        $items = preg_split('/\r\n|\r|\n/', (string) $state);
                                        $items = array_filter(array_map('trim', $items), fn ($v) => $v !== '');
                                        return implode(' | ', $items);
                                    }),
                                Column::make('attachments')->heading('Lampiran')
                                    ->formatStateUsing(function ($state) {
                                        if (blank($state)) return '';
                                        // $state = array path/file; gabung nama file saja
                                        $names = array_map(function ($path) {
                                            return is_string($path) ? basename($path) : (is_array($path) ? ($path['name'] ?? $path['path'] ?? 'file') : 'file');
                                        }, (array) $state);
                                        return implode(' | ', $names);
                                    }),
                            ])
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withFilename('visit_reports_'.now()->format('Ymd_His'))
                            ->queue(false),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->icon('heroicon-m-eye'),
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
            'index'  => Pages\ListVisitReports::route('/'),
            'create' => Pages\CreateVisitReport::route('/create'),
            'edit'   => Pages\EditVisitReport::route('/{record}/edit'),
        ];
    }
}
