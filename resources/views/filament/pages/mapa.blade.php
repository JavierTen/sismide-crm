<x-filament-panels::page>
    @php
        /** @var array $data  App\Filament\Pages\Mapa::mapData() */
        $data = $this->mapData();
        $byRoute = collect($data['entrepreneurs'])->countBy(fn ($e) => $e['route'] ?? 'none');
        $actorCount = count($data['actors']);
    @endphp

    <style>
        .mp { --mp-line: rgba(var(--gray-950), .1); --mp-bg: #fff; --mp-text: rgb(var(--gray-950)); --mp-muted: rgb(var(--gray-500)); --mp-hover: rgba(var(--gray-400), .12);
            display: flex; flex-direction: column; gap: .75rem; font-size: .875rem; }
        .dark .mp { --mp-line: rgba(255, 255, 255, .1); --mp-bg: rgb(var(--gray-900)); --mp-text: #fff; --mp-muted: rgb(var(--gray-400)); }

        /* Barra: búsqueda y filtros */
        .mp-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; }
        .mp-search { flex: 1 1 18rem; max-width: 30rem; display: flex; align-items: center; gap: .5rem; height: 2.5rem; padding: 0 .75rem; border-radius: .6rem;
            border: 1px solid var(--mp-line); background: var(--mp-bg); box-shadow: 0 1px 2px rgb(0 0 0 / .05); }
        .mp-search:focus-within { border-color: rgb(var(--primary-500)); box-shadow: 0 0 0 1px rgb(var(--primary-500)); }
        .mp-search svg { width: 1.1rem; height: 1.1rem; color: var(--mp-muted); flex: none; }
        .mp-search input { flex: 1; min-width: 0; height: 100%; border: 0; padding: 0; background: transparent; color: var(--mp-text); font-size: .875rem; box-shadow: none; outline: none; }
        .mp-search input::placeholder { color: var(--mp-muted); }
        .mp-filters { display: flex; flex-wrap: wrap; gap: .5rem; }
        .mp-select, .mp-btn { height: 2.5rem; border-radius: .6rem; border: 1px solid var(--mp-line); background-color: var(--mp-bg); color: var(--mp-text); font-size: .875rem; font-weight: 500;
            box-shadow: 0 1px 2px rgb(0 0 0 / .05); }
        .mp-select { padding: 0 2.25rem 0 .8rem; max-width: 15rem; }
        .mp-select:focus, .mp-btn:focus-visible { outline: none; border-color: rgb(var(--primary-500)); box-shadow: 0 0 0 1px rgb(var(--primary-500)); }
        .mp-btn { padding: 0 .9rem; cursor: pointer; }
        .mp-btn[aria-pressed="true"] { background: rgba(var(--primary-500), .12); border-color: rgb(var(--primary-500)); color: rgb(var(--primary-600)); }
        .dark .mp-btn[aria-pressed="true"] { color: rgb(var(--primary-400)); }
        .mp-btn:disabled { opacity: .5; cursor: not-allowed; }

        /* Mapa y panel flotante */
        .mp-stage { position: relative; border-radius: .9rem; overflow: hidden; border: 1px solid var(--mp-line); background: var(--mp-bg); }
        .mp-map { width: 100%; height: calc(100vh - 14rem); min-height: 30rem; }
        .mp-panel { position: absolute; z-index: 2; top: 1rem; left: 1rem; width: 17.5rem; max-height: calc(100% - 2rem); overflow: auto; padding: 1rem 1.1rem;
            border-radius: .9rem; background: var(--mp-bg); border: 1px solid var(--mp-line); box-shadow: 0 12px 28px -8px rgb(0 0 0 / .25); color: var(--mp-text); }
        .mp-kicker { font-size: .7rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--mp-muted); }
        .mp-total { display: flex; align-items: baseline; gap: .4rem; margin-top: .15rem; }
        .mp-total strong { font-size: 2rem; line-height: 2.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .mp-total span { color: var(--mp-muted); }
        .mp-sep { border: 0; border-top: 1px solid var(--mp-line); margin: .9rem 0; }
        .mp-routes { list-style: none; margin: .4rem 0 0; padding: 0; display: flex; flex-direction: column; gap: .1rem; }
        .mp-route { width: 100%; display: flex; align-items: center; gap: .6rem; padding: .4rem .4rem; border: 0; border-radius: .5rem; background: none; color: inherit;
            text-align: left; cursor: pointer; font-size: .85rem; }
        .mp-route:hover { background: var(--mp-hover); }
        .mp-route:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 1px; }
        .mp-route small { display: block; font-size: .72rem; color: var(--mp-muted); }
        .mp-dot { flex: none; width: .7rem; height: .7rem; border-radius: 9999px; background: var(--c); }
        .mp-route[aria-pressed="false"] { opacity: .5; }
        .mp-route[aria-pressed="false"] .mp-dot { background: transparent; box-shadow: inset 0 0 0 2px var(--c); }
        .mp-num { margin-left: auto; font-weight: 700; font-variant-numeric: tabular-nums; }
        .mp-entities { display: flex; justify-content: space-between; gap: .75rem; font-size: .85rem; }
        .mp-entities span { color: var(--mp-muted); text-align: right; }
        .mp-note { margin: .8rem 0 0; font-size: .78rem; line-height: 1.15rem; color: var(--mp-muted); }
        .mp-empty { position: absolute; z-index: 2; top: 1rem; left: 50%; transform: translateX(-50%); padding: .5rem .95rem; border-radius: 9999px;
            background: var(--mp-bg); border: 1px solid var(--mp-line); box-shadow: 0 6px 16px -4px rgb(0 0 0 / .2); color: var(--mp-text); font-size: .85rem; }
        .mp-message { padding: 2rem; text-align: center; color: var(--mp-muted); }

        /* Ventanas del mapa */
        .mp .mapboxgl-popup-content { padding: 0; border-radius: .9rem; background: var(--mp-bg); border: 1px solid var(--mp-line); box-shadow: 0 14px 32px -8px rgb(0 0 0 / .35); }
        .mp .mapboxgl-popup-tip { display: none; }
        .mp .mapboxgl-popup-close-button { top: .55rem; right: .55rem; width: 1.75rem; height: 1.75rem; border-radius: .4rem; font-size: 1.2rem; color: var(--mp-muted); }
        .mp .mapboxgl-popup-close-button:hover { background: var(--mp-hover); }
        .mp-pop { width: 19.5rem; max-width: calc(100vw - 4rem); padding: 1rem 1.1rem 1.05rem; color: var(--mp-text); font-family: inherit; font-size: .85rem; }
        .mp-pop-title { padding-right: 1.75rem; font-size: 1rem; font-weight: 700; line-height: 1.35rem; overflow-wrap: anywhere; }
        .mp-pop-sub { margin-top: .15rem; color: var(--mp-muted); overflow-wrap: anywhere; }
        .mp-pill { display: inline-flex; align-items: center; gap: .4rem; margin-top: .65rem; padding: .2rem .6rem; border-radius: 9999px; font-size: .76rem; font-weight: 600; }
        .mp-pill i { width: .5rem; height: .5rem; border-radius: 9999px; }
        .mp-rows { margin: .8rem 0 0; display: grid; grid-template-columns: auto 1fr; gap: .35rem .9rem; }
        .mp-rows dt { color: var(--mp-muted); }
        .mp-rows dd { margin: 0; overflow-wrap: anywhere; }
        .mp-rows a, .mp-link { color: rgb(var(--primary-600)); text-decoration: none; }
        .dark .mp-rows a, .dark .mp-link { color: rgb(var(--primary-400)); }
        .mp-rows a:hover, .mp-link:hover { text-decoration: underline; }
        .mp-link { display: inline-block; margin-top: .9rem; font-weight: 600; }
        .mp-back { margin: 0 0 .55rem; padding: 0; border: 0; background: none; color: var(--mp-muted); font-size: .8rem; cursor: pointer; }
        .mp-back:hover { color: var(--mp-text); }
        .mp-list { list-style: none; margin: .8rem 0 0; padding: .4rem 0 0; border-top: 1px solid var(--mp-line); }
        .mp-list button { width: 100%; display: flex; align-items: center; gap: .65rem; padding: .55rem .35rem; border: 0; border-radius: .5rem; background: none;
            color: inherit; text-align: left; cursor: pointer; }
        .mp-list button:hover { background: var(--mp-hover); }
        .mp-list b { display: block; font-weight: 600; overflow-wrap: anywhere; }
        .mp-list small { display: block; color: var(--mp-muted); font-size: .76rem; }
        .mp-list .mp-chevron { margin-left: auto; color: var(--mp-muted); font-size: 1.1rem; }

        .dark .mp .mapboxgl-ctrl-group { background: rgb(var(--gray-800)); }
        .dark .mp .mapboxgl-ctrl-group button + button { border-top-color: rgba(255, 255, 255, .1); }
        .dark .mp .mapboxgl-ctrl-icon { filter: invert(1); }

        /* Angosto: el panel pasa debajo del mapa */
        @media (max-width: 47.99rem) {
            .mp-map { height: 60vh; min-height: 22rem; }
            .mp-panel { position: static; width: auto; max-height: none; border: 0; border-top: 1px solid var(--mp-line); border-radius: 0; box-shadow: none; }
            .mp-search { max-width: none; }
        }
    </style>

    <div class="mp">
        <div class="mp-toolbar">
            <label class="mp-search">
                <x-filament::icon icon="heroicon-m-magnifying-glass" />
                <input id="mp-search" type="search" placeholder="Buscar emprendedor o emprendimiento" aria-label="Buscar emprendedor o emprendimiento" autocomplete="off">
            </label>

            <div class="mp-filters">
                <select id="mp-city" class="mp-select" aria-label="Municipio">
                    <option value="">Todos los municipios</option>
                    @foreach ($data['cities'] as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>

                @if (count($data['managers']) > 1)
                    <select id="mp-manager" class="mp-select" aria-label="Gestor">
                        <option value="">Todos los gestores</option>
                        @foreach ($data['managers'] as $manager)
                            <option value="{{ $manager }}">{{ $manager }}</option>
                        @endforeach
                    </select>
                @endif

                <button id="mp-actors" type="button" class="mp-btn" aria-pressed="false"
                    @disabled($actorCount === 0)
                    title="{{ $actorCount === 0 ? 'Ninguna entidad tiene coordenadas todavía' : 'Mostrar u ocultar las entidades' }}">
                    Entidades
                </button>
            </div>
        </div>

        <div class="mp-stage">
            @if (blank($data['token']))
                <p class="mp-message">El mapa no está configurado: falta el token de Mapbox (MAPBOX_ACCESS_TOKEN).</p>
            @else
                <div id="mp-map" class="mp-map" role="region" aria-label="Mapa de emprendedores"></div>
                <div id="mp-empty" class="mp-empty" hidden>No hay emprendedores con esos filtros.</div>
            @endif

            <aside class="mp-panel" aria-label="Resumen y leyenda">
                <div class="mp-kicker">Emprendedores</div>
                <div class="mp-total"><strong id="mp-count">{{ number_format(count($data['entrepreneurs']), 0, ',', '.') }}</strong><span>graficados</span></div>

                <hr class="mp-sep">

                <div class="mp-kicker">Rutas</div>
                <ul class="mp-routes">
                    @foreach ($data['routes'] as $route)
                        <li>
                            <button type="button" class="mp-route" data-route="{{ $route['number'] }}" aria-pressed="true" style="--c: {{ $route['hex'] }};"
                                title="Mostrar u ocultar la Ruta {{ $route['number'] }}">
                                <span class="mp-dot" aria-hidden="true"></span>
                                <span><strong>Ruta {{ $route['number'] }}</strong> · {{ $route['name'] }}<small>{{ $route['levels'] }}</small></span>
                                <span class="mp-num" data-count-route="{{ $route['number'] }}">{{ $byRoute[$route['number']] ?? 0 }}</span>
                            </button>
                        </li>
                    @endforeach
                    @if (($byRoute['none'] ?? 0) > 0)
                        <li>
                            <button type="button" class="mp-route" data-route="none" aria-pressed="true" style="--c: {{ $data['noRouteHex'] }};" title="Mostrar u ocultar los que no tienen ruta">
                                <span class="mp-dot" aria-hidden="true"></span>
                                <span><strong>Sin ruta</strong><small>Sin diagnóstico de entrada</small></span>
                                <span class="mp-num" data-count-route="none">{{ $byRoute['none'] }}</span>
                            </button>
                        </li>
                    @endif
                </ul>

                <hr class="mp-sep">

                <div class="mp-entities">
                    <strong>Entidades</strong>
                    <span>{{ $actorCount === 0 ? 'Ninguna tiene coordenadas todavía' : $actorCount . ' con coordenadas' }}</span>
                </div>

                <p class="mp-note">Los círculos con número agrupan emprendedores cercanos: haz clic o acércate para separarlos.</p>
            </aside>
        </div>
    </div>

    @if (filled($data['token']))
        @push('scripts')
            <script src="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js"></script>
            <link href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" rel="stylesheet">

            <script>
                (() => {
                    const data = @js($data);
                    const container = document.getElementById('mp-map');
                    if (! container || ! window.mapboxgl) return;

                    mapboxgl.accessToken = data.token;

                    const isDark = () => document.documentElement.classList.contains('dark');
                    const styleUrl = () => isDark() ? 'mapbox://styles/mapbox/dark-v11' : 'mapbox://styles/mapbox/light-v11';
                    const routes = Object.fromEntries(data.routes.map((r) => [r.number, r]));
                    const colorOf = (route) => routes[route]?.hex ?? data.noRouteHex;
                    const routeKey = (e) => e.route ? String(e.route) : 'none';
                    const normalize = (text) => (text ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
                    const number = (n) => new Intl.NumberFormat('es-CO').format(n);
                    const fonts = ['DIN Pro Medium', 'Arial Unicode MS Bold'];

                    const state = { query: '', city: '', manager: '', routes: new Set([...data.routes.map((r) => String(r.number)), 'none']), actors: false };
                    let groups = new Map();
                    let popup = null;

                    const map = new mapboxgl.Map({ container, style: styleUrl(), center: [-74.2, 10.4], zoom: 8 });
                    map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

                    // ── Datos visibles ─────────────────────────────────────────
                    const matchesFilters = (e) => (! state.query || normalize(`${e.name} ${e.business ?? ''}`).includes(state.query))
                        && (! state.city || e.city === state.city)
                        && (! state.manager || e.manager === state.manager);
                    const visible = () => data.entrepreneurs.filter((e) => matchesFilters(e) && state.routes.has(routeKey(e)));

                    // Los que comparten coordenada van en un solo punto ("3 emprendedores aquí").
                    function geojson(list) {
                        groups = new Map();
                        list.forEach((e) => {
                            const key = `${e.lat.toFixed(5)},${e.lng.toFixed(5)}`;
                            groups.set(key, [...(groups.get(key) ?? []), e]);
                        });

                        return {
                            type: 'FeatureCollection',
                            features: [...groups].map(([key, items]) => ({
                                type: 'Feature',
                                geometry: { type: 'Point', coordinates: [items[0].lng, items[0].lat] },
                                properties: { key, count: items.length, color: colorOf(items[0].route) },
                            })),
                        };
                    }

                    const actorsGeojson = () => ({
                        type: 'FeatureCollection',
                        features: data.actors.map((a, i) => ({ type: 'Feature', geometry: { type: 'Point', coordinates: [a.lng, a.lat] }, properties: { index: i } })),
                    });

                    // ── Capas (se vuelven a poner al cambiar entre modo claro y oscuro) ──
                    map.on('style.load', () => {
                        const dark = isDark();

                        map.addSource('entrepreneurs', { type: 'geojson', data: geojson(visible()), cluster: true, clusterMaxZoom: 13, clusterRadius: 44,
                            clusterProperties: { total: ['+', ['get', 'count']] } });

                        map.addLayer({ id: 'clusters', type: 'circle', source: 'entrepreneurs', filter: ['has', 'point_count'], paint: {
                            'circle-color': dark ? '#e5e7eb' : '#1f2937',
                            'circle-radius': ['step', ['get', 'total'], 15, 10, 19, 30, 24],
                            'circle-stroke-width': 7,
                            'circle-stroke-color': dark ? 'rgba(229, 231, 235, 0.16)' : 'rgba(31, 41, 55, 0.14)',
                        } });
                        map.addLayer({ id: 'cluster-count', type: 'symbol', source: 'entrepreneurs', filter: ['has', 'point_count'],
                            layout: { 'text-field': ['to-string', ['get', 'total']], 'text-font': fonts, 'text-size': 13, 'text-allow-overlap': true },
                            paint: { 'text-color': dark ? '#111827' : '#ffffff' } });

                        const multiple = ['>', ['get', 'count'], 1];
                        map.addLayer({ id: 'points', type: 'circle', source: 'entrepreneurs', filter: ['!', ['has', 'point_count']], paint: {
                            'circle-color': ['get', 'color'],
                            'circle-radius': ['case', multiple, 10, 7],
                            'circle-stroke-width': ['case', multiple, 3, 2],
                            'circle-stroke-color': dark ? '#0b1220' : '#ffffff',
                        } });
                        map.addLayer({ id: 'points-count', type: 'symbol', source: 'entrepreneurs', filter: ['all', ['!', ['has', 'point_count']], multiple],
                            layout: { 'text-field': ['to-string', ['get', 'count']], 'text-font': fonts, 'text-size': 10, 'text-allow-overlap': true },
                            paint: { 'text-color': '#ffffff' } });

                        if (data.actors.length) {
                            map.addSource('actors', { type: 'geojson', data: actorsGeojson() });
                            map.addLayer({ id: 'actors', type: 'circle', source: 'actors',
                                layout: { visibility: state.actors ? 'visible' : 'none' },
                                paint: { 'circle-color': dark ? '#e5e7eb' : '#374151', 'circle-radius': 6, 'circle-stroke-width': 2, 'circle-stroke-color': dark ? '#0b1220' : '#ffffff' } });
                        }
                    });

                    // ── Ventanas (armadas con nodos: los datos nunca se insertan como HTML) ──
                    function el(tag, attrs = {}, ...children) {
                        const node = document.createElement(tag);
                        Object.entries(attrs).forEach(([key, value]) => {
                            if (key === 'class') node.className = value;
                            else if (key === 'style') node.style.cssText = value;
                            else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
                            else if (value !== null && value !== undefined) node.setAttribute(key, value);
                        });
                        children.flat().forEach((child) => {
                            if (child !== null && child !== undefined && child !== false && child !== '') node.append(child instanceof Node ? child : document.createTextNode(String(child)));
                        });
                        return node;
                    }

                    const routeText = (e) => e.route ? `Ruta ${e.route}${e.level ? ' · ' + e.level : ''}` : 'Sin ruta';
                    const pill = (e) => {
                        const color = colorOf(e.route);
                        return el('span', { class: 'mp-pill', style: `color: ${color}; background: ${color}1f;` }, el('i', { style: `background: ${color};` }), routeText(e));
                    };
                    const rows = (pairs) => el('dl', { class: 'mp-rows' }, pairs.filter(([, value]) => value).flatMap(([label, value]) => [el('dt', {}, label), el('dd', {}, value)]));
                    const tel = (phone) => phone ? el('a', { href: 'tel:' + phone.replace(/\s+/g, '') }, phone) : null;
                    const mail = (email) => email ? el('a', { href: 'mailto:' + email }, email) : null;

                    function entrepreneurCard(e, back) {
                        return el('div', { class: 'mp-pop' },
                            back ? el('button', { type: 'button', class: 'mp-back', onclick: back }, '‹ Volver') : null,
                            el('div', { class: 'mp-pop-title' }, e.name),
                            el('div', { class: 'mp-pop-sub' }, [e.business, e.city].filter(Boolean).join(' · ')),
                            pill(e),
                            rows([['Teléfono', tel(e.phone)], ['Correo', mail(e.email)], ['Gestor', e.manager]]),
                            el('a', { class: 'mp-link', href: e.url }, 'Ver ruta del emprendedor →'),
                        );
                    }

                    function groupCard(items) {
                        const card = el('div', { class: 'mp-pop' },
                            el('div', { class: 'mp-pop-title' }, `${items.length} emprendedores aquí`),
                            el('div', { class: 'mp-pop-sub' }, ['Mismo punto', items[0].city].filter(Boolean).join(' · ')),
                            el('ul', { class: 'mp-list' }, items.map((e) => el('li', {},
                                el('button', { type: 'button', onclick: () => popup.setDOMContent(entrepreneurCard(e, () => popup.setDOMContent(card))) },
                                    el('span', { class: 'mp-dot', style: `--c: ${colorOf(e.route)};` }),
                                    el('span', {}, el('b', {}, e.name), el('small', {}, routeText(e))),
                                    el('span', { class: 'mp-chevron', 'aria-hidden': 'true' }, '›'),
                                ),
                            ))),
                        );
                        return card;
                    }

                    function actorCard(a) {
                        return el('div', { class: 'mp-pop' },
                            el('div', { class: 'mp-pop-title' }, a.name),
                            el('div', { class: 'mp-pop-sub' }, [a.type, a.city].filter(Boolean).join(' · ')),
                            rows([['Contacto', a.contact], ['Teléfono', tel(a.phone)], ['Correo', mail(a.email)], ['Gestor', a.manager]]),
                        );
                    }

                    function open(lngLat, content) {
                        popup?.remove();
                        popup = new mapboxgl.Popup({ offset: 14, maxWidth: 'none', focusAfterOpen: false }).setLngLat(lngLat).setDOMContent(content).addTo(map);
                    }

                    map.on('click', 'clusters', (event) => {
                        const feature = event.features[0];
                        map.getSource('entrepreneurs').getClusterExpansionZoom(feature.properties.cluster_id, (error, zoom) => {
                            if (! error) map.easeTo({ center: feature.geometry.coordinates, zoom });
                        });
                    });
                    map.on('click', 'points', (event) => {
                        const feature = event.features[0];
                        const items = groups.get(feature.properties.key) ?? [];
                        if (items.length) open(feature.geometry.coordinates, items.length === 1 ? entrepreneurCard(items[0]) : groupCard(items));
                    });
                    map.on('click', 'actors', (event) => {
                        const feature = event.features[0];
                        open(feature.geometry.coordinates, actorCard(data.actors[feature.properties.index]));
                    });
                    ['clusters', 'points', 'actors'].forEach((layer) => {
                        map.on('mouseenter', layer, () => map.getCanvas().style.cursor = 'pointer');
                        map.on('mouseleave', layer, () => map.getCanvas().style.cursor = '');
                    });

                    // ── Filtros ────────────────────────────────────────────────
                    const panelWidth = () => window.matchMedia('(max-width: 47.99rem)').matches ? 0 : 320;

                    function fit(list, animate = true) {
                        const points = [...list.map((e) => [e.lng, e.lat]), ...(state.actors ? data.actors.map((a) => [a.lng, a.lat]) : [])];
                        if (! points.length) return;
                        const bounds = points.reduce((b, p) => b.extend(p), new mapboxgl.LngLatBounds(points[0], points[0]));
                        map.fitBounds(bounds, { padding: { top: 60, right: 70, bottom: 60, left: 60 + panelWidth() }, maxZoom: 14, duration: animate ? 600 : 0 });
                    }

                    function refresh(refit = true) {
                        const filtered = data.entrepreneurs.filter(matchesFilters);
                        document.querySelectorAll('[data-count-route]').forEach((node) => {
                            node.textContent = number(filtered.filter((e) => routeKey(e) === node.dataset.countRoute).length);
                        });

                        const list = filtered.filter((e) => state.routes.has(routeKey(e)));
                        document.getElementById('mp-count').textContent = number(list.length);
                        document.getElementById('mp-empty').hidden = list.length > 0;

                        popup?.remove();
                        map.getSource('entrepreneurs')?.setData(geojson(list));
                        if (refit) fit(list);
                    }

                    let typing;
                    document.getElementById('mp-search').addEventListener('input', (event) => {
                        clearTimeout(typing);
                        typing = setTimeout(() => { state.query = normalize(event.target.value.trim()); refresh(); }, 200);
                    });
                    document.getElementById('mp-city').addEventListener('change', (event) => { state.city = event.target.value; refresh(); });
                    document.getElementById('mp-manager')?.addEventListener('change', (event) => { state.manager = event.target.value; refresh(); });
                    document.querySelectorAll('.mp-route').forEach((button) => button.addEventListener('click', () => {
                        const key = button.dataset.route;
                        state.routes.has(key) ? state.routes.delete(key) : state.routes.add(key);
                        button.setAttribute('aria-pressed', state.routes.has(key) ? 'true' : 'false');
                        refresh(false);
                    }));
                    document.getElementById('mp-actors').addEventListener('click', (event) => {
                        state.actors = ! state.actors;
                        event.currentTarget.setAttribute('aria-pressed', state.actors ? 'true' : 'false');
                        if (map.getLayer('actors')) map.setLayoutProperty('actors', 'visibility', state.actors ? 'visible' : 'none');
                    });

                    map.once('load', () => fit(visible(), false));

                    // Si se cambia entre modo claro y oscuro, el mapa cambia con el panel.
                    let wasDark = isDark();
                    new MutationObserver(() => {
                        if (isDark() !== wasDark) {
                            wasDark = isDark();
                            popup?.remove();
                            map.setStyle(styleUrl());
                        }
                    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                })();
            </script>
        @endpush
    @endif
</x-filament-panels::page>
