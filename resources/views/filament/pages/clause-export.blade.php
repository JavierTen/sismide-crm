@php
    use App\Models\ExportTemplate;
    use App\Support\ClauseExport\Clauses;
    use App\Support\ClauseExport\FieldCatalog;

    $builder   = $this->builder();
    $hasCities = $builder->cityIds() !== [];
    $total     = $hasCities ? $builder->count() : 0;
    $preview   = ($hasCities && $this->columns !== []) ? $builder->rows(\App\Filament\Pages\ClauseExport::PREVIEW_ROWS) : [];
    $headings  = $builder->headings();
    $template  = $this->currentTemplate();

    // Colores del tema del panel: funcionan igual en modo claro y oscuro.
    $primary   = 'rgb(var(--primary-600))';
    $primarySoft = 'rgba(var(--primary-500), 0.10)';
    $border    = 'rgba(var(--gray-400), 0.35)';
    $soft      = 'rgba(var(--gray-400), 0.08)';
    $muted     = 'opacity:.7;';

@endphp

<x-filament-panels::page>
    {{-- Plantilla --}}
    <div style="display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:.75rem;">
        <label for="clause-template" style="font-size:.875rem;{{ $muted }}">Plantilla</label>

        <div style="min-width:18rem;">
            <x-filament::input.wrapper>
                <x-filament::input.select id="clause-template" wire:model.live="templateId">
                    <option value="">Ninguna</option>
                    @foreach ($this->templateOptions() as $groupLabel => $templates)
                        @if (count($templates))
                            <optgroup label="{{ $groupLabel }}">
                                @foreach ($templates as $option)
                                    <option value="{{ $option->id }}">
                                        {{ $option->name }}
                                        @if ($option->isOwnedBy(auth()->user()))
                                            ({{ ExportTemplate::VISIBILITY_LABELS[$option->visibility] }})
                                        @else
                                            (de {{ $option->user?->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        {{-- Solo con una plantilla propia seleccionada. --}}
        @foreach ([$this->updateTemplateAction, $this->deleteTemplateAction] as $templateAction)
            @if ($templateAction->isVisible())
                {{ $templateAction }}
            @endif
        @endforeach
    </div>

    @if ($template && ! $template->isOwnedBy(auth()->user()))
        <p style="font-size:.8125rem;{{ $muted }}text-align:right;margin-top:-.75rem;">
            Plantilla pública de {{ $template->user?->name }}. Puede usarla y guardar una copia propia, pero solo su creador puede modificarla.
        </p>
    @endif

    {{-- 1. Cláusula --}}
    <x-filament::section icon="heroicon-o-map-pin" icon-color="primary">
        <x-slot name="heading">Escoja la cláusula</x-slot>
        <x-slot name="description">Se traen todos los emprendedores de los municipios marcados.</x-slot>
        <x-slot name="headerEnd">
            <div style="display:flex;align-items:center;gap:.5rem;">
                <label for="clause-year" style="font-size:.875rem;{{ $muted }}">Año</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="clause-year" wire:model.live="year">
                        @foreach ($this->yearOptions() as $yearOption)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.75rem;">
            @foreach (Clauses::options() as $key => $label)
                @php $active = $this->clause === $key; @endphp
                <button
                    type="button"
                    wire:click="selectClause('{{ $key }}')"
                    aria-pressed="{{ $active ? 'true' : 'false' }}"
                    style="text-align:left;display:flex;flex-direction:column;gap:.35rem;padding:1rem 1.1rem;border-radius:.75rem;cursor:pointer;border:2px solid {{ $active ? $primary : $border }};background:{{ $active ? $primarySoft : 'transparent' }};"
                >
                    <strong style="font-size:1rem;">{{ $label }}</strong>
                    <span style="font-size:.8125rem;{{ $muted }}">{{ implode(', ', Clauses::cities($key)) }}</span>
                </button>
            @endforeach
        </div>

        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.6rem;margin-top:1rem;">
            <span style="font-size:.875rem;font-weight:600;{{ $muted }}">Municipios:</span>
            @foreach (Clauses::cities($this->clause) as $cityId => $cityName)
                {{-- El estado va en la clave para que Livewire redibuje la casilla y no quede desmarcada. --}}
                <label wire:key="city-{{ $this->clause }}-{{ $cityId }}-{{ in_array($cityId, $this->excludedCities, true) ? 'off' : 'on' }}" style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;border:1px solid {{ $border }};border-radius:9999px;padding:.45rem .9rem;background:{{ $soft }};cursor:pointer;">
                    <x-filament::input.checkbox
                        wire:click="toggleCity({{ $cityId }})"
                        :checked="! in_array($cityId, $this->excludedCities, true)"
                    />
                    <span>{{ $cityName }}</span>
                </label>
            @endforeach
        </div>

        @unless ($hasCities)
            <p style="font-size:.875rem;color:rgb(var(--danger-600));margin-top:.75rem;">Marque al menos un municipio.</p>
        @endunless

        <div style="margin-top:1.25rem;">
            {{ $this->form }}
        </div>
    </x-filament::section>

    {{-- 2. Datos --}}
    <x-filament::section icon="heroicon-o-squares-plus" icon-color="primary">
        <x-slot name="heading">Escoja los datos del Excel</x-slot>
        <x-slot name="description">Marque los campos de cualquier módulo. Cada campo marcado es una columna.</x-slot>

        {{-- Tres columnas de grupos; menos en pantallas angostas. --}}
        <style>
            .clause-export-groups { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; align-items: stretch; }
            @media (max-width: 1100px) { .clause-export-groups { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 640px) { .clause-export-groups { grid-template-columns: minmax(0, 1fr); } }
            .clause-export-groups [x-cloak] { display: none !important; }
        </style>

        <div style="display:flex;flex-direction:column;gap:.9rem;">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar campo (ej.: puntaje, plan, género)"
                    aria-label="Buscar campo"
                />
            </x-filament::input.wrapper>

            @php
                $groups        = $this->visibleGroups();
                $visibleFields = 8; // campos visibles por grupo antes de "Ver más"
            @endphp

            @if ($groups === [])
                <p style="font-size:.875rem;{{ $muted }}padding:.75rem;">No hay campos que coincidan con la búsqueda.</p>
            @else
                <div class="clause-export-groups">
                    @foreach ($groups as $group => $fields)
                        @php
                            $groupKeys = array_column(FieldCatalog::groups()[$group], 'key');
                            $marked    = count(array_intersect($groupKeys, $this->columns));
                            // Al buscar se muestran todas las coincidencias.
                            $hidden    = $this->search === '' ? max(0, count($fields) - $visibleFields) : 0;
                        @endphp
                        <div wire:key="group-{{ md5($group) }}" x-data="{ expanded: false }" style="border:1px solid {{ $border }};border-radius:.75rem;padding:.85rem;display:flex;flex-direction:column;gap:.4rem;background:{{ $soft }};">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;">
                                <span style="font-size:.875rem;font-weight:700;">
                                    {{ $group }}
                                    <span style="font-weight:500;{{ $muted }}">{{ $marked }}/{{ count($groupKeys) }}</span>
                                </span>
                                <button type="button" wire:click="toggleGroup(@js($group))" style="font-size:.75rem;font-weight:600;color:{{ $primary }};background:none;border:none;cursor:pointer;padding:.35rem .2rem;">
                                    {{ $marked === count($groupKeys) ? 'Quitar todo' : 'Seleccionar todo' }}
                                </button>
                            </div>

                            @foreach ($fields as $field)
                                <label wire:key="field-{{ $field['key'] }}-{{ in_array($field['key'], $this->columns, true) ? 'on' : 'off' }}" @if ($hidden && $loop->index >= $visibleFields) x-show="expanded" x-cloak @endif style="display:flex;align-items:center;gap:.6rem;font-size:.875rem;cursor:pointer;padding:.15rem 0;">
                                    <x-filament::input.checkbox
                                        wire:click="toggleColumn('{{ $field['key'] }}')"
                                        :checked="in_array($field['key'], $this->columns, true)"
                                    />
                                    <span>{{ $field['label'] }}</span>
                                </label>
                            @endforeach

                            @if ($hidden)
                                <button type="button" x-on:click="expanded = ! expanded" style="margin-top:auto;align-self:flex-start;font-size:.75rem;font-weight:600;color:{{ $primary }};background:none;border:none;cursor:pointer;padding:.35rem 0 0;">
                                    <span x-show="! expanded">Ver más ({{ $hidden }})</span>
                                    <span x-show="expanded" x-cloak>Ver menos</span>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- 3. Orden de las columnas --}}
    <x-filament::section icon="heroicon-o-arrows-up-down" icon-color="primary">
        <x-slot name="heading">Columnas del Excel</x-slot>
        <x-slot name="description">En este orden salen en el Excel · {{ count($this->columns) + 1 }} columnas</x-slot>

        <div style="display:flex;flex-direction:column;gap:.6rem;">
            <div style="display:flex;align-items:center;gap:.4rem;font-size:.875rem;padding:.5rem .6rem;border:1px dashed {{ $border }};border-radius:.5rem;{{ $muted }}">
                <span style="width:1.4rem;text-align:right;">0</span>
                <span>Cláusula (siempre incluida)</span>
            </div>

            @forelse ($this->columns as $index => $key)
                @php $field = FieldCatalog::get($key); @endphp
                <div wire:key="col-{{ $key }}" style="display:flex;align-items:center;gap:.25rem;font-size:.875rem;padding:.2rem .2rem .2rem .6rem;border:1px solid {{ $border }};border-radius:.5rem;">
                    <span style="width:1.4rem;text-align:right;{{ $muted }}">{{ $index + 1 }}</span>
                    <span style="flex:1;padding-left:.35rem;min-width:0;">
                        {{ $field['label'] }}
                        <span style="font-size:.75rem;{{ $muted }}">({{ $field['group'] }})</span>
                    </span>
                    <x-filament::icon-button icon="heroicon-m-chevron-up" color="gray" size="sm" :disabled="$index === 0" wire:click="moveColumn({{ $index }}, -1)" :label="'Subir '.$field['label']" />
                    <x-filament::icon-button icon="heroicon-m-chevron-down" color="gray" size="sm" :disabled="$index === count($this->columns) - 1" wire:click="moveColumn({{ $index }}, 1)" :label="'Bajar '.$field['label']" />
                    <x-filament::icon-button icon="heroicon-m-x-mark" color="danger" size="sm" wire:click="toggleColumn('{{ $key }}')" :label="'Quitar '.$field['label']" />
                </div>
            @empty
                <p style="font-size:.875rem;{{ $muted }}">Marque al menos un campo.</p>
            @endforelse
        </div>
    </x-filament::section>

    {{-- 4. Vista previa y descarga --}}
    <x-filament::section icon="heroicon-o-document-arrow-down" icon-color="primary">
        <x-slot name="heading">Vista previa y descarga</x-slot>
        <x-slot name="description">
            <strong>{{ number_format($total) }}</strong> emprendedores encontrados en {{ $builder->year }}
            @if ($total > count($preview) && count($preview))
                · se muestran los primeros {{ count($preview) }}
            @endif
        </x-slot>
        <x-slot name="headerEnd">
            <div style="display:flex;flex-wrap:wrap;gap:.6rem;">
                {{ $this->saveTemplateAction }}
                {{ $this->exportAction }}
            </div>
        </x-slot>

        @if ($preview === [])
            <p style="font-size:.875rem;{{ $muted }}">
                {{ $this->columns === [] ? 'Marque al menos un campo para ver la vista previa.' : 'No hay emprendedores con los criterios escogidos.' }}
            </p>
        @else
            <div style="overflow-x:auto;border:1px solid {{ $border }};border-radius:.6rem;">
                <table style="border-collapse:collapse;width:100%;min-width:max-content;">
                    <thead>
                        <tr>
                            @foreach ($headings as $heading)
                                <th style="font-size:.75rem;font-weight:700;text-align:left;padding:.7rem .85rem;border-bottom:1px solid {{ $border }};background:{{ $soft }};white-space:nowrap;">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview as $row)
                            <tr>
                                @foreach ($row as $i => $value)
                                    <td style="font-size:.8125rem;padding:.65rem .85rem;border-bottom:1px solid {{ $border }};white-space:nowrap;{{ $i === 0 ? 'color:'.$primary.';font-weight:600;' : '' }}{{ $value === FieldCatalog::NO_RECORD ? $muted.'font-style:italic;' : '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
