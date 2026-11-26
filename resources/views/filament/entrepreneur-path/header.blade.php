{{-- Ficha de la Ruta del emprendedor: quién es, dónde está y en qué ruta. Incluye los estilos de toda la ficha. --}}
@php
    /** @var array $p  App\Support\EntrepreneurPath::for() */
    $p = $getState();
    $routeColors = [1 => 'warning', 2 => 'info', 3 => 'success'];
    $level = ($p['exit'] ?? $p['entry'] ?? null)['level'] ?? null;
@endphp

<style>
    .ep { --ep-line: rgba(var(--gray-950), .08); --ep-soft: rgba(var(--gray-400), .07); display: flex; flex-direction: column; gap: 1rem; font-size: .875rem; }
    .dark .ep, .dark .ep-tab { --ep-line: rgba(255, 255, 255, .1); }
    .ep-tab { --ep-line: rgba(var(--gray-950), .08); --ep-soft: rgba(var(--gray-400), .07); font-size: .875rem; }
    .ep-muted { color: rgb(var(--gray-500)); }
    .dark .ep-muted { color: rgb(var(--gray-400)); }

    .ep-header { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 1rem; }
    .ep-avatar { flex: none; width: 3.5rem; height: 3.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.15rem;
        background: rgba(var(--primary-500), .14); color: rgb(var(--primary-600)); }
    .dark .ep-avatar { color: rgb(var(--primary-400)); }
    .ep-who { flex: 1 1 18rem; min-width: 0; }
    .ep-name { font-size: 1.25rem; font-weight: 700; line-height: 1.6rem; color: rgb(var(--gray-950)); overflow-wrap: anywhere; }
    .dark .ep-name { color: #fff; }
    .ep-business { margin-top: .15rem; font-weight: 600; color: rgb(var(--gray-700)); }
    .dark .ep-business { color: rgb(var(--gray-200)); }
    .ep-meta { margin-top: .35rem; display: flex; flex-wrap: wrap; gap: .2rem .9rem; font-size: .8rem; }
    .ep-side { display: flex; flex-direction: column; align-items: flex-end; gap: .4rem; text-align: right; }
    .ep-level { font-size: .75rem; }
    .ep-contact { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .25rem .9rem; }

    .ep-journey { border: 1px solid var(--ep-line); border-radius: .75rem; padding: .9rem 1rem; }
    .ep-journey-head { display: flex; align-items: center; gap: .75rem; margin-bottom: .8rem; }
    .ep-journey-title { font-weight: 600; color: rgb(var(--gray-950)); }
    .dark .ep-journey-title { color: #fff; }
    .ep-meter { flex: 1; height: .375rem; border-radius: 9999px; background: var(--ep-line); overflow: hidden; }
    .ep-meter > div { height: 100%; border-radius: 9999px; background: rgb(var(--success-500)); }
    .ep-steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(8.75rem, 1fr)); gap: .6rem; }
    .ep-step { display: flex; gap: .55rem; align-items: flex-start; padding: .55rem .6rem; border-radius: .6rem; background: var(--ep-soft); }
    .ep-step-icon { flex: none; width: 1.75rem; height: 1.75rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
        border: 1.5px dashed rgb(var(--gray-300)); color: rgb(var(--gray-400)); }
    .dark .ep-step-icon { border-color: rgb(var(--gray-600)); }
    .ep-step-icon svg { width: 1rem; height: 1rem; }
    .ep-step.is-done .ep-step-icon { border: 0; background: rgba(var(--success-500), .15); color: rgb(var(--success-600)); }
    .dark .ep-step.is-done .ep-step-icon { color: rgb(var(--success-400)); }
    .ep-step-title { font-size: .75rem; font-weight: 600; line-height: 1rem; color: rgb(var(--gray-700)); }
    .dark .ep-step-title { color: rgb(var(--gray-200)); }
    .ep-step-detail { font-size: .75rem; line-height: 1rem; margin-top: .1rem; }

    .ep-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: 1rem 1.5rem; }
    .ep-label { font-size: .75rem; font-weight: 500; color: rgb(var(--gray-500)); }
    .dark .ep-label { color: rgb(var(--gray-400)); }
    .ep-value { margin-top: .15rem; color: rgb(var(--gray-950)); overflow-wrap: anywhere; }
    .dark .ep-value { color: #fff; }
    .ep-empty { color: rgb(var(--gray-400)); }
    .ep-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .35rem; }
    .ep-block { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--ep-line); }

    .ep-table-wrap { overflow-x: auto; border: 1px solid var(--ep-line); border-radius: .75rem; }
    .ep-table { width: 100%; border-collapse: collapse; font-size: .825rem; }
    .ep-table th { text-align: left; font-size: .75rem; font-weight: 600; padding: .6rem .75rem; color: rgb(var(--gray-500)); background: var(--ep-soft); white-space: nowrap; }
    .ep-table td { padding: .6rem .75rem; border-top: 1px solid var(--ep-line); vertical-align: top; color: rgb(var(--gray-950)); }
    .dark .ep-table td { color: rgb(var(--gray-100)); }
    .ep-table td.ep-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .ep-dx { display: grid; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); gap: 1rem; }
    .ep-dx-card { border: 1px solid var(--ep-line); border-radius: .75rem; padding: 1rem; display: flex; flex-direction: column; gap: .6rem; }
    .ep-dx-score { font-size: 2rem; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; color: rgb(var(--gray-950)); }
    .dark .ep-dx-score { color: #fff; }
    .ep-level-pill { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 600; padding: .15rem .55rem; border-radius: 9999px; }

    .ep-docs { display: flex; flex-direction: column; gap: 1rem; }
    .ep-doc-list { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .4rem; }
    .ep-doc { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .7rem; border-radius: .5rem; border: 1px solid var(--ep-line); font-size: .8rem; font-weight: 500;
        color: rgb(var(--primary-600)); text-decoration: none; background: var(--ep-soft); }
    .ep-doc:hover { background: rgba(var(--primary-500), .1); }
    .ep-doc:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 2px; }
    .dark .ep-doc { color: rgb(var(--primary-400)); }
    .ep-doc svg { width: 1rem; height: 1rem; }
</style>

<div class="ep">
    <div class="ep-header">
        <div class="ep-avatar" aria-hidden="true">{{ $p['initials'] ?: '—' }}</div>

        <div class="ep-who">
            <div class="ep-name">{{ $p['name'] }}</div>
            <div class="ep-business">
                {{ $p['business'] ?? 'Sin emprendimiento' }}<span class="ep-muted" style="font-weight:400;">{{ $p['city'] ? ' · ' . $p['city'] : '' }}</span>
            </div>
            <div class="ep-meta ep-muted">
                @if ($p['document']) <span>{{ $p['document'] }}</span> @endif
                <span>Gestor: {{ $p['manager'] ?? 'sin asignar' }}</span>
                @if ($p['registered']) <span>Registrado el {{ $p['registered'] }}{{ $p['project'] ? ' · ' . $p['project'] : '' }}</span> @endif
            </div>
        </div>

        <div class="ep-side">
            @if ($p['route'])
                <x-filament::badge :color="$routeColors[$p['route']] ?? 'gray'" size="lg">Ruta {{ $p['route'] }}</x-filament::badge>
            @else
                <x-filament::badge color="gray" size="lg">Sin ruta</x-filament::badge>
            @endif
            @if ($level)
                <span class="ep-level ep-muted">{{ $level['label'] }}</span>
            @endif
            <div class="ep-contact">
                @if ($p['phone'])
                    <x-filament::link :href="'tel:' . preg_replace('/\s+/', '', $p['phone'])" icon="heroicon-m-phone" size="sm">{{ $p['phone'] }}</x-filament::link>
                @endif
                @if ($p['email'])
                    <x-filament::link :href="'mailto:' . $p['email']" icon="heroicon-m-envelope" size="sm">{{ $p['email'] }}</x-filament::link>
                @endif
            </div>
        </div>
    </div>

    @include('filament.entrepreneur-path.journey', ['steps' => $p['steps']])
</div>
