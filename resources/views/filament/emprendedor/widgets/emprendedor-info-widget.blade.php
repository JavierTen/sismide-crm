<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Información perosonal  --}}
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user-circle class="h-6 w-6 text-primary-500" />
                <span class="text-xl font-bold">Mi Información</span>
            </div>
        </x-slot>

        @php
            $entrepreneur = $this->getEntrepreneur();
            $business = $this->getBusiness();
        @endphp

        <div class="grid gap-6 md:grid-cols-2">
            {{-- Columna 1: Datos Personales --}}
            <div class="space-y-4">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-primary-100 p-2 dark:bg-primary-900/30">
                            <x-heroicon-o-user class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Nombre completo
                            </dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $entrepreneur->full_name ?? 'No especificado' }}
                            </dd>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                            <x-heroicon-o-map-pin class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Municipio / Zona de atención
                            </dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $entrepreneur->city->name ?? 'No especificado' }}
                            </dd>
                            @if ($business?->business_zone)
                                <dd class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                                    Zona: {{ ucfirst($business->business_zone) }}
                                </dd>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                            <x-heroicon-o-briefcase class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Sector productivo
                            </dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $business?->productiveLine?->name ?? 'No especificado' }}
                            </dd>
                            @if ($business?->economicActivity)
                                <dd class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $business->economicActivity->name }}
                                </dd>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna 2: Datos del Negocio --}}
            <div class="space-y-4">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                            <x-heroicon-o-building-storefront class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Nombre del emprendimiento
                            </dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $business?->business_name ?? 'Sin emprendimiento registrado' }}
                            </dd>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-orange-100 p-2 dark:bg-orange-900/30">
                            <x-heroicon-o-chart-bar class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Estado actual del negocio
                            </dt>
                            <dd class="mt-1">
                                @php
                                    $stage = $business?->entrepreneurshipStage?->name ?? 'No especificado';
                                    $stageColors = [
                                        'Idea' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        'En marcha' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'Consolidado' =>
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    ];
                                    $colorClass =
                                        $stageColors[$stage] ??
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                @endphp
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $colorClass }}">
                                    {{ $stage }}
                                </span>
                            </dd>

                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-teal-100 p-2 dark:bg-teal-900/30">
                            <x-heroicon-o-calendar class="h-5 w-5 text-teal-600 dark:text-teal-400" />
                        </div>
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Fecha de registro en el proyecto
                            </dt>
                            <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $entrepreneur->admission_date?->format('d/m/Y') ?? $entrepreneur->created_at->format('d/m/Y') }}
                            </dd>
                            @if ($entrepreneur->project)
                                <dd class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                                    Proyecto: {{ $entrepreneur->project->name }}
                                </dd>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Información adicional (si existe) --}}
        @if ($business?->description)
            <div class="mt-6 rounded-lg border-t border-gray-200 pt-4 dark:border-gray-700">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Descripción del emprendimiento
                </dt>
                <dd class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ $business->description }}
                </dd>
            </div>
        @endif
    </x-filament::section>

    {{-- Nueva Sección: Mis Visitas --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-calendar-days class="h-6 w-6 text-primary-500" />
                <span class="text-xl font-bold">Mis Visitas</span>
            </div>
            @if ($entrepreneur->visits()->count() > 0)
                <div class="ml-auto">
                    <span
                        class="rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                        {{ $entrepreneur->visits()->count() }}
                        {{ $entrepreneur->visits()->count() === 1 ? 'visita' : 'visitas' }}
                    </span>
                </div>
            @endif
        </x-slot>

        @php
            $visits = $this->getVisits();
        @endphp

        @if ($visits->count() > 0)
            <div class="space-y-4">
                @foreach ($visits as $visit)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

                            {{-- Fecha y Hora --}}
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                                <div class="flex items-start gap-3">
                                    <div class="rounded-lg bg-primary-100 p-2 dark:bg-primary-900/30">
                                        <x-heroicon-o-calendar class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Fecha y Hora
                                        </dt>
                                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $visit->visit_date->format('d/m/Y') }}
                                        </dd>
                                        <dd
                                            class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 flex items-center gap-1">
                                            <x-heroicon-m-clock class="h-3 w-3" />
                                            {{ \Carbon\Carbon::parse($visit->visit_time)->format('h:i A') }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            {{-- Tipo de Visita --}}
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                                <div class="flex items-start gap-3">
                                    <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                                        @if ($visit->visit_type === 'Presencial')
                                            <x-heroicon-o-building-office-2
                                                class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        @else
                                            <x-heroicon-o-phone class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Tipo de Visita
                                        </dt>
                                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $visit->visit_type }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            {{-- Gestor --}}
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                                <div class="flex items-start gap-3">
                                    <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                                        <x-heroicon-o-user class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Gestor Asignado
                                        </dt>
                                        <dd class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $visit->manager?->name ?? 'Sin asignar' }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            {{-- Estado y Fortalecida --}}
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                                <div class="flex items-start gap-3">
                                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                                        @if ($visit->strengthened)
                                            <x-heroicon-o-check-circle
                                                class="h-5 w-5 text-green-600 dark:text-green-400" />
                                        @else
                                            <x-heroicon-o-clock class="h-5 w-5 text-green-600 dark:text-green-400" />
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Estado
                                        </dt>
                                        @if ($visit->rescheduled)
                                            <dd class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">
                                                Reprogramada
                                            </dd>
                                        @else
                                            <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                                Confirmada
                                            </dd>
                                        @endif
                                        <dd class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                            @if ($visit->strengthened)
                                                <span
                                                    class="text-green-600 dark:text-green-400 font-medium">Fortalecida</span>
                                            @else
                                                <span>Sin fortalecer</span>
                                            @endif
                                        </dd>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Razón de reprogramación (si existe) --}}
                        @if ($visit->rescheduled && $visit->reschedule_reason)
                            <div class="mt-3 rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/10">
                                <div class="flex gap-2">
                                    <x-heroicon-m-information-circle
                                        class="h-4 w-4 flex-shrink-0 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-yellow-800 dark:text-yellow-300">
                                            Motivo de reprogramación:
                                        </p>
                                        <p class="mt-1 text-xs text-yellow-700 dark:text-yellow-400">
                                            {{ $visit->reschedule_reason }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Mensaje si hay más visitas --}}
            @if ($entrepreneur->visits()->count() > 10)
                <div
                    class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <x-heroicon-m-information-circle class="h-5 w-5 text-gray-400" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Mostrando las últimas 10 visitas de {{ $entrepreneur->visits()->count() }} totales
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- Estado vacío --}}
            <div class="flex flex-col items-center justify-center rounded-lg bg-gray-50 p-12 dark:bg-gray-800">
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                    <x-heroicon-o-calendar-days class="h-10 w-10 text-gray-400 dark:text-gray-500" />
                </div>
                <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-white">
                    No hay visitas registradas
                </h3>
                <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
                    Aún no tienes visitas programadas o realizadas.
                </p>
            </div>
        @endif
    </x-filament::section>

        {{-- Nueva Sección: Mi Caracterización --}}
        @php
        $characterization = $this->getLatestCharacterization();
    @endphp

    @if ($characterization)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-check class="h-6 w-6 text-primary-500" />
                        <span class="text-xl font-bold">Mi Caracterización</span>
                    </div>
                </div>
            </x-slot>

            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Fecha:
                {{ $characterization->characterization_date ? \Carbon\Carbon::parse($characterization->characterization_date)->format('d/m/Y') : 'No especificada' }}
                @if ($characterization->manager)
                    <span class="ml-4">• Gestor: {{ $characterization->manager->name }}</span>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                {{-- Columna 1: Información del Negocio --}}
                <div class="space-y-3">
                    {{-- Actividad Económica --}}
                    @if ($characterization->economicActivity)
                        <div
                            class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-briefcase
                                    class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Actividad Económica
                                    </div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $characterization->economicActivity->name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Tipo de Negocio --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-building-storefront
                                class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Tipo de Negocio
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ ucfirst($characterization->business_type ?? 'No especificado') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Antigüedad del Negocio --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-calendar class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Antigüedad del Negocio
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    @php
                                        $ages = [
                                            'over_6_months' => 'Más de 6 meses',
                                            'over_12_months' => 'Más de 12 meses',
                                            'over_24_months' => 'Más de 24 meses',
                                        ];
                                    @endphp
                                    {{ $ages[$characterization->business_age] ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Ventas Mensuales --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-currency-dollar
                                class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Ventas Mensuales Promedio
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    @php
                                        $sales = [
                                            'lt_500000' => 'Menos de $500,000',
                                            '500k_1m' => '$501,000 — $1,000,000',
                                            '1m_2m' => '$1,001,000 — $2,000,000',
                                            '2m_5m' => '$2,001,000 — $5,000,000',
                                            'gt_5m' => 'Más de $5,001,000',
                                        ];
                                    @endphp
                                    {{ $sales[$characterization->average_monthly_sales] ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Columna 2: Información Operativa --}}
                <div class="space-y-3">
                    {{-- Empleos Generados --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-user-group class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Empleos Generados
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    @php
                                        $employees = [
                                            'up_to_2' => 'Hasta 2 empleados',
                                            '3_to_4' => '3 a 4 empleados',
                                            'more_than_5' => 'Más de 5 empleados',
                                        ];
                                    @endphp
                                    {{ $employees[$characterization->employees_generated] ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Estrategias de Promoción --}}
                    @if ($characterization->promotion_strategies)
                        <div
                            class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-megaphone
                                    class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Estrategias de Promoción
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @php
                                            $promotionLabels = [
                                                'word_of_mouth' => 'Voz a voz',
                                                'whatsapp' => 'WhatsApp',
                                                'facebook' => 'Facebook',
                                                'instagram' => 'Instagram',
                                            ];
                                        @endphp
                                        @foreach ($characterization->promotion_strategies as $strategy)
                                            <span
                                                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                {{ $promotionLabels[$strategy] ?? $strategy }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Registros y Documentación --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-document-check
                                class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Documentación
                                </div>
                                <div class="mt-2 space-y-1.5">
                                    <div class="flex items-center gap-2 text-xs">
                                        @if ($characterization->has_accounting_records)
                                            <x-heroicon-s-check-circle class="h-4 w-4 text-green-500" />
                                            <span class="text-gray-700 dark:text-gray-300">Registros contables</span>
                                        @else
                                            <x-heroicon-s-x-circle class="h-4 w-4 text-gray-300 dark:text-gray-600" />
                                            <span class="text-gray-500 dark:text-gray-400">Sin registros
                                                contables</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-xs">
                                        @if ($characterization->has_commercial_registration)
                                            <x-heroicon-s-check-circle class="h-4 w-4 text-green-500" />
                                            <span class="text-gray-700 dark:text-gray-300">Registro comercial</span>
                                        @else
                                            <x-heroicon-s-x-circle class="h-4 w-4 text-gray-300 dark:text-gray-600" />
                                            <span class="text-gray-500 dark:text-gray-400">Sin registro
                                                comercial</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Población Atendida --}}
                    @if ($characterization->population)
                        <div
                            class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-users class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Población Objetivo
                                    </div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $characterization->population->name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Nueva Sección: Documentos y Evidencias --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-folder-open class="h-6 w-6 text-primary-500" />
                    <span class="text-xl font-bold">Documentos y Evidencias</span>
                </div>
            </div>
        </x-slot>

        @php

            $characterizations = $this->getCharacterizations();
            $totalFiles = 0;
            foreach ($characterizations as $char) {
                $totalFiles += count($char->commerce_evidence_path ?? []);
                $totalFiles += count($char->population_evidence_path ?? []);
                $totalFiles += count($char->photo_evidence_path ?? []);
            }
        @endphp

        @if ($totalFiles > 0)
            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {{ $totalFiles }} {{ $totalFiles === 1 ? 'archivo' : 'archivos' }}
            </div>
        @endif

        @if ($characterizations->count() > 0)
            <div class="space-y-4">
                @foreach ($characterizations as $characterization)
                    @php
                        $hasFiles =
                            !empty($characterization->commerce_evidence_path) ||
                            !empty($characterization->population_evidence_path) ||
                            !empty($characterization->photo_evidence_path);
                    @endphp

                    @if ($hasFiles)
                        {{-- Header de la caracterización --}}
                        <div class="mb-2">
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Caracterización -
                                {{ $characterization->characterization_date ? \Carbon\Carbon::parse($characterization->characterization_date)->format('d/m/Y') : 'Sin fecha' }}
                            </div>
                            @if ($characterization->manager)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Gestor: {{ $characterization->manager->name }}
                                </div>
                            @endif
                        </div>

                        {{-- Grid de archivos como cards --}}
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {{-- Evidencias de Comercio --}}
                            @if (!empty($characterization->commerce_evidence_path))
                                @foreach ($characterization->commerce_evidence_path as $index => $file)
                                    <div
                                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                <x-heroicon-o-building-storefront
                                                    class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Evidencia de Comercio
                                                    </div>
                                                    <div
                                                        class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                        Archivo {{ $index + 1 }}
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ Storage::url($file) }}" target="_blank"
                                                class="ml-2 flex-shrink-0 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50">
                                                Ver
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            {{-- Evidencias de Población --}}
                            @if (!empty($characterization->population_evidence_path))
                                @foreach ($characterization->population_evidence_path as $index => $file)
                                    <div
                                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                <x-heroicon-o-user-group
                                                    class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Evidencia de Población
                                                    </div>
                                                    <div
                                                        class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                        Archivo {{ $index + 1 }}
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ Storage::url($file) }}" target="_blank"
                                                class="ml-2 flex-shrink-0 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50">
                                                Ver
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            {{-- Evidencias Fotográficas --}}
                            @if (!empty($characterization->photo_evidence_path))
                                @foreach ($characterization->photo_evidence_path as $index => $file)
                                    <div
                                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                <x-heroicon-o-camera
                                                    class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Fotografía
                                                    </div>
                                                    <div
                                                        class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                        Foto {{ $index + 1 }}
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ Storage::url($file) }}" target="_blank"
                                                class="ml-2 flex-shrink-0 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50">
                                                Ver
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            {{-- Estado vacío --}}
            <div class="flex flex-col items-center justify-center rounded-lg bg-gray-50 p-12 dark:bg-gray-800">
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                    <x-heroicon-o-folder-open class="h-10 w-10 text-gray-400 dark:text-gray-500" />
                </div>
                <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-white">
                    No hay documentos cargados
                </h3>
                <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
                    Aún no se han cargado documentos o evidencias.
                </p>
            </div>
        @endif
    </x-filament::section>








</x-filament-widgets::widget>
