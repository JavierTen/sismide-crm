<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Información del Emprendedor -->
        <x-filament::section>
            <x-slot name="heading">
                Información del Emprendedor
            </x-slot>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Emprendedor</p>
                    <p class="text-base">{{ $record->entrepreneur->full_name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Emprendimiento</p>
                    <p class="text-base">{{ $record->entrepreneur->business?->business_name ?? 'Sin emprendimiento' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</p>
                    <p class="text-base">{{ $record->entrepreneur->city?->name ?? 'Sin ubicación' }}</p>
                </div>
            </div>
        </x-filament::section>

        <!-- Calificaciones -->
        <x-filament::section>
            <x-slot name="heading">
                Mi Evaluación
            </x-slot>

            <div class="space-y-4">
                @foreach($evaluations as $evaluation)
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $evaluation->question_number }}. {{ $evaluation->question->question_text }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $evaluation->question->description }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Ponderación: {{ $evaluation->question->weight * 100 }}%
                                </p>
                            </div>
                            <div class="ml-4">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-bold bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                    {{ number_format($evaluation->score, 1) }} / 10
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Promedio -->
                <div class="mt-6 p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-success-800 dark:text-success-200">
                            Promedio Ponderado:
                        </span>
                        <span class="text-2xl font-bold text-success-600 dark:text-success-400">
                            {{ number_format($average, 2) }} / 10
                        </span>
                    </div>
                </div>

                <!-- Comentarios -->
                @if($evaluations->first()?->comments)
                    <div class="mt-6">
                        <p class="font-semibold text-gray-900 dark:text-white mb-2">Recomendaciones:</p>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-gray-700 dark:text-gray-300">{{ $evaluations->first()->comments }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        <!-- Botón volver -->
        <div class="flex justify-end">
            <x-filament::button
                tag="a"
                :href="$this->getResource()::getUrl('index')"
                color="gray"
            >
                Volver a la lista
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
