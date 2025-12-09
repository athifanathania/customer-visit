<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Judul & deskripsi singkat --}}
        <div>
            <h2 class="text-xl font-semibold">
                Schedule Timeline
            </h2>
            <p class="text-sm text-gray-600">
                Lihat ringkasan jadwal kunjungan per customer per bulan atau per minggu
                untuk tahun yang dipilih.
            </p>
        </div>

        {{-- FILTER BAR --}}
        <div class="flex flex-wrap gap-4 items-end bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            {{-- Filter Tahun --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">
                    Tahun
                </label>
                <select
                    wire:model.live="selectedYear"
                    class="fi-input block rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    @foreach ($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Customer (opsional) --}}
            <div class="flex flex-col gap-1 min-w-[220px]">
                <label class="text-xs font-medium text-gray-600">
                    Customer (opsional)
                </label>
                <select
                    wire:model.live="selectedCustomerId"
                    class="fi-input block rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Semua customer</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Mode tampilan: Bulanan / Mingguan --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">
                    Mode tampilan
                </label>
                <select
                    wire:model.live="viewMode"
                    class="fi-input block rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="month">Bulanan</option>
                    <option value="week">Mingguan</option>
                </select>
            </div>

            {{-- Jika mode week, pilih bulan --}}
            @if ($viewMode === 'week')
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-600">
                        Bulan (untuk mode mingguan)
                    </label>
                    <select
                        wire:model.live="selectedMonthForWeek"
                        class="fi-input block rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        @foreach ($months as $monthNumber => $monthLabel)
                            <option value="{{ $monthNumber }}">{{ $monthLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- GRID TIMELINE --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 overflow-x-auto">
            @if (empty($timelineRows))
                <div class="text-sm text-gray-500">
                    Tidak ada jadwal untuk tahun {{ $selectedYear }}
                    @if ($selectedCustomerId)
                        pada customer yang dipilih.
                    @else
                        pada semua customer.
                    @endif
                </div>
            @else
                {{-- MODE BULANAN --}}
                @if ($viewMode === 'month')
                    <table class="w-full table-fixed border border-gray-200 text-xs border-collapse">
                        <thead>
                            <tr>
                                {{-- Header customer, rata kiri --}}
                                <th
                                    class="sticky left-0 z-10 bg-white border border-gray-200 px-3 py-2 
                                        text-left text-[0.7rem] font-semibold text-gray-700 w-72"
                                >
                                    Customer
                                </th>
                                @foreach ($months as $monthNumber => $monthLabel)
                                    <th
                                        class="border border-gray-200 px-2 py-2 text-center text-[0.7rem] 
                                            font-semibold text-gray-700"
                                    >
                                        {{ $monthLabel }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($timelineRows as $row)
                                <tr class="align-top">
                                    {{-- Nama customer (rata kiri, sticky) --}}
                                    <th
                                        class="sticky left-0 z-10 bg-white border border-gray-200 px-3 py-2 text-[0.75rem] font-medium text-gray-900 max-w-[240px] text-left"
                                    >
                                        {{ $row['customer_name'] }}
                                    </th>

                                    {{-- Kolom per bulan --}}
                                    @foreach ($months as $monthNumber => $monthLabel)
                                        @php
                                            $items = $row['months'][$monthNumber] ?? [];
                                        @endphp
                                        <td class="border border-gray-200 px-2 py-2 align-top">
                                            @if (! empty($items))
                                                @foreach (array_slice($items, 0, 3) as $item)
                                                    @php
                                                        if ($item['status'] === 'canceled') {
                                                            $style = 'background-color:#f3f4f6;color:#4b5563;border-color:#e5e7eb;';
                                                        } elseif ($item['status'] === 'done') {
                                                            $style = 'background-color:#dcfce7;color:#166534;border-color:#bbf7d0;';
                                                        } elseif ($item['status'] === 'planned' && $item['is_overdue']) {
                                                            $style = 'background-color:#fee2e2;color:#b91c1c;border-color:#fecaca;';
                                                        } else {
                                                            $style = 'background-color:#fef9c3;color:#92400e;border-color:#fef08a;';
                                                        }

                                                        $day = str_pad($item['day'], 2, '0', STR_PAD_LEFT);
                                                    @endphp
                                                    <div
                                                        class="mb-1 inline-flex max-w-full items-center gap-1 rounded-full px-2 py-0.5 text-[0.65rem] font-medium border"
                                                        style="{{ $style }}"
                                                        title="{{ $item['day'] }} {{ $monthLabel }} {{ $selectedYear }} — {{ $item['title'] }}"
                                                    >
                                                        <span class="shrink-0">{{ $day }}</span>
                                                    </div>
                                                @endforeach

                                                @if (count($items) > 3)
                                                    <div class="text-[0.65rem] text-gray-500 mt-1">
                                                        +{{ count($items) - 3 }} lagi
                                                    </div>
                                                @endif
                                            @else
                                                <div class="text-[0.6rem] text-gray-300 text-center">
                                                    —
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                {{-- MODE MINGGUAN --}}
                @else
                    <table class="min-w-full border border-gray-200 text-xs border-collapse">
                        <thead>
                            <tr>
                                <th
                                    class="sticky left-0 z-10 bg-white border border-gray-200 px-3 py-2 text-left text-[0.7rem] font-semibold text-gray-700"
                                >
                                    Customer
                                </th>
                                @for ($w = 1; $w <= 5; $w++)
                                    <th
                                        class="border border-gray-200 px-2 py-2 text-center text-[0.7rem] font-semibold text-gray-700"
                                    >
                                        Minggu {{ $w }}
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeklyRows as $row)
                                <tr class="align-top">
                                    <th
                                        class="sticky left-0 z-10 bg-white border border-gray-200 px-3 py-2 text-[0.75rem] font-medium text-gray-900 max-w-[240px] text-left"
                                    >
                                        {{ $row['customer_name'] }}
                                    </th>

                                    @for ($w = 1; $w <= 5; $w++)
                                        @php
                                            $items = $row['weeks'][$w] ?? [];
                                            $monthLabel = $months[$selectedMonthForWeek] ?? '';
                                        @endphp
                                        <td class="border border-gray-200 px-2 py-2 align-top">
                                            @if (! empty($items))
                                                @foreach ($items as $item)
                                                    @php
                                                        if ($item['status'] === 'canceled') {
                                                            $style = 'background-color:#f3f4f6;color:#4b5563;border-color:#e5e7eb;';
                                                        } elseif ($item['status'] === 'done') {
                                                            $style = 'background-color:#dcfce7;color:#166534;border-color:#bbf7d0;';
                                                        } elseif ($item['status'] === 'planned' && $item['is_overdue']) {
                                                            $style = 'background-color:#fee2e2;color:#b91c1c;border-color:#fecaca;';
                                                        } else {
                                                            $style = 'background-color:#fef9c3;color:#92400e;border-color:#fef08a;';
                                                        }

                                                        $day   = str_pad($item['day'], 2, '0', STR_PAD_LEFT);
                                                    @endphp

                                                    <div
                                                        class="mb-1 inline-flex max-w-full items-center gap-1 rounded-full px-2 py-0.5 text-[0.65rem] font-medium border"
                                                        style="{{ $style }}"
                                                        title="Minggu {{ $w }} — {{ $item['day'] }} {{ $monthLabel }} {{ $selectedYear }} — {{ $item['title'] }}"
                                                    >
                                                        <span class="shrink-0">{{ $day }}</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="text-[0.6rem] text-gray-300 text-center">
                                                    —
                                                </div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
