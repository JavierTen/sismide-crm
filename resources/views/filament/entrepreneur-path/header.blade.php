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
    .ep-contact { margin-top: .4rem; display: flex; flex-wrap: wrap; gap: .25rem 1.1rem; font-size: .8rem; }
    .ep-contact a { display: inline-flex; align-items: center; gap: .35rem; color: rgb(var(--gray-600)); text-decoration: none; overflow-wrap: anywhere; }
    .dark .ep-contact a { color: rgb(var(--gray-300)); }
    .ep-contact a:hover { color: rgb(var(--primary-600)); text-decoration: underline; }
    .ep-contact a:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 2px; border-radius: .25rem; }
    .ep-contact svg { flex: none; width: .95rem; height: .95rem; color: rgb(var(--gray-400)); }

    /* Ruta: recuadro con el color de la ruta (--rc) y el nivel de madurez debajo. */
    .ep-route { flex: 0 1 15rem; min-width: 12rem; padding: .7rem .9rem; border-radius: .75rem; background: rgba(var(--rc), .1); border: 1px solid rgba(var(--rc), .28); }
    .ep-route-kicker { font-size: .68rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: rgb(var(--gray-500)); }
    .ep-route-value { margin-top: .1rem; font-size: 1.35rem; font-weight: 700; line-height: 1.6rem; color: rgb(var(--rc-text)); }
    .dark .ep-route-value { color: rgb(var(--rc-text-dark)); }
    .ep-route-level { margin-top: .3rem; display: flex; align-items: flex-start; gap: .4rem; font-size: .75rem; line-height: 1rem; color: rgb(var(--gray-600)); }
    .dark .ep-route-level { color: rgb(var(--gray-300)); }
    .ep-route-level i { flex: none; width: .5rem; height: .5rem; margin-top: .25rem; border-radius: 9999px; }

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
            @if ($p['phone'] || $p['email'])
                <div class="ep-contact">
                    @if ($p['phone'])
                        <a href="tel:{{ preg_replace('/\s+/', '', $p['phone']) }}" title="Llamar"><x-filament::icon icon="heroicon-m-phone" />{{ $p['phone'] }}</a>
                    @endif
                    @if ($p['email'])
                        <a href="mailto:{{ $p['email'] }}" title="Escribir un correo"><x-filament::icon icon="heroicon-m-envelope" />{{ $p['email'] }}</a>
                    @endif
                </div>
            @endif
        </div>

        @php $rc = $routeColors[$p['route']] ?? 'gray'; @endphp
        <div class="ep-route" style="--rc: var(--{{ $rc }}-500); --rc-text: var(--{{ $rc }}-700); --rc-text-dark: var(--{{ $rc }}-400);">
            <div class="ep-route-kicker">Ruta de atención</div>
            <div class="ep-route-value">{{ $p['route'] ? 'Ruta ' . $p['route'] : 'Sin ruta' }}</div>
            <div class="ep-route-level">
                @if ($level)
                    <i aria-hidden="true" style="background: {{ $level['color'] }};"></i><span>{{ $level['label'] }}</span>
                @else
                    <span>Se asigna con el diagnóstico de entrada.</span>
                @endif
            </div>
        </div>
    </div>

    @include('filament.entrepreneur-path.journey', ['steps' => $p['steps']])
</div>
