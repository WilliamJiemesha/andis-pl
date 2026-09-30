const uppercaseInputs = document.querySelectorAll('[data-uppercase]');

for (const input of uppercaseInputs) {
    input.addEventListener('input', () => {
        input.value = input.value.toUpperCase();
    });
}

const currencyInputs = document.querySelectorAll('[data-currency-input]');

for (const input of currencyInputs) {
    const formatCurrency = (value) => {
        const digits = value.replace(/\D+/g, '');

        if (digits === '') {
            return '';
        }

        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0,
        }).format(Number(digits));
    };

    input.value = formatCurrency(input.value);

    input.addEventListener('input', () => {
        input.value = formatCurrency(input.value);
    });
}

const modalTriggers = document.querySelectorAll('[data-open-modal]');
const modalClosers = document.querySelectorAll('[data-close-modal]');
const sidebar = document.querySelector('[data-sidebar]');
const sidebarOpeners = document.querySelectorAll('[data-sidebar-open]');
const sidebarClosers = document.querySelectorAll('[data-sidebar-close]');
const pageBody = document.body;

const openSidebar = () => {
    if (!(sidebar instanceof HTMLElement)) {
        return;
    }

    sidebar.classList.add('is-open');
    pageBody.classList.add('sidebar-open');

    for (const closer of sidebarClosers) {
        closer.hidden = false;
    }
};

const closeSidebar = () => {
    if (!(sidebar instanceof HTMLElement)) {
        return;
    }

    sidebar.classList.remove('is-open');
    pageBody.classList.remove('sidebar-open');

    for (const closer of sidebarClosers) {
        if (closer.classList.contains('app-sidebar-overlay')) {
            closer.hidden = true;
        }
    }
};

for (const opener of sidebarOpeners) {
    opener.addEventListener('click', openSidebar);
}

for (const closer of sidebarClosers) {
    closer.addEventListener('click', closeSidebar);
}

window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        closeSidebar();
    }
});

for (const trigger of modalTriggers) {
    trigger.addEventListener('click', () => {
        closeSidebar();
        const target = document.getElementById(trigger.dataset.openModal);

        if (target) {
            target.hidden = false;
            const focusTarget = target.querySelector('[data-initial-focus]');

            if (focusTarget instanceof HTMLElement) {
                window.setTimeout(() => focusTarget.focus(), 60);
            }
        }
    });
}

for (const closer of modalClosers) {
    closer.addEventListener('click', () => {
        const modal = closer.closest('.modal-shell');

        if (modal) {
            modal.hidden = true;
        }
    });
}

const infoPopovers = document.querySelectorAll('[data-info-popover]');

for (const popover of infoPopovers) {
    const toggle = popover.querySelector('[data-info-popover-toggle]');
    const panel = popover.querySelector('[data-info-popover-panel]');
    let pinned = false;

    if (!(toggle instanceof HTMLElement) || !(panel instanceof HTMLElement)) {
        continue;
    }

    const show = () => {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
    };

    const hide = () => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        pinned = false;
    };

    popover.addEventListener('mouseenter', show);
    popover.addEventListener('mouseleave', () => {
        if (! pinned) {
            hide();
        }
    });

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        pinned = ! pinned;
        if (pinned) {
            show();
        } else {
            hide();
        }
    });

    panel.addEventListener('click', (event) => {
        event.stopPropagation();
        hide();
    });

    document.addEventListener('click', (event) => {
        if (! popover.contains(event.target)) {
            hide();
        }
    });
}

const priceReviewBatchForm = document.querySelector('[data-price-review-batch-form]');
const priceReviewEditToggle = document.querySelector('[data-price-review-edit-toggle]');
const priceReviewCancel = document.querySelector('[data-price-review-cancel]');
const priceReviewSubmit = document.querySelector('[data-price-review-submit]');

if (priceReviewBatchForm instanceof HTMLFormElement && priceReviewEditToggle instanceof HTMLElement) {
    const editParts = document.querySelectorAll('[data-price-review-edit]');
    const normalParts = document.querySelectorAll('[data-price-review-normal]');

    const setPriceReviewEditMode = (editing) => {
        for (const part of editParts) {
            part.classList.toggle('hidden', ! editing);
        }

        for (const part of normalParts) {
            part.classList.toggle('hidden', editing);
        }

        priceReviewEditToggle.classList.toggle('hidden', editing);
        priceReviewSubmit?.classList.toggle('hidden', ! editing);
        priceReviewCancel?.classList.toggle('hidden', ! editing);

        if (editing) {
            const firstInput = priceReviewBatchForm.querySelector('input[data-currency-input]');
            if (firstInput instanceof HTMLInputElement) {
                firstInput.focus();
            }
        }
    };

    priceReviewEditToggle.addEventListener('click', () => setPriceReviewEditMode(true));
    priceReviewCancel?.addEventListener('click', () => setPriceReviewEditMode(false));
}

document.addEventListener('click', (event) => {
    const target = event.target;

    if (target instanceof HTMLElement && target.classList.contains('modal-shell')) {
        target.hidden = true;
    }
});

const searchModalForm = document.querySelector('[data-search-modal-form]');
const searchResults = document.querySelector('[data-search-results]');

if (searchModalForm && searchResults) {
    searchModalForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(searchModalForm);
        const params = new URLSearchParams(formData);
        params.set('modal', '1');

        searchResults.innerHTML = '<div class="rounded-2xl border border-stone-200 px-4 py-6 text-sm text-stone-500">Loading...</div>';

        const response = await fetch(`/search-items?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        searchResults.innerHTML = await response.text();
    });
}

const masterCombobox = document.querySelector('[data-master-combobox]');
const masterSearch = document.querySelector('[data-master-search]');
const masterHiddenInput = masterCombobox?.querySelector('input[name="master_item_id"]');
const masterMenu = document.querySelector('[data-master-menu]');
const masterToggle = document.querySelector('[data-master-toggle]');
const inlineMasterForm = document.querySelector('[data-inline-master-form]');
const inlineMasterToggle = document.querySelector('input[name="create_master_inline"]');
const inlineMasterSave = document.querySelector('[data-save-inline-master]');
const incomingNotes = document.getElementById('incoming_notes');

if (masterCombobox instanceof HTMLElement && masterSearch instanceof HTMLInputElement && masterMenu instanceof HTMLElement) {
    const getOptions = () => Array.from(masterMenu.querySelectorAll('[data-master-option]'));

    const openMenu = () => {
        masterMenu.classList.remove('hidden');
    };

    const closeMenu = () => {
        masterMenu.classList.add('hidden');
    };

    const applyMasterFilter = () => {
        const query = masterSearch.value.trim().toUpperCase();
        let visibleCount = 0;

        for (const option of getOptions()) {
            const text = option.textContent?.toUpperCase() ?? '';
            const isCreate = option.dataset.value === '__create__';
            const shouldShow = isCreate || query === '' || text.includes(query);
            option.classList.toggle('hidden', ! shouldShow);

            if (shouldShow) {
                visibleCount += 1;
            }
        }

        masterMenu.classList.toggle('hidden', visibleCount === 0);
    };

    const syncMasterSelection = (selected) => {
        const creatingInline = selected?.dataset.value === '__create__';

        if (inlineMasterForm instanceof HTMLElement) {
            inlineMasterForm.classList.toggle('hidden', ! creatingInline);
        }

        if (inlineMasterToggle instanceof HTMLInputElement) {
            inlineMasterToggle.value = creatingInline ? '1' : '0';
        }

        if (masterHiddenInput instanceof HTMLInputElement) {
            masterHiddenInput.value = creatingInline ? '' : (selected?.dataset.value ?? '');
        }

        if (! creatingInline && selected && incomingNotes instanceof HTMLTextAreaElement) {
            incomingNotes.value = selected.dataset.notes ?? '';
        }
    };

    const selectOption = (option) => {
        masterSearch.value = option.dataset.value === '__create__'
            ? option.textContent?.trim() ?? ''
            : option.textContent?.trim() ?? '';
        syncMasterSelection(option);
        if (option.dataset.value === '__create__') {
            openMenu();
        } else {
            closeMenu();
        }
    };

    masterSearch.addEventListener('focus', () => {
        openMenu();
        applyMasterFilter();
    });

    masterSearch.addEventListener('input', () => {
        if (masterHiddenInput instanceof HTMLInputElement) {
            masterHiddenInput.value = '';
        }
        openMenu();
        applyMasterFilter();
    });

    masterToggle?.addEventListener('click', () => {
        if (masterMenu.classList.contains('hidden')) {
            openMenu();
            applyMasterFilter();
            masterSearch.focus();
        } else {
            closeMenu();
        }
    });

    for (const option of getOptions()) {
        option.addEventListener('click', () => selectOption(option));
    }

    document.addEventListener('click', (event) => {
        if (! masterCombobox.contains(event.target)) {
            closeMenu();
        }
    });
}

if (inlineMasterSave instanceof HTMLButtonElement && masterMenu instanceof HTMLElement) {
    inlineMasterSave.addEventListener('click', async () => {
        const barang = document.getElementById('new_master_barang');
        const merk = document.getElementById('new_master_merk');
        const tipe = document.getElementById('new_master_tipe');
        const aliasName = document.getElementById('new_master_alias_name');
        const notes = document.getElementById('new_master_notes');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            ?? document.querySelector('input[name="_token"]')?.value;

        const payload = new URLSearchParams({
            barang: barang instanceof HTMLInputElement ? barang.value : '',
            merk: merk instanceof HTMLInputElement ? merk.value : '',
            tipe: tipe instanceof HTMLInputElement ? tipe.value : '',
            alias_name: aliasName instanceof HTMLInputElement ? aliasName.value : '',
            notes: notes instanceof HTMLTextAreaElement ? notes.value : '',
        });

        const response = await fetch('/master-items/quick-create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            body: payload.toString(),
        });

        if (! response.ok) {
            return;
        }

        const data = await response.json();
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'combo-box__option';
        option.dataset.masterOption = '';
        option.dataset.value = String(data.id);
        option.dataset.notes = data.notes ?? '';
        const optionLabel = `${data.alias_name} · ${data.official_name}`;
        option.textContent = optionLabel;
        option.addEventListener('click', () => {
            masterSearch.value = optionLabel;
            if (masterHiddenInput instanceof HTMLInputElement) {
                masterHiddenInput.value = String(data.id);
            }
            if (incomingNotes instanceof HTMLTextAreaElement) {
                incomingNotes.value = data.notes ?? '';
            }
            masterMenu.classList.add('hidden');
        });
        masterMenu.append(option);

        if (inlineMasterForm instanceof HTMLElement) {
            inlineMasterForm.classList.add('hidden');
        }

        if (inlineMasterToggle instanceof HTMLInputElement) {
            inlineMasterToggle.value = '0';
        }

        if (masterSearch instanceof HTMLInputElement) {
            masterSearch.value = optionLabel;
        }

        if (masterHiddenInput instanceof HTMLInputElement) {
            masterHiddenInput.value = String(data.id);
        }

        if (incomingNotes instanceof HTMLTextAreaElement) {
            incomingNotes.value = data.notes ?? '';
        }

        masterMenu.classList.add('hidden');
    });
}

for (const card of document.querySelectorAll('[data-notification-link]')) {
    card.addEventListener('click', (event) => {
        const target = event.target;

        if (target instanceof HTMLElement && target.closest('[data-notification-action]')) {
            return;
        }

        const href = card.getAttribute('data-notification-link');

        if (href) {
            window.location.href = href;
        }
    });
}
