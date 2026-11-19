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
|   data-depends-on="id"       <select> whose options follow another <select> (division > district > upazila)
|   data-autosubmit            submits the form when the field changes
|   data-tip="text"            tooltip on hover and keyboard focus (charts)
|   data-brand-list="id"       medicine <select> that suggests brand names in a <datalist>
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

// ----- Dependent dropdowns (division -> district -> upazila) -----
// <select data-depends-on="parentId"> only offers <option data-parent="value"> that match the parent's value.
// When a parent changes, children that no longer fit are cleared, and the change passes down the chain.
function syncDependent(child) {
    const parent = document.getElementById(child.dataset.dependsOn);
    if (!parent) return;

    const value = parent.value;
    let cleared = false;
    child.querySelectorAll('option[data-parent]').forEach((option) => {
        // With no parent chosen, show everything (filters) unless the child needs a parent (upazila).
        const match = value ? option.dataset.parent === value : !child.hasAttribute('data-needs-parent');
        option.hidden = !match;
        option.disabled = !match;
        if (!match && option.selected) {
            child.value = '';
            cleared = true;
        }
    });

    return cleared;
}

document.querySelectorAll('select[data-depends-on]').forEach((child) => {
    syncDependent(child);
    document.getElementById(child.dataset.dependsOn)?.addEventListener('change', () => {
        syncDependent(child);
        child.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

// ----- Auto-submit -----
// <select data-autosubmit> sends its form as soon as the value changes (sort order).
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-autosubmit]')) event.target.form?.requestSubmit();
});

// ----- Tooltips -----
// Any element with data-tip="text" shows a small tooltip on hover and keyboard focus (used by charts).
const tooltip = document.createElement('div');
tooltip.setAttribute('role', 'tooltip');
tooltip.className =
    'pointer-events-none fixed z-[90] hidden max-w-60 rounded-control bg-ink px-2.5 py-1.5 text-xs font-medium text-white shadow-raised';
document.body.append(tooltip);

function showTip(target) {
    tooltip.textContent = target.dataset.tip;
    tooltip.classList.remove('hidden');

    const box = target.getBoundingClientRect();
    const tip = tooltip.getBoundingClientRect();
    const left = Math.min(Math.max(8, box.left + box.width / 2 - tip.width / 2), window.innerWidth - tip.width - 8);
    const top = box.top - tip.height - 8 > 0 ? box.top - tip.height - 8 : box.bottom + 8;

    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;
}

const hideTip = () => tooltip.classList.add('hidden');

document.addEventListener('mouseover', (event) => {
    const target = event.target.closest?.('[data-tip]');
    target ? showTip(target) : hideTip();
});
document.addEventListener('focusin', (event) => {
    const target = event.target.closest?.('[data-tip]');
    if (target) showTip(target);
});
document.addEventListener('focusout', hideTip);
window.addEventListener('scroll', hideTip, { passive: true });

// ----- Brand name suggestions -----
// <select data-brand-list="datalistId"> with <option data-brands="Napa|Ace"> fills the datalist
// for the brand name field with the brands of the chosen catalogue medicine.
document.querySelectorAll('select[data-brand-list]').forEach((select) => {
    const list = document.getElementById(select.dataset.brandList);
    if (!list) return;

    const fill = () => {
        const brands = select.selectedOptions[0]?.dataset.brands?.split('|').filter(Boolean) ?? [];
        list.replaceChildren(...brands.map((brand) => Object.assign(document.createElement('option'), { value: brand })));
    };

    fill();
    select.addEventListener('change', fill);
});
