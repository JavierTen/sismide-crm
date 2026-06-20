<div class="flex items-center gap-x-2 px-3">
    <label for="year-switcher" class="text-sm font-medium text-gray-500 dark:text-gray-400">
        Año
    </label>

    <select
        id="year-switcher"
        wire:model.live="selectedYear"
        class="fi-input block rounded-lg border-none bg-gray-100 py-1.5 text-sm text-gray-950 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white"
    >
        @foreach ($years as $year)
            <option value="{{ $year }}">{{ $year }}</option>
        @endforeach
        <option value="{{ \App\Support\YearContext::ALL_YEARS }}">Todos los años</option>
    </select>
</div>
