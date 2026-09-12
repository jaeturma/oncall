/**
 * Oncall Philippines front-end behaviour.
 *
 * Deliberately framework-free: a handful of small, declarative behaviours
 * driven by data attributes so Blade views stay readable and the bundle
 * stays tiny.
 *
 *  - Submit guard: disables submit controls + spinner to stop double taps.
 *  - Disclosures: [data-toggle="id"] opens/closes [id] with aria-expanded.
 *  - Mobile nav / drawers: [data-drawer] with body scroll lock, Esc, focus.
 *  - Menus: [data-menu] dropdowns that close on outside click / Esc.
 *  - Stepper forms: [data-stepper] multi-step forms with native validation.
 *  - Dependent selects: [data-municipalities-for] loads cities for a province.
 */

const SPINNER_SVG = '<svg class="size-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>';

/* ------------------------------------------------------------------ */
/* Submit guard                                                        */
/* ------------------------------------------------------------------ */

function submitControls(form) {
    return form.querySelectorAll('button:not([type="reset"]):not([type="button"]), input[type="submit"]');
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

/* ------------------------------------------------------------------ */
/* Disclosures (simple show/hide)                                      */
/* ------------------------------------------------------------------ */

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-toggle]');

    if (!trigger) {
        return;
    }

    const target = document.getElementById(trigger.dataset.toggle);

    if (!target) {
        return;
    }

    const open = target.hidden;
    target.hidden = !open;
    trigger.setAttribute('aria-expanded', String(open));
});

/* ------------------------------------------------------------------ */
/* Drawers (mobile navigation, filter sheet)                           */
/* ------------------------------------------------------------------ */

let openDrawer = null;
let drawerOpener = null;

function focusableIn(root) {
    return Array.from(root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter((el) => el.offsetParent !== null);
}

function closeDrawer() {
    if (!openDrawer) {
        return;
    }

    openDrawer.hidden = true;
    document.body.classList.remove('overflow-hidden');
    document.querySelectorAll(`[data-drawer-open="${openDrawer.id}"]`).forEach((btn) => btn.setAttribute('aria-expanded', 'false'));

    if (drawerOpener) {
        drawerOpener.focus({ preventScroll: true });
    }

    openDrawer = null;
    drawerOpener = null;
}

function showDrawer(drawer, opener) {
    closeDrawer();
    openDrawer = drawer;
    drawerOpener = opener;
    drawer.hidden = false;
    document.body.classList.add('overflow-hidden');
    document.querySelectorAll(`[data-drawer-open="${drawer.id}"]`).forEach((btn) => btn.setAttribute('aria-expanded', 'true'));

    const [first] = focusableIn(drawer);

    if (first) {
        first.focus({ preventScroll: true });
    }
}

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-drawer-open]');

    if (opener) {
        const drawer = document.getElementById(opener.dataset.drawerOpen);

        if (drawer) {
            drawer.hidden ? showDrawer(drawer, opener) : closeDrawer();
        }

        return;
    }

    if (event.target.closest('[data-drawer-close]')) {
        closeDrawer();
    }
});

document.addEventListener('keydown', (event) => {
    if (!openDrawer) {
        return;
    }

    if (event.key === 'Escape') {
        closeDrawer();

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const items = focusableIn(openDrawer);

    if (items.length === 0) {
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});

// Close a drawer automatically when the viewport grows past the breakpoint
// it was designed for (e.g. rotating a tablet), so the page never gets stuck
// with a locked body.
const desktopQuery = window.matchMedia('(min-width: 1024px)');
desktopQuery.addEventListener('change', (event) => {
    if (event.matches && openDrawer && openDrawer.dataset.drawer === 'mobile') {
        closeDrawer();
    }
});

/* ------------------------------------------------------------------ */
/* Menus (avatar dropdown, row action menus)                           */
/* ------------------------------------------------------------------ */

function closeMenus(except = null) {
    document.querySelectorAll('[data-menu]').forEach((menu) => {
        if (menu === except) {
            return;
        }

        const panel = menu.querySelector('[data-menu-panel]');
        const button = menu.querySelector('[data-menu-button]');

        if (panel) {
            panel.hidden = true;
        }

        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }
    });
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-menu-button]');

    if (button) {
        const menu = button.closest('[data-menu]');
        const panel = menu?.querySelector('[data-menu-panel]');

        if (!panel) {
            return;
        }

        const willOpen = panel.hidden;
        closeMenus(menu);
        panel.hidden = !willOpen;
        button.setAttribute('aria-expanded', String(willOpen));

        return;
    }

    if (!event.target.closest('[data-menu-panel]')) {
        closeMenus();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMenus();
    }
});

/* ------------------------------------------------------------------ */
/* Stepper forms                                                       */
/* ------------------------------------------------------------------ */

function initStepper(form) {
    const steps = Array.from(form.querySelectorAll('[data-step]'));
    const indicators = Array.from(form.querySelectorAll('[data-step-indicator]'));

    if (steps.length === 0) {
        return;
    }

    const show = (index) => {
        steps.forEach((step, i) => {
            step.hidden = i !== index;
        });
        indicators.forEach((indicator, i) => {
            indicator.dataset.state = i < index ? 'done' : i === index ? 'current' : 'todo';
            indicator.setAttribute('aria-current', i === index ? 'step' : 'false');
        });
        form.dataset.currentStep = String(index);

        const heading = steps[index].querySelector('[data-step-title]');

        if (heading) {
            heading.setAttribute('tabindex', '-1');
            heading.focus({ preventScroll: true });
        }
    };

    // Start on the first step that has a server-side validation error, if any.
    const errorIndex = steps.findIndex((step) => step.querySelector('[data-has-error]'));
    show(errorIndex >= 0 ? errorIndex : 0);

    form.addEventListener('click', (event) => {
        const next = event.target.closest('[data-step-next]');
        const prev = event.target.closest('[data-step-prev]');
        const current = Number(form.dataset.currentStep || 0);

        if (next) {
            event.preventDefault();
            const fields = steps[current].querySelectorAll('input, select, textarea');
            let valid = true;

            fields.forEach((field) => {
                if (!field.reportValidity()) {
                    valid = false;
                }
            });

            if (valid) {
                show(Math.min(current + 1, steps.length - 1));
            }
        }

        if (prev) {
            event.preventDefault();
            show(Math.max(current - 1, 0));
        }
    });

    // Enter on a non-final step behaves like "Continue", not "Submit".
    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.target instanceof HTMLTextAreaElement) {
            return;
        }

        const current = Number(form.dataset.currentStep || 0);

        if (current < steps.length - 1) {
            event.preventDefault();
            steps[current].querySelector('[data-step-next]')?.click();
        }
    });
}

document.querySelectorAll('form[data-stepper]').forEach(initStepper);

/* ------------------------------------------------------------------ */
/* Dependent municipality selects                                      */
/* ------------------------------------------------------------------ */

function initDependentSelect(municipalitySelect) {
    const provinceSelect = document.getElementById(municipalitySelect.dataset.municipalitiesFor);
    const template = municipalitySelect.dataset.municipalitiesUrl; // .../locations/PROVINCE/municipalities

    if (!provinceSelect || !template) {
        return;
    }

    const placeholder = municipalitySelect.querySelector('option[value=""]')?.textContent || 'Select';

    const load = async (keepValue) => {
        const provinceId = provinceSelect.value;
        municipalitySelect.innerHTML = `<option value="">${placeholder}</option>`;

        if (!provinceId) {
            municipalitySelect.disabled = true;

            return;
        }

        municipalitySelect.disabled = true;

        try {
            const response = await fetch(template.replace('PROVINCE', provinceId), { headers: { Accept: 'application/json' } });
            const municipalities = await response.json();

            municipalities.forEach((municipality) => {
                const option = document.createElement('option');
                option.value = municipality.id;
                option.textContent = municipality.name;
                option.selected = String(municipality.id) === String(keepValue);
                municipalitySelect.append(option);
            });
        } catch {
            // Leave the placeholder in place; the server still validates.
        } finally {
            municipalitySelect.disabled = false;
        }
    };

    provinceSelect.addEventListener('change', () => load(null));

    if (municipalitySelect.dataset.municipalitiesInitial === 'load') {
        load(municipalitySelect.dataset.selected || null);
    }
}

document.querySelectorAll('select[data-municipalities-for]').forEach(initDependentSelect);

/* ------------------------------------------------------------------ */
/* Category shortcuts on the landing page                              */
/* ------------------------------------------------------------------ */

document.addEventListener('click', (event) => {
    const shortcut = event.target.closest('[data-pick-help]');

    if (!shortcut) {
        return;
    }

    const help = document.getElementById('help');
    const province = document.getElementById('province_id');

    if (!help) {
        return;
    }

    help.value = shortcut.dataset.pickHelp;
    help.dispatchEvent(new Event('change', { bubbles: true }));

    const finder = document.getElementById('find-help');
    finder?.scrollIntoView({ behavior: 'smooth', block: 'start' });

    window.setTimeout(() => {
        (province && !province.value ? province : help).focus({ preventScroll: true });
    }, 350);
});

/* ------------------------------------------------------------------ */
/* Auto-submit controls (e.g. the availability status select)          */
/* ------------------------------------------------------------------ */

document.addEventListener('change', (event) => {
    const field = event.target.closest('[data-autosubmit]');

    if (field) {
        field.form?.requestSubmit();
    }
});
