{{--
    Aviso de "Descargar Evidencias": muestra el avance mientras el servidor arma
    el ZIP y luego lo descarga. Lo inicia EvidenceZips::action() con
    window.evidenceZip.start({ token, label }). Mismo aspecto que las
    notificaciones de Filament (arriba a la derecha).
--}}
<style>
    .ez-stack { position: fixed; top: 1rem; right: 1rem; z-index: 60; display: flex; flex-direction: column; gap: .75rem; width: 24rem; max-width: calc(100vw - 2rem); pointer-events: none; }
    .ez-toast { pointer-events: auto; display: flex; gap: .75rem; padding: 1rem; border-radius: .75rem; background: #fff; font-size: .875rem; line-height: 1.25rem;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / .1), 0 4px 6px -4px rgb(0 0 0 / .1); outline: 1px solid rgba(var(--gray-950), .05); animation: ez-in .25s ease-out; transition: opacity .3s, transform .3s; }
    .ez-toast.ez-leaving { opacity: 0; transform: translateX(1rem); }
    .dark .ez-toast { background: rgb(var(--gray-900)); outline-color: rgba(255, 255, 255, .1); }
    .ez-icon { flex: none; width: 1.5rem; height: 1.5rem; }
    .ez-icon svg { width: 1.5rem; height: 1.5rem; }
    .ez-icon[data-tone="working"] { color: rgb(var(--primary-500)); }
    .ez-icon[data-tone="success"] { color: rgb(var(--success-500)); }
    .ez-icon[data-tone="warning"] { color: rgb(var(--warning-500)); }
    .ez-icon[data-tone="danger"]  { color: rgb(var(--danger-500)); }
    .ez-content { flex: 1; min-width: 0; }
    .ez-title { font-weight: 500; color: rgb(var(--gray-950)); }
    .dark .ez-title { color: #fff; }
    .ez-body { margin-top: .25rem; color: rgb(var(--gray-500)); overflow-wrap: anywhere; }
    .dark .ez-body { color: rgb(var(--gray-400)); }
    .ez-progress { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; }
    .ez-progress[hidden] { display: none; }
    .ez-bar { flex: 1; height: .375rem; border-radius: 9999px; background: rgb(var(--gray-200)); overflow: hidden; }
    .dark .ez-bar { background: rgba(255, 255, 255, .1); }
    .ez-fill { height: 100%; width: 0; border-radius: 9999px; background: rgb(var(--primary-600)); transition: width .4s ease; }
    .ez-percent { font-size: .75rem; font-weight: 600; color: rgb(var(--gray-700)); font-variant-numeric: tabular-nums; min-width: 2.6rem; text-align: right; }
    .dark .ez-percent { color: rgb(var(--gray-200)); }
    .ez-close { flex: none; align-self: flex-start; width: 1.25rem; height: 1.25rem; color: rgb(var(--gray-400)); background: none; border: 0; padding: 0; cursor: pointer; border-radius: .25rem; }
    .ez-close:hover { color: rgb(var(--gray-500)); }
    .ez-close:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 2px; }
    .ez-close svg { width: 1.25rem; height: 1.25rem; }
    @keyframes ez-in { from { opacity: 0; transform: translateX(1rem); } }
    @media (prefers-reduced-motion: reduce) { .ez-toast, .ez-fill { animation: none; transition: none; } }
</style>

<div id="ez-stack" class="ez-stack"></div>

<template id="ez-toast-template">
    <div class="ez-toast" role="status" aria-live="polite">
        <div class="ez-icon" data-tone="working"></div>
        <div class="ez-content">
            <div class="ez-title"></div>
            <div class="ez-body"></div>
            <div class="ez-progress">
                <div class="ez-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="ez-fill"></div></div>
                <span class="ez-percent">0 %</span>
            </div>
        </div>
        <button type="button" class="ez-close" aria-label="Cerrar"><x-filament::icon icon="heroicon-m-x-mark" /></button>
    </div>
</template>
<template id="ez-icon-working"><x-filament::icon icon="heroicon-o-archive-box-arrow-down" /></template>
<template id="ez-icon-success"><x-filament::icon icon="heroicon-o-check-circle" /></template>
<template id="ez-icon-warning"><x-filament::icon icon="heroicon-o-exclamation-triangle" /></template>
<template id="ez-icon-danger"><x-filament::icon icon="heroicon-o-x-circle" /></template>

<script>
    window.evidenceZip = (() => {
        const urls = { prepare: @js(route('evidences.prepare')), progress: @js(route('evidences.progress')) };
        const number = (n) => new Intl.NumberFormat('es-CO').format(n);

        function toast(label) {
            const el = document.getElementById('ez-toast-template').content.firstElementChild.cloneNode(true);
            const $ = (selector) => el.querySelector(selector);
            let timer = null;

            const close = () => {
                el.classList.add('ez-leaving');
                setTimeout(() => el.remove(), 300);
            };

            $('.ez-close').addEventListener('click', close);
            document.getElementById('ez-stack').appendChild(el);

            return {
                // Mientras se arma el ZIP: barra con el porcentaje.
                progress(percent, step) {
                    $('.ez-title').textContent = 'Preparando la descarga';
                    $('.ez-body').textContent = `${label} · ${step}`;
                    $('.ez-fill').style.width = `${percent}%`;
                    $('.ez-percent').textContent = `${percent} %`;
                    $('.ez-bar').setAttribute('aria-valuenow', percent);
                },
                // Resultado: cambia el ícono y el texto; si no es un error, se cierra solo.
                finish(tone, title, body) {
                    const icon = $('.ez-icon');
                    icon.dataset.tone = tone;
                    icon.replaceChildren(document.getElementById(`ez-icon-${tone}`).content.cloneNode(true));
                    $('.ez-title').textContent = title;
                    $('.ez-body').textContent = body;

                    if (tone === 'success') {
                        $('.ez-fill').style.width = '100%';
                        $('.ez-percent').textContent = '100 %';
                    } else {
                        $('.ez-progress').hidden = true;
                    }

                    clearTimeout(timer);
                    if (tone !== 'danger') timer = setTimeout(close, 8000);
                },
                init() {
                    $('.ez-icon').replaceChildren(document.getElementById('ez-icon-working').content.cloneNode(true));
                    this.progress(0, 'revisando los archivos…');
                },
            };
        }

        async function start({ token, label }) {
            const t = toast(label);
            t.init();

            let finished = false;
            const poll = setInterval(async () => {
                try {
                    const response = await fetch(`${urls.progress}?token=${encodeURIComponent(token)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    const { percent } = response.ok ? await response.json() : {};

                    if (! finished && typeof percent === 'number') {
                        t.progress(percent, percent < 5 ? 'revisando los archivos…' : 'empacando los archivos…');
                    }
                } catch (e) {
                    // Un sondeo fallido no detiene la descarga: se reintenta en el siguiente.
                }
            }, 1000);

            try {
                const response = await fetch(urls.prepare, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ token }),
                });

                finished = true;
                clearInterval(poll);

                if (! response.ok) {
                    throw new Error(response.status === 419
                        ? 'La sesión expiró. Recarga la página e intenta de nuevo.'
                        : `El servidor respondió con el error ${response.status}. Intenta de nuevo; si se repite, avisa al administrador.`);
                }

                const data = await response.json();

                if (data.status === 'empty') {
                    t.finish('warning', 'Sin evidencias', `${label}: los registros no tienen archivos ni videos para descargar.`);
                    return;
                }

                t.finish('success', 'Descarga lista', `${label}: ${number(data.files)} archivos de ${number(data.records)} registros. El navegador está descargando el ZIP.`);
                window.location.href = data.url;
            } catch (error) {
                finished = true;
                clearInterval(poll);
                t.finish('danger', 'No se pudo preparar la descarga', error.message || 'Intenta de nuevo.');
            }
        }

        return { start };
    })();
</script>
