// ============================================================
// Application theme: independent from payment and form workflows.
// ============================================================
const themeToggle = document.querySelector('[data-theme-toggle]');
const themeStorageKey = 'mawa-center-theme';
const themeColorMeta = document.querySelector('meta[name="theme-color"]');
const systemThemeQuery = window.matchMedia?.('(prefers-color-scheme: dark)');

const storedTheme = () => {
    try {
        const value = window.localStorage.getItem(themeStorageKey);
        return value === 'light' || value === 'dark' ? value : null;
    } catch {
        return null;
    }
};

const applyTheme = (theme) => {
    const isDark = theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
    themeToggle?.setAttribute('aria-label', isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
    themeToggle?.setAttribute('title', isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
    themeToggle?.setAttribute('aria-pressed', String(isDark));
    themeColorMeta?.setAttribute('content', isDark ? '#1c2536' : '#ffffff');
};

const initialTheme = storedTheme() ?? (systemThemeQuery?.matches ? 'dark' : 'light');
applyTheme(initialTheme);

themeToggle?.addEventListener('click', () => {
    const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);
    try {
        window.localStorage.setItem(themeStorageKey, nextTheme);
    } catch {
        // Theme remains active for this session when storage is unavailable.
    }
});

systemThemeQuery?.addEventListener('change', (event) => {
    if (!storedTheme()) applyTheme(event.matches ? 'dark' : 'light');
});

const sidebar = document.querySelector('[data-sidebar]');
const sidebarNav = document.querySelector('[data-sidebar-nav]');
const sidebarScrollStorageKey = 'mawa-center:sidebar-scroll-top';
const overlay = document.querySelector('[data-sidebar-overlay]');
const toast = document.querySelector('[data-toast]');
const passwordInput = document.querySelector('[data-password]');
const passwordToggle = document.querySelector('[data-password-toggle]');
const currencyInputs = Array.from(document.querySelectorAll('[data-currency-input]'));
const paymentUnitFilter = document.querySelector('[data-payment-unit-filter]');
const paymentClassFilter = document.querySelector('[data-payment-class-filter]');

const persistSidebarScroll = () => {
    if (!sidebarNav) return;

    try {
        window.sessionStorage.setItem(sidebarScrollStorageKey, String(sidebarNav.scrollTop));
    } catch {
        // Sidebar remains usable when session storage is unavailable.
    }
};

const restoreSidebarScroll = () => {
    if (!sidebarNav) return;

    let savedScrollTop = 0;
    try {
        const storedValue = Number.parseInt(window.sessionStorage.getItem(sidebarScrollStorageKey) ?? '', 10);
        savedScrollTop = Number.isFinite(storedValue) && storedValue >= 0 ? storedValue : 0;
    } catch {
        savedScrollTop = 0;
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            sidebarNav.scrollTop = savedScrollTop;
        });
    });
};

sidebarNav?.addEventListener('scroll', persistSidebarScroll, { passive: true });
window.addEventListener('pagehide', persistSidebarScroll);
restoreSidebarScroll();

paymentUnitFilter?.addEventListener('change', () => {
    if (!paymentClassFilter || paymentUnitFilter.value === paymentUnitFilter.dataset.selectedUnit) return;

    const selectedUnitId = paymentUnitFilter.value;
    const classPlaceholder = paymentClassFilter.querySelector('option[value=""]');
    paymentClassFilter.value = '';
    paymentClassFilter.disabled = selectedUnitId === '';
    if (classPlaceholder) {
        classPlaceholder.textContent = selectedUnitId ? 'Semua Kelas' : 'Pilih unit pendidikan terlebih dahulu';
    }
    paymentClassFilter.querySelectorAll('[data-payment-class-unit]').forEach((option) => {
        const belongsToSelectedUnit = option.dataset.paymentClassUnit === selectedUnitId;
        option.hidden = !belongsToSelectedUnit;
        option.disabled = !belongsToSelectedUnit;
    });
});

document.querySelectorAll('[data-payment-overview-per-page]').forEach((control) => {
    control.addEventListener('change', () => {
        const form = control.form;

        if (!form || form.dataset.paymentLoading === 'true') return;

        form.querySelector('input[name="page"]')?.remove();
        form.dataset.paymentLoading = 'true';
        form.setAttribute('aria-busy', 'true');
        form.submit();
    });
});

// ============================================================
// Login: Toggle mode Wali Santri
// ============================================================
const loginForm = document.querySelector('[data-login-form]');
const waliToggle = document.querySelector('[data-wali-toggle]');
const waliReset = document.querySelector('[data-wali-reset]');
const loginTypeInput = document.querySelector('[data-login-type-value]');
const loginUsernameLabel = document.querySelector('[data-login-username-label]');
const loginUsernameInput = document.querySelector('[data-login-username-input]');
const loginPasswordField = loginForm?.querySelector('.login-password-field input[type="password"]');
const loginGuardianUnit = document.querySelector('[data-guardian-unit]');

const setWaliMode = (active) => {
    if (!loginForm) return;
    loginForm.classList.toggle('wali-mode', active);
    if (loginTypeInput) loginTypeInput.value = active ? 'wali' : '';
    if (loginUsernameLabel) loginUsernameLabel.textContent = active ? 'NIS Santri' : 'Username';
    if (loginUsernameInput) {
        loginUsernameInput.placeholder = active ? 'Masukkan NIS santri' : 'Masukkan username';
        loginUsernameInput.focus();
    }
    if (loginPasswordField) {
        loginPasswordField.required = true;
    }
    if (loginGuardianUnit) {
        loginGuardianUnit.disabled = !active;
        loginGuardianUnit.required = active;
    }
};

waliToggle?.addEventListener('click', () => setWaliMode(true));
waliReset?.addEventListener('click', () => setWaliMode(false));
if (loginForm) {
    setWaliMode(loginForm.classList.contains('wali-mode'));
}
// ============================================================

const digitsOnly = (value) => String(value ?? '').replace(/\D/g, '');
const formatThousands = (value) => {
    const digits = digitsOnly(value).replace(/^0+(?=\d)/, '');
    return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
};
const wibTime = () => Object.fromEntries(
    new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(new Date()).filter((part) => part.type !== 'literal').map((part) => [part.type, part.value]),
);
const currentWibClock = () => {
    const { hour, minute, second } = wibTime();
    return {
        display: `${hour}.${minute}`,
        value: `${hour}:${minute}:${second}`,
    };
};
const formatIndonesianDate = (value) => {
    const [year, month, day] = String(value ?? '').slice(0, 10).split('-');
    return day && month && year ? `${day}/${month}/${year}` : '';
};
const formatDateInput = (value) => {
    if (/^\d{4}-\d{2}-\d{2}/.test(value)) return formatIndonesianDate(value);
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 8);
    return [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)].filter(Boolean).join('/');
};
const indonesianDateToIso = (value) => {
    const match = String(value ?? '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    return match ? `${match[3]}-${match[2]}-${match[1]}` : '';
};

passwordToggle?.addEventListener('click', () => {
    const revealing = passwordInput.type === 'password';
    passwordInput.type = revealing ? 'text' : 'password';
    passwordToggle.classList.toggle('is-visible', revealing);
    passwordToggle.setAttribute('aria-label', revealing ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    passwordToggle.setAttribute('title', revealing ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});

document.querySelectorAll('[data-indonesian-date]').forEach((input) => {
    input.value = formatDateInput(input.value);
    input.addEventListener('input', () => { input.value = formatDateInput(input.value); });
});
document.querySelectorAll('[data-date-picker-control]').forEach((control) => {
    const display = control.querySelector('[data-indonesian-date]');
    const picker = control.querySelector('[data-date-picker]');
    const button = control.querySelector('[data-date-picker-button]');
    picker.value = indonesianDateToIso(display.value);
    display.addEventListener('input', () => { picker.value = indonesianDateToIso(display.value); });
    picker.addEventListener('change', () => {
        if (picker.value) {
            display.value = formatIndonesianDate(picker.value);
            display.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    button.addEventListener('click', () => {
        if (typeof picker.showPicker === 'function') picker.showPicker();
        else picker.click();
    });
});

const formatCurrencyInput = (input) => {
    if (input.dataset.currencyDisabled === 'true') return;
    input.value = formatThousands(input.value);
};

currencyInputs.forEach((input) => {
    formatCurrencyInput(input);
    input.addEventListener('input', () => formatCurrencyInput(input));
});

document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => {
    form.querySelectorAll('[data-currency-input]').forEach((input) => {
        if (input.dataset.currencyDisabled !== 'true') input.value = digitsOnly(input.value);
    });
}));

document.querySelectorAll('[data-payment-one-stop-form]').forEach((form) => {
    const formatter = new Intl.NumberFormat('id-ID');
    const bills = Array.from(form.querySelectorAll('[data-payment-bill]'));
    const billAmountInputs = Array.from(form.querySelectorAll('[data-payment-bill-input]'));
    const hasCardAllocations = form.querySelector('[data-payment-bill-allocation]') !== null;
    const totalOutput = form.querySelector('[data-payment-total]');
    const paidInput = form.querySelector('[data-payment-paid-display]');
    const submitButton = form.querySelector('[data-payment-submit]');
    const method = form.querySelector('[data-payment-method]');
    const billChoice = form.querySelector('[data-payment-bill-choice]');
    const paymentType = form.querySelector('[data-payment-type]');
    const transferPanel = form.querySelector('[data-payment-transfer-panel]');
    const transferUpload = form.querySelector('[data-payment-transfer-upload]');
    const transferFile = form.querySelector('[data-payment-transfer-file]');
    const uploadName = form.querySelector('[data-payment-upload-name]');
    const paidTotalOutput = form.querySelector('[data-payment-paid-total]');
    const paidError = form.querySelector('[data-payment-paid-error]');
    const selectedSummaryList = form.querySelector('[data-payment-selected-list]');
    const selectedSummaryCount = form.querySelector('[data-payment-selected-count]');
    const selectedSummaryEmpty = form.querySelector('[data-payment-selected-empty]');
    const transferError = form.querySelector('[data-payment-transfer-error]');
    const paymentTypeOptions = Array.from(form.querySelectorAll('[data-payment-type-option]'));
    const methodOptions = Array.from(form.querySelectorAll('[data-payment-method-option]'));
    const submitLabel = submitButton?.querySelector('[data-payment-submit-label]');
    const defaultSubmitLabel = submitLabel?.textContent || 'Bayar & Cetak Struk';
    const isEditPayment = Boolean(form.dataset.paymentEditMode);
    const hasTransferProof = form.dataset.paymentHasTransferProof === 'true';
    const requiresTransferProof = form.dataset.paymentRequireTransferProof === 'true';
    let paidTouched = false;
    let validationAttempted = Boolean(paidError?.textContent.trim() || transferError?.textContent.trim());
    let isSubmitting = false;

    const selectedTotal = () => hasCardAllocations
        ? billAmountInputs.reduce((total, input) => total + Number(digitsOnly(input.value || '0')), 0)
        : bills
            .filter((bill) => bill.checked)
            .reduce((total, bill) => total + Number(bill.dataset.amount || 0), 0);

    const cardInputState = (input) => {
        const row = input.closest('[data-payment-bill-row]');
        const bill = row?.querySelector('[data-payment-bill]');
        const allocation = row?.querySelector('[data-payment-bill-allocation]');
        const fullButton = row?.querySelector('[data-payment-bill-full]');
        const periodSelect = row?.querySelector('[data-payment-bill-period-select]');
        const error = row?.querySelector('[data-payment-bill-error]');
        const amount = Number(digitsOnly(input.value || '0'));
        const balance = Number(input.dataset.paymentBillBalance || 0);
        const isEnabled = Boolean(bill?.checked);
        const message = amount > balance ? `Nominal tidak boleh melebihi sisa tagihan Rp ${formatter.format(balance)}.` : '';

        input.disabled = !isEnabled;
        row?.classList.toggle('is-allocated', isEnabled && amount > 0);
        if (fullButton) fullButton.disabled = !isEnabled;
        if (periodSelect) periodSelect.disabled = !isEnabled;
        if (allocation) {
            allocation.disabled = !isEnabled || amount < 1;
            allocation.value = String(amount);
        }
        input.setCustomValidity(message);
        showFieldError(error, message, validationAttempted);

        return { amount, isValid: isEnabled ? !message : true };
    };

    const syncCardAllocations = () => hasCardAllocations
        ? billAmountInputs.map(cardInputState)
        : [];

    const formatTotal = (total) => (total > 0 ? formatter.format(total) : '');

    const showFieldError = (target, message, shouldShow) => {
        if (!target) return;
        target.textContent = message;
        target.hidden = !message || !shouldShow;
    };

    const renderSelectedBillSummary = () => {
        if (!selectedSummaryList || !selectedSummaryCount) return;

        const selections = billAmountInputs.map((input) => {
            const row = input.closest('[data-payment-bill-row]');
            const amount = Number(digitsOnly(input.value || '0'));
            let periodOptions = [];

            try {
                periodOptions = JSON.parse(row?.dataset.paymentBillPeriodOptions || '[]');
            } catch {
                periodOptions = [];
            }

            const periodOption = periodOptions.find((option) => Number(option.amount || 0) === amount)
                || periodOptions.find((option) => amount > 0 && amount <= Number(option.amount || 0));

            return {
                amount,
                title: row?.dataset.paymentBillTitle || 'Tagihan',
                detail: periodOption?.detail || row?.dataset.paymentBillDetail || '',
            };
        }).filter((selection) => selection.amount > 0);

        selectedSummaryCount.textContent = `${selections.length} Tagihan`;
        selectedSummaryList.replaceChildren();

        if (selections.length === 0) {
            if (selectedSummaryEmpty) selectedSummaryList.append(selectedSummaryEmpty);
            return;
        }

        selections.forEach((selection) => {
            const item = document.createElement('div');
            const copy = document.createElement('span');
            const title = document.createElement('strong');
            const detail = document.createElement('span');
            const amount = document.createElement('strong');

            item.className = 'payment-prd-cashier-selected-item';
            copy.className = 'payment-prd-cashier-selected-copy';
            title.textContent = selection.title;
            detail.textContent = selection.detail;
            amount.textContent = `Rp. ${formatter.format(selection.amount)},-`;
            copy.append(title);
            if (selection.detail) copy.append(detail);
            item.append(copy, amount);
            selectedSummaryList.append(item);
        });
    };

    const transferFileError = () => {
        if (method?.value !== 'Transfer' || !requiresTransferProof) return '';

        const file = transferFile?.files?.[0];
        if (!file) {
            return isEditPayment && hasTransferProof
                ? ''
                : 'Bukti transfer wajib diunggah untuk metode pembayaran Transfer.';
        }
        if (!/\.(jpe?g|png|pdf)$/i.test(file.name)) {
            return 'Bukti transfer harus berupa JPG, JPEG, PNG, atau PDF.';
        }
        if (file.size > 2 * 1024 * 1024) {
            return 'Ukuran bukti transfer maksimal 2 MB.';
        }

        return '';
    };

    const renderPaymentState = (showErrors = validationAttempted) => {
        const cardStates = syncCardAllocations();
        const total = selectedTotal();
        if (hasCardAllocations && paidInput) paidInput.value = String(total);
        const paidAmount = hasCardAllocations ? total : Number(digitsOnly(paidInput?.value || '0'));
        const isFullPayment = paymentType?.value !== 'partial';
        let paidMessage = '';

        if (hasCardAllocations && paidAmount < 1) {
            paidMessage = 'Isi nominal setoran pada minimal satu tagihan.';
        } else if (!hasCardAllocations && !isFullPayment && paidAmount < 1) {
            paidMessage = 'Nominal dibayar harus lebih dari 0.';
        } else if (paidAmount > total && total > 0) {
            paidMessage = 'Nominal tidak boleh melebihi total pembayaran.';
        }

        const proofMessage = transferFileError();
        if (transferFile) transferFile.setCustomValidity(proofMessage);
        showFieldError(paidError, paidMessage, showErrors);
        showFieldError(transferError, proofMessage, showErrors);
        if (paidTotalOutput) paidTotalOutput.textContent = `${formatter.format(paidAmount)},-`;

        const isValid = total > 0
            && paidAmount > 0
            && paidAmount <= total
            && cardStates.every((state) => state.isValid)
            && proofMessage === '';
        if (submitButton) submitButton.disabled = isSubmitting || !isValid;

        return isValid;
    };

    const renderTotal = (syncPaid = false) => {
        const total = selectedTotal();
        if (totalOutput) totalOutput.textContent = `${formatter.format(total)},-`;
        if (paidInput) {
            const isFullPayment = paymentType?.value !== 'partial';
            if (hasCardAllocations) {
                paidInput.value = String(total);
            } else if (isFullPayment) {
                paidInput.value = formatTotal(total);
                paidTouched = false;
            } else if (syncPaid && (!paidTouched || Number(digitsOnly(paidInput.value)) > total)) {
                paidInput.value = formatTotal(total);
                paidTouched = false;
            }
        }
        renderSelectedBillSummary();
        renderPaymentState();
    };

    const renderMandatoryDisplayTotal = () => {
        const mandatoryTotal = form.querySelector('[data-payment-mandatory-total]');
        if (!mandatoryTotal) return;

        const total = Array.from(form.querySelectorAll('[data-payment-display-row]'))
            .reduce((sum, row) => {
                const bill = row.querySelector('[data-payment-bill]');
                return sum + (bill?.checked ? Number(row.dataset.amount || bill.dataset.amount || 0) : 0);
            }, 0);
        mandatoryTotal.textContent = `${formatter.format(total)},-`;
    };

    const syncBillChoice = () => {
        const selectedKey = billChoice?.value || 'all';

        form.querySelectorAll('[data-payment-summary-bill-key]').forEach((row) => {
            const bill = row.querySelector('[data-payment-bill]');
            const isSelected = selectedKey === 'all' || row.dataset.paymentSummaryBillKey === selectedKey;
            if (bill) bill.checked = isSelected;
            row.hidden = !isSelected;
        });

        renderTotal(true);
    };

    const renderTransferFields = () => {
        const isTransfer = method?.value === 'Transfer';
        if (transferPanel) transferPanel.hidden = !isTransfer;
        if (transferUpload) transferUpload.hidden = !isTransfer;
        if (transferFile) {
            transferFile.required = isTransfer && (!isEditPayment || !hasTransferProof);
            if (!isTransfer) transferFile.setCustomValidity('');
        }
    };

    bills.forEach((bill) => {
        bill.addEventListener('change', () => {
            const row = bill.closest('[data-payment-bill-row]');
            const input = row?.querySelector('[data-payment-bill-input]');
            if (input && !bill.checked) input.value = '';
            renderMandatoryDisplayTotal();
            renderTotal(true);
        });
    });

    billAmountInputs.forEach((input) => {
        input.addEventListener('input', () => {
            const row = input.closest('[data-payment-bill-row]');
            const bill = row?.querySelector('[data-payment-bill]');
            if (Number(digitsOnly(input.value || '0')) > 0 && bill) bill.checked = true;
            validationAttempted = true;
            renderTotal(true);
        });
    });

    form.querySelectorAll('[data-payment-bill-full]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-payment-bill-row]');
            const bill = row?.querySelector('[data-payment-bill]');
            const input = row?.querySelector('[data-payment-bill-input]');
            if (!bill || !input || button.disabled) return;

            bill.checked = true;
            input.value = formatter.format(Number(input.dataset.paymentBillBalance || 0));
            validationAttempted = true;
            renderTotal(true);
        });
    });

    form.querySelectorAll('[data-payment-bill-period-select]').forEach((select) => {
        select.addEventListener('change', () => {
            const row = select.closest('[data-payment-bill-row]');
            const option = select.selectedOptions[0];
            const bill = row?.querySelector('[data-payment-bill]');
            const periodCount = row?.querySelector('[data-payment-bill-period-count]');
            const input = row?.querySelector('[data-payment-bill-input]');
            const amountOutput = row?.querySelector('[data-payment-bill-amount]');
            const detailOutput = row?.querySelector('[data-payment-bill-detail]');
            const amount = Number(option?.dataset.amount || 0);
            const detail = option?.dataset.detail || '';

            if (!row || !bill || !input || !option || amount < 1) return;

            bill.checked = true;
            bill.dataset.amount = String(amount);
            row.dataset.amount = String(amount);
            row.dataset.paymentBillDetail = detail;
            input.dataset.paymentBillBalance = String(amount);
            input.value = formatter.format(amount);
            if (periodCount) periodCount.value = select.value;
            if (amountOutput) amountOutput.textContent = formatter.format(amount);
            if (detailOutput) detailOutput.textContent = detail;
            validationAttempted = true;
            renderMandatoryDisplayTotal();
            renderTotal(true);
        });
    });

    billChoice?.addEventListener('change', syncBillChoice);

    paymentType?.addEventListener('change', () => renderTotal(true));

    paymentTypeOptions.forEach((option) => {
        option.addEventListener('change', () => {
            if (!option.checked || !paymentType) return;
            paymentType.value = option.value;
            paidTouched = false;
            validationAttempted = method?.value === 'Transfer';
            renderTotal(true);
        });
    });

    methodOptions.forEach((option) => {
        option.addEventListener('change', () => {
            if (!option.checked || !method) return;
            method.value = option.value;
            validationAttempted = option.value === 'Transfer';
            renderTransferFields();
            renderPaymentState();
        });
    });

    form.addEventListener('submit', (event) => {
        validationAttempted = true;
        if (isSubmitting || !renderPaymentState(true)) {
            event.preventDefault();
            if (paidInput) formatCurrencyInput(paidInput);
            return;
        }

        isSubmitting = true;
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
        }
        if (submitLabel) submitLabel.textContent = isEditPayment ? 'Menyimpan Perubahan...' : 'Memproses Pembayaran...';
    });

    paidInput?.addEventListener('input', () => {
        paidTouched = true;
        validationAttempted = true;
        renderPaymentState(true);
    });

    method?.addEventListener('change', () => {
        renderTransferFields();
        renderPaymentState();
    });

    transferFile?.addEventListener('change', () => {
        validationAttempted = true;
        if (uploadName) uploadName.textContent = transferFile.files?.[0]?.name || 'Pilih file bukti transfer';
        renderPaymentState(true);
    });

    transferFile?.addEventListener('invalid', () => {
        validationAttempted = true;
        renderPaymentState(true);
    });

    form.querySelector('[data-payment-copy-account]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const number = button.dataset.accountNumber || '';
        if (!number) return;
        try {
            await navigator.clipboard.writeText(number);
            const label = button.querySelector('span');
            const original = label?.textContent || 'Salin Rekening';
            if (label) label.textContent = 'Tersalin';
            window.setTimeout(() => {
                if (label) label.textContent = original;
            }, 1400);
        } catch {
            window.prompt('Salin nomor rekening:', number);
        }
    });

    renderTransferFields();
    if (billChoice) syncBillChoice();
    renderMandatoryDisplayTotal();
    renderTotal(false);
    if (submitLabel) submitLabel.textContent = defaultSubmitLabel;
});

document.querySelectorAll('[data-auto-receipts]').forEach((launcher) => {
    const source = launcher.querySelector('[data-receipt-urls]');
    const downloadSource = launcher.querySelector('[data-receipt-download-urls]');
    const fallbackModal = launcher.querySelector('[data-auto-receipt-modal]');
    let urls = [];
    let downloadUrls = [];
    try {
        urls = JSON.parse(source?.textContent || '[]');
    } catch {
        urls = [];
    }
    try {
        downloadUrls = JSON.parse(downloadSource?.textContent || '[]');
    } catch {
        downloadUrls = [];
    }
    const showFallback = () => {
        if (!fallbackModal) return;
        fallbackModal.hidden = false;
        fallbackModal.classList.add('show');
        document.body.style.overflow = 'hidden';
        fallbackModal.querySelector('[data-open-receipts]')?.focus();
    };
    const hideFallback = () => {
        if (!fallbackModal) return;
        fallbackModal.classList.remove('show');
        fallbackModal.hidden = true;
        document.body.style.overflow = '';
    };
    const openReceipts = () => {
        let blocked = false;
        urls.forEach((url) => {
            const opened = window.open(url, '_blank', 'noopener');
            if (!opened) blocked = true;
        });
        if (blocked) {
            showFallback();
        }
    };
    const downloadReceipts = () => {
        downloadUrls.forEach((url) => {
            window.open(url, '_blank', 'noopener');
        });
    };

    launcher.querySelector('[data-open-receipts]')?.addEventListener('click', openReceipts);
    launcher.querySelector('[data-download-receipts]')?.addEventListener('click', downloadReceipts);
    launcher.querySelectorAll('[data-payment-success-close]').forEach((button) => button.addEventListener('click', hideFallback));
    fallbackModal?.addEventListener('click', (event) => {
        if (event.target === fallbackModal) hideFallback();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && fallbackModal?.classList.contains('show')) hideFallback();
    });
    if (urls.length) openReceipts();
});

document.querySelectorAll('[data-payment-context-switch]').forEach((control) => {
    control.addEventListener('change', () => {
        const form = control.form;
        const isSegmentedOption = control.matches('input[type="radio"]');

        if ((isSegmentedOption && !control.checked) || !form || form.dataset.paymentLoading === 'true') return;

        form.dataset.paymentLoading = 'true';
        form.classList.add('is-loading');
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('[data-payment-context-switch]').forEach((option) => {
            if (!option.matches('input[type="radio"]')) return;

            option.closest('.payment-context-option')?.classList.toggle('is-active', option === control);
            if (option !== control) option.disabled = true;
        });
        form.submit();
    });
});

document.querySelectorAll('[data-payment-history-delete-modal]').forEach((modal) => {
    let pendingForm = null;
    const name = modal.querySelector('[data-payment-delete-name]');
    const meta = modal.querySelector('[data-payment-delete-meta]');
    const cancel = modal.querySelector('[data-payment-delete-cancel]');
    const confirm = modal.querySelector('[data-payment-delete-confirm]');

    const close = () => {
        modal.classList.remove('show');
        modal.hidden = true;
        pendingForm = null;
        document.body.style.overflow = '';
        if (confirm) {
            confirm.disabled = false;
            confirm.removeAttribute('aria-busy');
            confirm.textContent = 'Ya, Hapus';
        }
    };

    document.querySelectorAll('[data-payment-history-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.paymentDeleteConfirmed === 'true') return;
            event.preventDefault();
            pendingForm = form;
            const detail = form.dataset.paymentDeleteDetail || '';
            const amount = form.dataset.paymentDeleteAmount || '';
            if (name) name.textContent = form.dataset.paymentDeleteTitle || 'transaksi ini';
            if (meta) meta.textContent = [detail, amount].filter(Boolean).join(' · ');
            modal.hidden = false;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            confirm?.focus();
        });
    });

    cancel?.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
    confirm?.addEventListener('click', () => {
        if (!pendingForm || pendingForm.dataset.paymentDeleteConfirmed === 'true') return;
        pendingForm.dataset.paymentDeleteConfirmed = 'true';
        confirm.disabled = true;
        confirm.setAttribute('aria-busy', 'true');
        confirm.textContent = 'Menghapus...';
        pendingForm.submit();
    });
});

document.querySelector('[data-spp-import-file]')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    const label = document.querySelector('[data-spp-import-filename]');
    if (label) label.textContent = file?.name || 'Ketuk untuk pilih berkas';
    event.target.closest('.spp-import-dropzone')?.classList.toggle('has-file', Boolean(file));
    event.target.closest('[data-student-import-upload]')?.classList.toggle('has-file', Boolean(file));
});

const paymentImport = document.querySelector('[data-payment-import]');
if (paymentImport) {
    const category = paymentImport.querySelector('[data-payment-import-category]');
    const fileInput = paymentImport.querySelector('[data-payment-import-file]');
    const dropzone = paymentImport.querySelector('[data-payment-import-dropzone]');
    const fileEmpty = paymentImport.querySelector('[data-payment-import-file-empty]');
    const fileSelected = paymentImport.querySelector('[data-payment-import-file-selected]');
    const fileName = paymentImport.querySelector('[data-payment-import-filename]');
    const fileSize = paymentImport.querySelector('[data-payment-import-filesize]');
    const changeFileButton = paymentImport.querySelector('[data-payment-import-file-change]');
    const removeFileButton = paymentImport.querySelector('[data-payment-import-file-remove]');
    const submitButton = paymentImport.querySelector('[data-payment-import-submit]');
    const submitLabel = paymentImport.querySelector('[data-payment-import-submit-label]');
    const sppContext = paymentImport.querySelector('[data-payment-import-spp-context]');
    const sppFields = Array.from(paymentImport.querySelectorAll('[data-payment-import-spp-field]'));

    const formatFileSize = (bytes) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const renderFileState = () => {
        const file = fileInput?.files?.[0];
        const hasFile = Boolean(file);

        if (fileEmpty) fileEmpty.hidden = hasFile;
        if (fileSelected) fileSelected.hidden = !hasFile;
        if (fileName) fileName.textContent = file?.name || '';
        if (fileSize) fileSize.textContent = file ? `${formatFileSize(file.size)} · Format XLSX` : '';
        if (changeFileButton) changeFileButton.hidden = !hasFile;
        if (removeFileButton) removeFileButton.hidden = !hasFile;
        dropzone?.classList.toggle('has-file', hasFile);
        dropzone?.setAttribute('aria-busy', 'false');
    };

    const syncPaymentImport = () => {
        const action = category?.selectedOptions?.[0]?.dataset.action;
        if (action) paymentImport.action = action;

        const isSpp = category?.value === 'spp';
        if (sppContext) sppContext.hidden = !isSpp;
        sppFields.forEach((field) => {
            field.disabled = !isSpp;
            field.required = isSpp;
        });

        const hasFile = Boolean(fileInput?.files?.length);
        const hasSppContext = !isSpp || sppFields.every((field) => field.value);
        if (submitButton) submitButton.disabled = !hasFile || !hasSppContext;
        renderFileState();
    };

    category?.addEventListener('change', syncPaymentImport);
    fileInput?.addEventListener('change', syncPaymentImport);
    sppFields.forEach((field) => field.addEventListener('change', syncPaymentImport));
    changeFileButton?.addEventListener('click', () => fileInput?.click());
    removeFileButton?.addEventListener('click', () => {
        if (fileInput) fileInput.value = '';
        syncPaymentImport();
        fileInput?.focus();
    });
    dropzone?.addEventListener('click', (event) => {
        if (event.target.closest('button, label, input')) return;
        fileInput?.click();
    });
    dropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    });
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');

        const droppedFile = event.dataTransfer?.files?.[0];
        if (!droppedFile || !fileInput) return;

        try {
            const transfer = new DataTransfer();
            transfer.items.add(droppedFile);
            fileInput.files = transfer.files;
        } catch {
            return;
        }

        syncPaymentImport();
    });
    syncPaymentImport();

    paymentImport.addEventListener('submit', () => {
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
        }
        if (submitLabel) submitLabel.textContent = 'Memproses...';
    });
}

const paymentImportPreview = document.querySelector('[data-import-preview]');
if (paymentImportPreview) {
    const rows = Array.from(paymentImportPreview.querySelectorAll('[data-import-preview-row]'));
    const errorGroups = Array.from(paymentImportPreview.querySelectorAll('[data-import-preview-group]'));
    const searchInput = paymentImportPreview.querySelector('[data-import-preview-search]');
    const rangeLabel = paymentImportPreview.querySelector('[data-import-preview-range]');
    const pagesContainer = paymentImportPreview.querySelector('[data-import-preview-pages]');
    const submitForm = paymentImportPreview.querySelector('[data-import-preview-submit-form]');
    const submitButton = paymentImportPreview.querySelector('[data-import-preview-submit]');
    const submitLabel = paymentImportPreview.querySelector('[data-import-preview-submit-label]');
    const pageSize = Number(paymentImportPreview.dataset.importPreviewPageSize || 25);
    let activeReason = '';
    let searchQuery = '';
    let currentPage = 1;

    const renderPagination = (total, pageCount) => {
        if (!pagesContainer) return;
        pagesContainer.replaceChildren();

        if (pageCount <= 1) return;

        const appendPageButton = (label, page, disabled = false, current = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'app-page-button payment-import-preview-page-button';
            button.textContent = label;
            button.disabled = disabled;
            if (current) button.classList.add('is-current');
            if (current) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => {
                currentPage = page;
                renderPreview();
            });
            pagesContainer.append(button);
        };

        appendPageButton('<', Math.max(1, currentPage - 1), currentPage === 1);
        for (let page = 1; page <= pageCount; page += 1) {
            appendPageButton(String(page), page, false, page === currentPage);
        }
        appendPageButton('>', Math.min(pageCount, currentPage + 1), currentPage === pageCount);
    };

    const renderPreview = () => {
        const filteredRows = rows.filter((row) => {
            const matchesReason = !activeReason || row.dataset.reason === activeReason;
            const matchesSearch = !searchQuery || (row.dataset.search || '').toLowerCase().includes(searchQuery);

            return matchesReason && matchesSearch;
        });
        const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        const pageStart = (currentPage - 1) * pageSize;
        const visibleRows = new Set(filteredRows.slice(pageStart, pageStart + pageSize));

        rows.forEach((row) => {
            const isVisible = visibleRows.has(row);
            const detailRow = row.nextElementSibling;
            const detailToggle = row.querySelector('[data-import-preview-detail-toggle]');

            row.hidden = !isVisible;
            if (!isVisible && detailRow?.matches('[data-import-preview-detail-row]')) {
                detailRow.hidden = true;
                detailToggle?.setAttribute('aria-expanded', 'false');
                row.classList.remove('is-expanded');
            }
        });

        if (rangeLabel) {
            rangeLabel.textContent = filteredRows.length === 0
                ? 'Tidak ada data gagal yang cocok'
                : `Menampilkan ${pageStart + 1}-${Math.min(pageStart + pageSize, filteredRows.length)} dari ${filteredRows.length} data gagal`;
        }
        renderPagination(filteredRows.length, pageCount);
    };

    errorGroups.forEach((group) => group.addEventListener('click', () => {
        const nextReason = group.dataset.importPreviewGroup || '';
        activeReason = activeReason === nextReason ? '' : nextReason;
        currentPage = 1;
        errorGroups.forEach((item) => {
            const isActive = item === group && activeReason !== '';
            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-pressed', String(isActive));
        });
        renderPreview();
    }));

    rows.forEach((row) => {
        const detailToggle = row.querySelector('[data-import-preview-detail-toggle]');
        const detailRow = row.nextElementSibling;

        if (!detailToggle || !detailRow?.matches('[data-import-preview-detail-row]')) return;

        detailToggle.addEventListener('click', () => {
            const isExpanded = detailToggle.getAttribute('aria-expanded') === 'true';
            detailToggle.setAttribute('aria-expanded', String(!isExpanded));
            detailRow.hidden = isExpanded;
            row.classList.toggle('is-expanded', !isExpanded);
        });
    });

    searchInput?.addEventListener('input', () => {
        searchQuery = searchInput.value.trim().toLowerCase();
        currentPage = 1;
        renderPreview();
    });
    submitForm?.addEventListener('submit', () => {
        if (!submitButton) return;

        submitButton.disabled = true;
        submitButton.classList.add('is-loading');
        if (submitLabel) submitLabel.textContent = `Mengimpor ${submitButton.dataset.importCount || ''} Transaksi...`;
    });
    renderPreview();
}

const sppImportToggle = document.querySelector('[data-spp-import-toggle]');
const sppImportPanel = document.querySelector('[data-spp-import-panel]');
const setSppImportPanel = (open) => {
    if (!sppImportPanel) return;
    sppImportPanel.hidden = !open;
    sppImportPanel.classList.toggle('show', open);
    sppImportToggle?.classList.toggle('active', open);
    sppImportToggle?.setAttribute('aria-expanded', String(open));
    const isModal = sppImportPanel.classList.contains('spp-import-modal-backdrop');
    document.body.style.overflow = open && isModal ? 'hidden' : '';
    if (open && !isModal) sppImportPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};
sppImportToggle?.addEventListener('click', () => {
    setSppImportPanel(sppImportPanel?.hidden ?? true);
});
document.querySelectorAll('[data-spp-import-close]').forEach((button) => button.addEventListener('click', () => setSppImportPanel(false)));
sppImportPanel?.addEventListener('click', (event) => {
    if (event.target === sppImportPanel && sppImportPanel.classList.contains('spp-import-modal-backdrop')) setSppImportPanel(false);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sppImportPanel && !sppImportPanel.hidden) setSppImportPanel(false);
});
if (sppImportPanel && !sppImportPanel.hidden && sppImportPanel.classList.contains('spp-import-modal-backdrop')) document.body.style.overflow = 'hidden';

const setSidebar = (open) => {
    sidebar?.classList.toggle('open', open);
    overlay?.classList.toggle('show', open);
    document.body.style.overflow = open ? 'hidden' : '';
};

const toggleSidebar = () => {
    if (!sidebar) return;
    if (window.matchMedia('(max-width: 850px)').matches) {
        setSidebar(!sidebar.classList.contains('open'));
        return;
    }
    sidebar.classList.toggle('collapsed');
};

document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', toggleSidebar);
document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => setSidebar(true));
document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => setSidebar(false));
overlay?.addEventListener('click', () => setSidebar(false));

document.querySelectorAll('[data-alert-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('[data-alert]')?.remove());
});

document.querySelectorAll('[data-alert]').forEach((alert) => {
    alert.setAttribute('role', 'dialog');
    alert.setAttribute('aria-modal', 'true');
    if (!alert.hasAttribute('aria-label') && !alert.hasAttribute('aria-labelledby')) {
        alert.setAttribute('aria-label', 'Notifikasi aplikasi');
    }
    alert.addEventListener('click', (event) => {
        if (event.target === alert) alert.remove();
    });
});

const appModalSelector = '.modal-backdrop, .result-modal-backdrop, .spp-import-modal-backdrop';
const appModalCloseSelector = '[data-alert-close], [data-app-confirm-cancel], [data-payment-success-close], [data-payment-delete-cancel], [data-modal-close], [data-spp-crud-close], [data-report-payment-close], [data-other-crud-close], [data-spp-import-close]';
const appModalFocusableSelector = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]), [contenteditable="true"]';
const appModalFocusOrigins = new WeakMap();
const visibleAppModals = () => Array.from(document.querySelectorAll(appModalSelector)).filter((modal) => !modal.hidden && modal.classList.contains('show'));
const appModalFocusableElements = (modal) => Array.from(modal.querySelectorAll(appModalFocusableSelector)).filter((element) => element.getClientRects().length > 0);
const rememberAppModalFocus = (modal) => {
    const activeElement = document.activeElement;
    if (!appModalFocusOrigins.has(modal) && activeElement instanceof HTMLElement && activeElement !== document.body && !modal.contains(activeElement)) {
        appModalFocusOrigins.set(modal, activeElement);
    }
};
const restoreAppModalFocus = (modal) => {
    const focusOrigin = appModalFocusOrigins.get(modal);
    appModalFocusOrigins.delete(modal);
    if (focusOrigin instanceof HTMLElement && focusOrigin.isConnected) focusOrigin.focus({ preventScroll: true });
};
const focusAppModal = (modal) => {
    rememberAppModalFocus(modal);
    const preferredControl = modal.querySelector('[data-payment-delete-confirm], [data-alert-close], [data-payment-success-close], [data-modal-close], [data-spp-crud-close], [data-report-payment-close], [data-other-crud-close], [data-spp-import-close], button, a[href], input, select, textarea');
    if (preferredControl) {
        preferredControl.focus();
        return;
    }

    modal.setAttribute('tabindex', '-1');
    modal.focus();
};

document.querySelectorAll(appModalSelector).forEach((modal) => {
    if (!modal.hasAttribute('role')) modal.setAttribute('role', 'dialog');
    if (!modal.hasAttribute('aria-modal')) modal.setAttribute('aria-modal', 'true');
});

const appModalObserver = new MutationObserver((mutations) => {
    mutations.forEach(({ target }) => {
        if (target.classList.contains('show') && !target.hidden) {
            focusAppModal(target);
            return;
        }

        restoreAppModalFocus(target);
    });
});

document.querySelectorAll(appModalSelector).forEach((modal) => appModalObserver.observe(modal, { attributes: true, attributeFilter: ['class', 'hidden'] }));
visibleAppModals().forEach(focusAppModal);

document.addEventListener('keydown', (event) => {
    const openModals = visibleAppModals();
    const modal = openModals[openModals.length - 1];
    if (!modal) return;

    if (event.key === 'Tab') {
        const focusableElements = appModalFocusableElements(modal);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (!firstElement || !lastElement) {
            event.preventDefault();
            modal.focus();
            return;
        }

        if (event.shiftKey && (document.activeElement === firstElement || !modal.contains(document.activeElement))) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && (document.activeElement === lastElement || !modal.contains(document.activeElement))) {
            event.preventDefault();
            firstElement.focus();
        }
        return;
    }

    if (event.key !== 'Escape') return;

    event.preventDefault();
    const closeButton = modal.querySelector(appModalCloseSelector);
    if (closeButton) {
        closeButton.click();
    } else if (modal.matches('[data-alert]')) {
        modal.remove();
    } else {
        modal.classList.remove('show');
    }
});

const showAppAlert = (message, title = 'Perlu Diperiksa') => {
    const focusOrigin = document.activeElement;
    const backdrop = document.createElement('div');
    backdrop.className = 'result-modal-backdrop show';
    backdrop.dataset.alert = '';
    backdrop.setAttribute('role', 'dialog');
    backdrop.setAttribute('aria-modal', 'true');
    backdrop.setAttribute('aria-label', title);

    const panel = document.createElement('div');
    panel.className = 'result-modal error-result';
    panel.setAttribute('role', 'document');

    const icon = document.createElement('span');
    icon.className = 'result-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '!';

    const heading = document.createElement('strong');
    heading.textContent = title;
    const description = document.createElement('p');
    description.textContent = message;
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'button button-primary';
    closeButton.dataset.alertClose = '';
    closeButton.textContent = 'Tutup';
    const close = () => {
        backdrop.remove();
        if (focusOrigin instanceof HTMLElement && focusOrigin.isConnected) focusOrigin.focus({ preventScroll: true });
    };
    closeButton.addEventListener('click', close);
    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) close();
    });

    panel.append(icon, heading, description, closeButton);
    backdrop.append(panel);
    document.body.append(backdrop);
    closeButton.focus();
};

const confirmAppAction = ({ title, message, confirmLabel }) => new Promise((resolve) => {
    const focusOrigin = document.activeElement;
    const backdrop = document.createElement('div');
    backdrop.className = 'result-modal-backdrop show';
    backdrop.setAttribute('role', 'dialog');
    backdrop.setAttribute('aria-modal', 'true');
    backdrop.setAttribute('aria-label', title);

    const panel = document.createElement('div');
    panel.className = 'result-modal confirmation-result';
    panel.setAttribute('role', 'document');

    const icon = document.createElement('span');
    icon.className = 'result-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '!';

    const heading = document.createElement('strong');
    heading.textContent = title;
    const description = document.createElement('p');
    description.textContent = message;
    const actions = document.createElement('div');
    actions.className = 'payment-result-actions';
    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'button button-secondary';
    cancelButton.dataset.appConfirmCancel = '';
    cancelButton.textContent = 'Batal';
    const confirmButton = document.createElement('button');
    confirmButton.type = 'button';
    confirmButton.className = 'button button-danger';
    confirmButton.textContent = confirmLabel;

    const close = (confirmed) => {
        backdrop.remove();
        if (focusOrigin instanceof HTMLElement && focusOrigin.isConnected) focusOrigin.focus({ preventScroll: true });
        resolve(confirmed);
    };

    cancelButton.addEventListener('click', () => close(false));
    confirmButton.addEventListener('click', () => close(true));
    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) close(false);
    });

    actions.append(cancelButton, confirmButton);
    panel.append(icon, heading, description, actions);
    backdrop.append(panel);
    document.body.append(backdrop);
    confirmButton.focus();
});

document.querySelectorAll('[data-master-delete-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') return;

        event.preventDefault();
        const confirmed = await confirmAppAction({
            title: 'Hapus data?',
            message: 'Data yang dihapus tidak dapat dikembalikan.',
            confirmLabel: 'Hapus',
        });

        if (!confirmed) return;

        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });
});

document.querySelectorAll('[data-account-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-account-menu-toggle]');
    const popup = menu.querySelector('[data-account-menu-popup]');
    const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));

    if (!toggle || !popup) return;

    const closeMenu = ({ returnFocus = false } = {}) => {
        if (popup.hidden) return;

        popup.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) toggle.focus();
    };

    const openMenu = () => {
        popup.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        items[0]?.focus();
    };

    toggle.addEventListener('click', () => {
        if (popup.hidden) {
            openMenu();
            return;
        }

        closeMenu();
    });

    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu({ returnFocus: true });
    });

    document.addEventListener('pointerdown', (event) => {
        if (!menu.contains(event.target)) closeMenu();
    });

    items.forEach((item) => item.addEventListener('click', () => closeMenu()));
});

document.querySelectorAll('[data-identity-split-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') return;

        event.preventDefault();
        const confirmed = await confirmAppAction({
            title: 'Pisahkan identitas?',
            message: 'Data siswa akan kembali dipisahkan berdasarkan unit atau pendaftaran asalnya.',
            confirmLabel: 'Pisahkan',
        });

        if (!confirmed) return;

        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });
});

const periodValues = {
    Mingguan: ['Rp 112,8 jt', [56, 72, 64, 83, 76, 91, 86, 62, 78, 88, 74, 94]],
    Bulanan: ['Rp 482,6 jt', [42, 54, 48, 69, 63, 82, 72, 89, 76, 92, 84, 96]],
    Tahunan: ['Rp 3,8 M', [68, 74, 70, 78, 81, 76, 84, 88, 86, 92, 89, 95]],
};

document.querySelectorAll('[data-period]').forEach((button) => {
    button.addEventListener('click', () => {
        const [total, heights] = periodValues[button.dataset.period];
        document.querySelectorAll('[data-period]').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        document.querySelector('[data-chart-total]').textContent = total;
        document.querySelectorAll('[data-chart] .bar-track span').forEach((bar, index) => {
            bar.style.height = `${heights[index]}%`;
            bar.style.animation = 'none';
            requestAnimationFrame(() => { bar.style.animation = ''; });
        });
        toast.classList.add('show');
        window.clearTimeout(window.dashboardToastTimer);
        window.dashboardToastTimer = window.setTimeout(() => toast.classList.remove('show'), 1800);
    });
});

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        document.querySelector('.search-box input')?.focus();
    }
    if (event.key === 'Escape') setSidebar(false);
});


const pageHas = (selector) => document.querySelector(selector) !== null;

if (pageHas('[data-modal], [data-student-picker], [data-guardian-student-picker], [data-student-filter-unit], [data-report-payment-type]')) {
    import('./modules/master.js').then(({ initMaster }) => initMaster({ digitsOnly, formatCurrencyInput, formatThousands }));
}
if (pageHas('[data-spp-form], [data-laundry-form], [data-other-form], [data-spp-detail-modal], [data-other-edit-modal]')) {
    import('./modules/finance.js').then(({ initFinance }) => initFinance({ digitsOnly, currentWibClock, formatCurrencyInput, formatThousands, showAppAlert }));
}
if (pageHas('[data-report-correction-modal], [data-report-cancel-modal]')) {
    import('./modules/reports.js').then(({ initReports }) => initReports({ formatCurrencyInput, showAppAlert }));
}
if (pageHas('[data-student-import-toolbar], [data-student-import-confirm], [data-class-movement-form]')) {
    import('./modules/students.js').then(({ initStudents }) => initStudents({ confirmAppAction }));
}
if (pageHas('[data-bill-builder], [data-bill-sync-progress]')) {
    import('./modules/bills.js').then(({ initBills }) => initBills());
}
