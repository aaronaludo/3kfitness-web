(function () {
    const routeMeta = document.querySelector('meta[name="print-preview-route"]');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    const previewRoute = routeMeta ? routeMeta.content : null;
    const csrfToken = csrfMeta ? csrfMeta.content : null;

    function createHidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    }

    function submitPreview(payload) {
        if (!previewRoute || !payload) {
            return false;
        }

        try {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = previewRoute;
            form.target = '_blank';
            form.className = 'd-none print-preview-form';

            if (csrfToken) {
                form.appendChild(createHidden('_token', csrfToken));
            }

            form.appendChild(createHidden('payload', JSON.stringify(payload)));
            document.body.appendChild(form);
            form.submit();

            setTimeout(() => form.remove(), 4000);
            return true;
        } catch (error) {
            console.error('Unable to open print preview:', error);
            return false;
        }
    }

    function composePayload(basePayload, headers, rows, filters) {
        const safeRows = Array.isArray(rows) ? rows : [];
        const safeHeaders = Array.isArray(headers) ? headers : [];
        const safeFilters = Array.isArray(filters) ? filters : [];

        return {
            title: (basePayload && basePayload.title) || 'Print preview',
            generated_at: (basePayload && basePayload.generated_at) || '',
            count: basePayload && typeof basePayload.count !== 'undefined' && basePayload.count !== null
                ? basePayload.count
                : safeRows.length,
            filters: safeFilters,
            table: {
                headers: safeHeaders,
                rows: safeRows,
            },
            meta: (basePayload && basePayload.meta) || {},
            notes: basePayload && basePayload.notes ? basePayload.notes : null,
        };
    }

    function tryOpen(basePayload, headers, rows, filters) {
        const payload = composePayload(basePayload, headers, rows, filters);
        return submitPreview(payload);
    }

    function resolveScope() {
        const modalEl = document.getElementById('printScopeModal');
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return Promise.resolve('current');
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        return new Promise((resolve) => {
            let settled = false;

            const cleanup = (result) => {
                if (settled) return;
                settled = true;
                modal.hide();
                resolve(result);
            };

            const onHidden = () => cleanup(null);
            modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });

            const currentBtn = modalEl.querySelector('[data-print-scope="current"]');
            const allBtn = modalEl.querySelector('[data-print-scope="all"]');

            if (currentBtn) {
                currentBtn.addEventListener('click', () => cleanup('current'), { once: true });
            }
            if (allBtn) {
                allBtn.addEventListener('click', () => cleanup('all'), { once: true });
            }

            modal.show();
        });
    }

    window.PrintPreview = {
        route: previewRoute,
        open: submitPreview,
        buildPayload: composePayload,
        tryOpen: tryOpen,
        chooseScope: resolveScope,
    };

    // Legacy single print form handler (keeps existing UX where present)
    const fallbackForm = document.getElementById('print-form');
    const fallbackButton = document.getElementById('print-submit-button');
    const fallbackLoader = document.getElementById('print-loader');

    if (fallbackForm && fallbackButton) {
        fallbackForm.addEventListener('submit', function () {
            fallbackButton.disabled = true;
            if (fallbackLoader) fallbackLoader.classList.remove('d-none');

            setTimeout(() => {
                fallbackButton.disabled = false;
                if (fallbackLoader) fallbackLoader.classList.add('d-none');
            }, 2500);
        });
    }
})();
