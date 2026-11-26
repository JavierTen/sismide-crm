{{--
    Aviso de "Descargar Evidencias", arriba a la derecha como las notificaciones
    de Filament. Muestra el avance mientras el servidor arma el ZIP y lo retoma
    en cualquier página del panel: al cargar pregunta por las descargas del
    usuario. La que se empezó en esta página se baja sola al terminar; las que
    se retoman en otra página muestran el botón "Descargar ZIP".
    Lo inicia EvidenceZips::action() con window.evidenceZip.start({ token, label }).
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
    .ez-bar { flex: 1; height: .375rem; border-radius: 9999px; background: rgb(var(--gray-200)); overflow: hidden; }
    .dark .ez-bar { background: rgba(255, 255, 255, .1); }
    .ez-fill { height: 100%; width: 0; border-radius: 9999px; background: rgb(var(--primary-600)); transition: width .4s ease; }
    .ez-percent { font-size: .75rem; font-weight: 600; color: rgb(var(--gray-700)); font-variant-numeric: tabular-nums; min-width: 2.6rem; text-align: right; }
    .dark .ez-percent { color: rgb(var(--gray-200)); }
    .ez-action { display: inline-flex; align-items: center; gap: .35rem; margin-top: .7rem; padding: .4rem .75rem; border-radius: .5rem; font-size: .8rem; font-weight: 600;
        color: #fff; background: rgb(var(--primary-600)); text-decoration: none; }
    .ez-action:hover { background: rgb(var(--primary-500)); }
    .ez-action:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 2px; }
    .ez-action svg { width: 1rem; height: 1rem; }
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
            <a class="ez-action" hidden><x-filament::icon icon="heroicon-m-arrow-down-tray" />Descargar ZIP</a>
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
        const urls = { prepare: @js(route('evidences.prepare')), status: @js(route('evidences.status')), dismiss: @js(route('evidences.dismiss')) };
        const number = (n) => new Intl.NumberFormat('es-CO').format(n);
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const post = (url, body) => fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(body),
        });

        /** Avisos en pantalla, por token: { el, set(job), autoDownload, done } */
        const toasts = new Map();
        let polling = null;

        function toast(token, label, autoDownload) {
            const el = document.getElementById('ez-toast-template').content.firstElementChild.cloneNode(true);
            const $ = (selector) => el.querySelector(selector);
            const item = { el, autoDownload, done: false, timer: null };

            const remove = () => {
                el.classList.add('ez-leaving');
                setTimeout(() => el.remove(), 300);
                toasts.delete(token);
            };

            const icon = (tone) => {
                $('.ez-icon').dataset.tone = tone;
                $('.ez-icon').replaceChildren(document.getElementById(`ez-icon-${tone}`).content.cloneNode(true));
            };

            const finish = (tone, title, body, { autoClose = true, link = null } = {}) => {
                item.done = true;
                icon(tone);
                $('.ez-title').textContent = title;
                $('.ez-body').textContent = body;
                $('.ez-progress').hidden = tone !== 'success';
                $('.ez-fill').style.width = '100%';
                $('.ez-percent').textContent = '100 %';
                if (link) {
                    $('.ez-action').href = link;
                    $('.ez-action').hidden = false;
                }
                clearTimeout(item.timer);
                if (autoClose) item.timer = setTimeout(remove, 8000);
            };

            // Cerrar: si la descarga ya terminó, se descarta en el servidor; si sigue, solo se oculta aquí
            // (vuelve a aparecer en la siguiente página mientras se arma).
            $('.ez-close').addEventListener('click', () => {
                if (item.done) post(urls.dismiss, { token }).catch(() => {});
                remove();
            });
            // Al bajar el ZIP con el botón, el aviso se cierra (el servidor ya lo da por descargado).
            $('.ez-action').addEventListener('click', () => setTimeout(remove, 300));

            item.set = (job) => {
                if (job.status === 'working') {
                    icon('working');
                    $('.ez-title').textContent = 'Preparando la descarga';
                    $('.ez-body').textContent = `${label} · ${job.percent < 5 ? 'revisando los archivos…' : 'empacando los archivos…'}`;
                    $('.ez-fill').style.width = `${job.percent}%`;
                    $('.ez-percent').textContent = `${job.percent} %`;
                    $('.ez-bar').setAttribute('aria-valuenow', job.percent);
                } else if (job.status === 'ready') {
                    const files = `${number(job.files)} archivos de ${number(job.records)} registros`;
                    if (item.autoDownload) {
                        finish('success', 'Descarga lista', `${label}: ${files}. El navegador está descargando el ZIP.`);
                        window.location.href = job.url;
                    } else {
                        finish('success', 'Descarga lista', `${label}: ${files}.`, { autoClose: false, link: job.url });
                    }
                } else if (job.status === 'empty') {
                    finish('warning', 'Sin evidencias', `${label}: los registros no tienen archivos ni videos para descargar.`, { autoClose: item.autoDownload });
                    // Visto en la misma página donde se pidió: no hace falta mostrarlo de nuevo en la siguiente.
                    if (item.autoDownload) post(urls.dismiss, { token }).catch(() => {});
                } else if (job.status === 'failed') {
                    finish('danger', 'No se pudo preparar la descarga', job.message || 'Intenta de nuevo.', { autoClose: false });
                }
            };

            document.getElementById('ez-stack').appendChild(el);
            toasts.set(token, item);

            return item;
        }

        // Pregunta por las descargas del usuario y actualiza (o crea) cada aviso.
        async function refresh() {
            let jobs = [];
            try {
                const response = await fetch(urls.status, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (! response.ok) return;
                jobs = await response.json();
            } catch (e) {
                return; // Sin conexión por un momento: se reintenta en la siguiente consulta.
            }

            jobs.forEach((job) => {
                const item = toasts.get(job.token) ?? toast(job.token, job.label, false);
                if (! item.done) item.set(job);
            });

            const pending = [...toasts.values()].some((item) => ! item.done);
            if (pending && ! polling) polling = setInterval(refresh, 1000);
            if (! pending && polling) { clearInterval(polling); polling = null; }
        }

        async function start({ token, label }) {
            const item = toast(token, label, true);
            item.set({ status: 'working', percent: 0 });

            try {
                const response = await post(urls.prepare, { token });
                if (! response.ok) {
                    throw new Error(response.status === 419
                        ? 'La sesión expiró. Recarga la página e intenta de nuevo.'
                        : `El servidor respondió con el error ${response.status}. Intenta de nuevo; si se repite, avisa al administrador.`);
                }
                refresh();
            } catch (error) {
                item.set({ status: 'failed', message: error.message });
            }
        }

        // Al abrir cualquier página del panel, retoma las descargas que estén en curso o listas.
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refresh);
        else refresh();

        return { start };
    })();
</script>
