<x-filament-panels::page>
    <div class="space-y-6">
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

        <!-- Mapa -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div id="map" style="height: calc(100vh - 280px); width: 100%; border-radius: 8px;"></div>
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

        // Actualizar contador
        document.getElementById('entrepreneur-count').textContent = entrepreneurs.length;

        // Mantener etiquetas de lugares siempre visibles
        map.on('load', () => {
            const layers = map.getStyle().layers;

            layers.forEach((layer) => {
                if (layer.type === 'symbol' && layer.id.includes('label')) {
                    // Aumentar tamaño de texto según zoom
                    map.setLayoutProperty(layer.id, 'text-size', [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        0, 11,
                        22, 18
                    ]);

                    // Hacer las etiquetas de lugares más prominentes
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

            // Agregar marcadores después de cargar el mapa
            entrepreneurs.forEach(entrepreneur => {
                const popupContent = `
                    <div style="padding: 12px; min-width: 320px; max-width: 400px;">
                        <h3 style="font-weight: bold; margin-bottom: 10px; font-size: 16px; color: #1f2937; word-wrap: break-word;">${entrepreneur.entrepreneur_name}</h3>
                        <p style="margin: 6px 0; font-size: 14px; word-wrap: break-word;"><strong>Emprendimiento:</strong> ${entrepreneur.business_name}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Municipio:</strong> ${entrepreneur.city}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Teléfono:</strong> ${entrepreneur.phone}</p>
                        <p style="margin: 6px 0; font-size: 14px; word-wrap: break-word;"><strong>Correo:</strong> ${entrepreneur.email}</p>
                        <p style="margin: 6px 0; font-size: 14px;"><strong>Gestor:</strong> ${entrepreneur.manager}</p>
                    </div>
                `;

                const popup = new mapboxgl.Popup({ offset: 25 })
                    .setHTML(popupContent);

                new mapboxgl.Marker({ color: '#ef4444' })
                    .setLngLat([entrepreneur.longitude, entrepreneur.latitude])
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
