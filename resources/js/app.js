/*
|--------------------------------------------------------------------------
| Medimatch BD interface behaviour
|--------------------------------------------------------------------------
| Small, dependency-free helpers driven by data attributes:
|
|   data-toggle="#id"          show/hide an element (mobile menu, filters, drawer)
|   data-dropdown              wrapper for a dropdown menu
|   data-dropdown-trigger      button that opens the dropdown
|   data-dropdown-menu         the menu panel
|   data-modal-open="id"       opens <dialog id="id">
|   data-modal-close           closes the dialog it is inside
|   data-dismiss               removes the closest [data-dismissible] element
|   <form data-loading>        disables the submit button while the form is sent
|   data-district-of="id"      district <select> that follows a division <select>
|   data-autosubmit            submits the form when the field changes
*/

// ----- Show / hide -----
function setToggle(button, open) {
    const target = document.querySelector(button.dataset.toggle);
    if (!target) return;
    target.classList.toggle('hidden', !open);
    document
        .querySelectorAll(`[data-toggle="${button.dataset.toggle}"]`)
        .forEach((el) => el.setAttribute('aria-expanded', String(open)));
}

// ----- Dropdowns -----
function closeDropdowns(except = null) {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        if (dropdown === except) return;
        dropdown.querySelector('[data-dropdown-menu]')?.classList.add('hidden');
        dropdown.querySelector('[data-dropdown-trigger]')?.setAttribute('aria-expanded', 'false');
    });
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle]');
    if (toggle) {
        const target = document.querySelector(toggle.dataset.toggle);
        if (target) setToggle(toggle, target.classList.contains('hidden'));
        return;
    }

    const trigger = event.target.closest('[data-dropdown-trigger]');
    if (trigger) {
        const dropdown = trigger.closest('[data-dropdown]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');
        const open = menu.classList.contains('hidden');
        closeDropdowns(dropdown);
        menu.classList.toggle('hidden', !open);
        trigger.setAttribute('aria-expanded', String(open));
        return;
    }

    // A click anywhere outside an open dropdown closes it
    if (!event.target.closest('[data-dropdown-menu]')) closeDropdowns();

    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
        document.getElementById(opener.dataset.modalOpen)?.showModal();
        return;
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
        closer.closest('dialog')?.close();
        return;
    }

    // A click on the dark backdrop (the <dialog> itself) closes the modal
    if (event.target instanceof HTMLDialogElement && event.target.hasAttribute('data-modal')) {
        event.target.close();
        return;
    }

    const dismiss = event.target.closest('[data-dismiss]');
    if (dismiss) dismiss.closest('[data-dismissible]')?.remove();
});

// Escape closes dropdowns and drawers (the browser already closes dialogs)
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeDropdowns();
    document.querySelectorAll('[data-drawer]:not(.hidden)').forEach((drawer) => {
        const button = document.querySelector(`[data-toggle="#${drawer.id}"]`);
        if (button) {
            setToggle(button, false);
            button.focus();
        }
    });
});

// ----- Form loading state -----
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-loading')) return;

    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.querySelector('[data-spinner]')?.classList.remove('hidden');
    });
});

// When the browser restores a page from history, re-enable any disabled submit buttons
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelectorAll('form[data-loading] button[aria-busy="true"]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.querySelector('[data-spinner]')?.classList.add('hidden');
    });
});

// ----- Division -> district dropdowns -----
// <select data-district-of="division"> only offers districts of the chosen division.
function syncDistricts(districtSelect) {
    const divisionSelect = document.getElementById(districtSelect.dataset.districtOf);
    if (!divisionSelect) return;

    const division = divisionSelect.value;
    districtSelect.querySelectorAll('option[data-division]').forEach((option) => {
        const match = !division || option.dataset.division === division;
        option.hidden = !match;
        option.disabled = !match;
        if (!match && option.selected) districtSelect.value = '';
    });
}

document.querySelectorAll('select[data-district-of]').forEach((districtSelect) => {
    syncDistricts(districtSelect);
    document
        .getElementById(districtSelect.dataset.districtOf)
        ?.addEventListener('change', () => syncDistricts(districtSelect));
});

// ----- Auto-submit -----
// <select data-autosubmit> sends its form as soon as the value changes (sort order).
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-autosubmit]')) event.target.form?.requestSubmit();
});
