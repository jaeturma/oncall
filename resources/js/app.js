/**
 * Disable a form's submit controls the instant it is submitted, and swap the
 * clicked one for a spinner + "Please wait…" label. Prevents double taps on
 * slow connections (duplicate service requests, duplicate payments, etc.)
 * without needing a JS framework.
 */
const SPINNER_SVG = '<svg class="size-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>';

function submitControls(form) {
    return form.querySelectorAll('button:not([type="reset"]), input[type="submit"]');
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || 'skipLoading' in form.dataset) {
        return;
    }

    const controls = submitControls(form);
    const submitter = event.submitter && controls.length && Array.from(controls).includes(event.submitter)
        ? event.submitter
        : controls[0];

    if (!submitter || submitter.disabled) {
        event.preventDefault();

        return;
    }

    controls.forEach((control) => {
        control.disabled = true;
        control.dataset.loadingDisabled = '1';
    });

    submitter.setAttribute('aria-busy', 'true');

    if (submitter instanceof HTMLInputElement) {
        submitter.dataset.originalLabel = submitter.value;
        submitter.value = submitter.dataset.loadingText || 'Please wait…';
    } else {
        submitter.dataset.originalLabel = submitter.innerHTML;
        submitter.innerHTML = `<span class="inline-flex items-center justify-center gap-2">${SPINNER_SVG}<span>${submitter.dataset.loadingText || submitter.textContent.trim()}</span></span>`;
    }
});

// A page restored from the back/forward cache can still have controls
// disabled from a submission made before the user navigated away.
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
        return;
    }

    document.querySelectorAll('[data-loading-disabled]').forEach((control) => {
        control.disabled = false;
        control.removeAttribute('aria-busy');
        delete control.dataset.loadingDisabled;

        if (control.dataset.originalLabel === undefined) {
            return;
        }

        if (control instanceof HTMLInputElement) {
            control.value = control.dataset.originalLabel;
        } else {
            control.innerHTML = control.dataset.originalLabel;
        }

        delete control.dataset.originalLabel;
    });
});
