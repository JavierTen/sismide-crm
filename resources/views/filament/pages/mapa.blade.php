<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Contador y leyenda -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Contador de emprendedores -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Emprendedores graficados</p>
                        <p class="text-2xl font-bold text-primary-600" id="entrepreneur-count">0</p>
                    </div>
                    <div class="bg-primary-100 dark:bg-primary-900 p-3 rounded-full">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Contador de actores -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Actores graficados</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="actor-count">0</p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-full">
                        <svg class="w-8 h-8 text-gray-900 dark:text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapa -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div id="map" style="height: calc(100vh - 380px); width: 100%; border-radius: 8px;"></div>
        </div>
    </div>

    @push('scripts')
    <script src='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css' rel='stylesheet' />

    <script>
        mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [-74.2973, 10.4103],
            zoom: 9
        });

        map.addControl(new mapboxgl.NavigationControl());

        const entrepreneurs = @js($this->getEntrepreneursWithCoordinates());
        const actors = @js($this->getActorsWithCoordinates());

        // Actualizar contadores
        document.getElementById('entrepreneur-count').textContent = entrepreneurs.length;
        document.getElementById('actor-count').textContent = actors.length;

        // Mantener etiquetas de lugares siempre visibles
        map.on('load', () => {
            const layers = map.getStyle().layers;

            layers.forEach((layer) => {
                if (layer.type === 'symbol' && layer.id.includes('label')) {
                    map.setLayoutProperty(layer.id, 'text-size', [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        0, 11,
                        22, 18
                    ]);

                    if (layer.id.includes('place')) {
                        map.setLayoutProperty(layer.id, 'text-size', [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            0, 13,
                            22, 20
                        ]);
                    }
                }
            });

            // Agregar marcadores con colores según la ruta
            entrepreneurs.forEach(entrepreneur => {
                const popupContent = `
                    <div style="padding: 12px; min-width: 320px; max-width: 400px;">
                        <h3 style="font-weight: bold; margin-bottom: 10px; font-size: 16px; color: #1f2937; word-wrap: break-word;">${entrepreneur.entrepreneur_name}</h3>
                        <p style="margin: 6px 0; font-size: 14px; word-wrap: break-word;"><strong>Emprendimiento:</strong> ${entrepreneur.business_name}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Municipio:</strong> ${entrepreneur.city}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Teléfono:</strong> ${entrepreneur.phone}</p>
                        <p style="margin: 6px 0; font-size: 14px; word-wrap: break-word;"><strong>Correo:</strong> ${entrepreneur.email}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Gestor:</strong> ${entrepreneur.manager}</p>
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: ${entrepreneur.route_color};">${entrepreneur.route}</p>

                    </div>
                `;

                const popup = new mapboxgl.Popup({ offset: 25 })
                    .setHTML(popupContent);

                // Crear marcador con color según la ruta
                new mapboxgl.Marker({ color: entrepreneur.route_color })
                    .setLngLat([entrepreneur.longitude, entrepreneur.latitude])
                    .setPopup(popup)
                    .addTo(map);
            });

            // Agregar marcadores de actores (color negro)
            actors.forEach(actor => {
                const popupContent = `
                    <div style="padding: 12px; min-width: 320px; max-width: 400px;">
                        <h3 style="font-weight: bold; margin-bottom: 10px; font-size: 16px; color: #1f2937; word-wrap: break-word;">${actor.name}</h3>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Tipo:</strong> ${actor.type}</p>
                        <p style="margin: 6px 0; font-size: 14px; word-wrap: break-word;"><strong>Contacto:</strong> ${actor.contact_name}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Municipio:</strong> ${actor.city}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Teléfono:</strong> ${actor.contact_phone}</p>
                        <p style="margin: 6px 0; font-size: 14px; word-wrap: break-word;"><strong>Correo:</strong> ${actor.contact_email}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Gestor:</strong> ${actor.manager}</p>
                    </div>
                `;

                const popup = new mapboxgl.Popup({ offset: 25 })
                    .setHTML(popupContent);

                // Crear marcador negro para actores
                new mapboxgl.Marker({ color: '#000000' })
                    .setLngLat([actor.longitude, actor.latitude])
                    .setPopup(popup)
                    .addTo(map);
            });
        });
    </script>

    <style>
        .mapboxgl-popup-content {
            width: 300px !important;
        }
    </style>

    @endpush
</x-filament-panels::page>
