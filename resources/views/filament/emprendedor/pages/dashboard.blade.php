<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Mensaje de bienvenida --}}
        <div class="rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 p-6 text-white shadow-lg">
            <h2 class="text-2xl font-bold">
                ¡Bienvenido, {{ auth()->user()->full_name }}!
            </h2>
            <p class="mt-2 text-primary-100">
                Este es tu panel de control. Aquí puedes ver toda tu información y gestionar tu emprendimiento.
            </p>
        </div>

        {{-- Widgets --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
    </div>
</x-filament-panels::page>
