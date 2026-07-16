<x-filament-widgets::widget>
    <x-filament::section heading="Avance por Municipio" icon="heroicon-o-map-pin">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 px-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Municipio</th>
                        <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Meta</th>
                        <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Registrados</th>
                        <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Caracterizados</th>
                        <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Diagnosticados</th>
                        <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pendientes</th>
                        <th class="py-2 px-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Avance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-2.5 px-3 font-medium text-gray-900 dark:text-gray-100">{{ $row['city'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['meta'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['registered'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['characterized'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['diagnosed'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['pending'] }}</td>
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[60px]">
                                    <div class="h-2 rounded-full bg-primary-600"
                                         style="width: {{ min($row['avance'], 100) }}%">
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-12 text-right">
                                    {{ $row['avance'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                        <td class="py-2.5 px-3 text-gray-900 dark:text-gray-100">TOTAL</td>
                        <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totals['meta'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totals['registered'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totals['characterized'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totals['diagnosed'] }}</td>
                        <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totals['pending'] }}</td>
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[60px]">
                                    <div class="h-2 rounded-full bg-primary-600"
                                         style="width: {{ min($totals['avance'], 100) }}%">
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-gray-900 dark:text-gray-100 w-12 text-right">
                                    {{ $totals['avance'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
