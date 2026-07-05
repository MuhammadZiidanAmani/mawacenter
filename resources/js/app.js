const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const toast = document.querySelector('[data-toast]');
const passwordInput = document.querySelector('[data-password]');
const passwordToggle = document.querySelector('[data-password-toggle]');
const currencyInputs = Array.from(document.querySelectorAll('[data-currency-input]'));
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
    passwordToggle.classList.toggle('active', revealing);
    passwordToggle.setAttribute('aria-label', revealing ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    passwordToggle.setAttribute('title', revealing ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});

document.querySelectorAll('.logout-button').forEach((button) => {
    button.addEventListener('click', () => { window.location.href = '/logout'; });
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
    const totalOutput = form.querySelector('[data-payment-total]');
    const paidInput = form.querySelector('[data-payment-paid-display]');
    const submitButton = form.querySelector('[data-payment-submit]');
    const method = form.querySelector('[data-payment-method]');
    const transferPanel = form.querySelector('[data-payment-transfer-panel]');
    const transferUpload = form.querySelector('[data-payment-transfer-upload]');
    const transferFile = form.querySelector('[data-payment-transfer-file]');
    const uploadName = form.querySelector('[data-payment-upload-name]');
    let paidTouched = false;

    const selectedTotal = () => bills
        .filter((bill) => bill.checked)
        .reduce((total, bill) => total + Number(bill.dataset.amount || 0), 0);

    const renderTotal = (syncPaid = false) => {
        const total = selectedTotal();
        if (totalOutput) totalOutput.textContent = `${formatter.format(total)},-`;
        if (submitButton) submitButton.disabled = total < 1;
        if (paidInput && syncPaid && (!paidTouched || Number(digitsOnly(paidInput.value)) > total)) {
            paidInput.value = total > 0 ? formatter.format(total) : '';
            paidTouched = false;
        }
    };

    const renderTransferFields = () => {
        const isTransfer = method?.value === 'Transfer';
        if (transferPanel) transferPanel.hidden = !isTransfer;
        if (transferUpload) transferUpload.hidden = !isTransfer;
        if (transferFile) {
            transferFile.required = isTransfer;
            if (!isTransfer) transferFile.setCustomValidity('');
        }
    };

    bills.forEach((bill) => {
        bill.addEventListener('change', () => renderTotal(true));
    });

    form.querySelectorAll('[data-payment-period-select]').forEach((select) => {
        select.addEventListener('change', () => {
            const row = select.closest('[data-payment-bill-row]');
            const bill = row?.querySelector('[data-payment-bill]');
            const detail = row?.querySelector('[data-payment-bill-detail]');
            const amount = row?.querySelector('[data-payment-bill-amount]');
            const option = select.selectedOptions?.[0];
            if (!row || !bill || !option) return;

            bill.dataset.amount = option.dataset.amount || '0';
            bill.checked = true;
            if (detail) detail.textContent = option.dataset.detail || '';
            if (amount) amount.textContent = `${formatter.format(Number(option.dataset.amount || 0))},-`;
            renderTotal(true);
        });
    });

    paidInput?.addEventListener('input', () => {
        paidTouched = true;
    });

    method?.addEventListener('change', renderTransferFields);

    transferFile?.addEventListener('change', () => {
        transferFile.setCustomValidity('');
        if (uploadName) uploadName.textContent = transferFile.files?.[0]?.name || 'Pilih file bukti transfer';
    });

    transferFile?.addEventListener('invalid', () => {
        if (method?.value === 'Transfer' && !transferFile.files?.length) {
            transferFile.setCustomValidity('Bukti transfer wajib diunggah untuk metode pembayaran Transfer.');
        }
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
    renderTotal(false);
});

document.querySelectorAll('[data-payment-history-period]').forEach((input) => {
    input.addEventListener('change', () => {
        if (input.value) input.form?.requestSubmit();
    });
});

document.querySelectorAll('[data-auto-receipts]').forEach((launcher) => {
    const source = launcher.querySelector('[data-receipt-urls]');
    let urls = [];
    try {
        urls = JSON.parse(source?.textContent || '[]');
    } catch {
        urls = [];
    }
    const openReceipts = () => {
        urls.forEach((url) => {
            window.open(url, '_blank', 'noopener');
        });
    };

    launcher.querySelector('[data-open-receipts]')?.addEventListener('click', openReceipts);
    if (urls.length) openReceipts();
});

document.querySelector('[data-spp-import-file]')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    const label = document.querySelector('[data-spp-import-filename]');
    if (label) label.textContent = file?.name || 'Ketuk untuk pilih berkas';
    event.target.closest('.spp-import-dropzone')?.classList.toggle('has-file', Boolean(file));
});

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
    alert.addEventListener('click', (event) => {
        if (event.target === alert) alert.remove();
    });
});

document.querySelectorAll('[data-master-nav-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const group = button.closest('.master-nav');
        const open = group.classList.toggle('open');
        button.setAttribute('aria-expanded', String(open));
    });
});

document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const group = button.closest('.nested-nav');
        const open = group.classList.toggle('open');
        button.setAttribute('aria-expanded', String(open));
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

const modal = document.querySelector('[data-modal]');
const masterForm = document.querySelector('[data-master-form]');
const formMethod = document.querySelector('[data-form-method]');
const modalTitle = document.querySelector('[data-modal-title]');
const studentUnit = document.querySelector('[data-student-unit]');
const studentClass = document.querySelector('[data-student-class]');
const studentClassOptions = studentClass ? Array.from(studentClass.options) : [];
const registrationClassList = document.querySelector('[data-registration-class-list]');
const registrationClassRows = registrationClassList ? Array.from(registrationClassList.querySelectorAll('label[data-unit-id]')) : [];
const registrationClassEmpty = document.querySelector('[data-registration-class-empty]');
const registrationAllClasses = document.querySelector('[data-registration-all-classes]');
const registrationSelectedClasses = document.querySelector('[data-registration-selected-classes]');
const registrationScopeValue = document.querySelector('[data-registration-scope-value]');
const registrationScopeSelect = document.querySelector('[data-registration-scope-select]');
const registrationAllClassesRow = document.querySelector('[data-registration-all-row]');
const feeScopeHelp = document.querySelector('[data-fee-scope-help]');
const feeCategories = Array.from(document.querySelectorAll('[data-fee-category]'));
const feeBillingChoice = document.querySelector('[data-fee-billing-choice]');
const feePeriodField = document.querySelector('[data-fee-period-field]');
const feePeriod = document.querySelector('[data-fee-period]');
const feeBehaviorTitle = document.querySelector('[data-fee-behavior-title]');
const feeBehaviorDescription = document.querySelector('[data-fee-behavior-description]');
const studentFilterPairs = Array.from(document.querySelectorAll('[data-student-filter-unit]'))
    .map((unitSelect) => {
        const scope = unitSelect.closest('form') || document;
        const classSelect = scope.querySelector('[data-student-filter-class]');

        return classSelect ? { unitSelect, classSelect, options: Array.from(classSelect.options) } : null;
    })
    .filter(Boolean);
const studentFilterToggle = document.querySelector('[data-student-filter-toggle]');
const studentFilterPanel = document.querySelector('[data-student-filter-panel]');
const studentExportToggle = document.querySelector('[data-student-export-toggle]');
const studentExportPanel = document.querySelector('[data-student-export-panel]');
const studentExportUnit = document.querySelector('[data-student-export-unit]');
const studentExportClass = document.querySelector('[data-student-export-class]');
const studentExportClassOptions = studentExportClass ? Array.from(studentExportClass.options) : [];
studentFilterToggle?.addEventListener('click', () => {
    const opening = studentFilterPanel.hidden;
    studentFilterPanel.hidden = !opening;
    if (opening && studentExportPanel) studentExportPanel.hidden = true;
    studentFilterToggle.classList.toggle('active', opening);
    studentFilterToggle.setAttribute('aria-expanded', String(opening));
    studentExportToggle?.setAttribute('aria-expanded', 'false');
});
studentExportToggle?.addEventListener('click', () => {
    const opening = studentExportPanel.hidden;
    studentExportPanel.hidden = !opening;
    if (opening && studentFilterPanel) studentFilterPanel.hidden = true;
    studentExportToggle.setAttribute('aria-expanded', String(opening));
    studentFilterToggle?.setAttribute('aria-expanded', 'false');
});
document.querySelector('[data-student-export-close]')?.addEventListener('click', () => {
    studentExportPanel.hidden = true;
    studentExportToggle?.setAttribute('aria-expanded', 'false');
});
const studentStatus = document.querySelector('[data-student-status]');
const inactiveFields = document.querySelector('[data-inactive-fields]');
const discountPayment = document.querySelector('[data-discount-payment]');
const discountSource = document.querySelector('[data-discount-source]');
const discountFeeType = document.querySelector('[data-discount-fee-type]');
const discountType = document.querySelector('[data-discount-type]');
const discountValue = document.querySelector('[data-discount-value]');
const fatherWhatsapp = document.querySelector('[data-father-whatsapp]');
const motherWhatsapp = document.querySelector('[data-mother-whatsapp]');
const existingStudent = document.querySelector('[data-existing-student]');
const newStudentFields = Array.from(document.querySelectorAll('[data-new-student-fields]'));
const studentRegions = {
    province: document.querySelector('[data-student-region="province"]'),
    city: document.querySelector('[data-student-region="city"]'),
    district: document.querySelector('[data-student-region="district"]'),
    village: document.querySelector('[data-student-region="village"]'),
};
const regionApiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';

const regionPlaceholder = {
    province: 'Pilih Provinsi',
    city: 'Pilih Kabupaten/Kota',
    district: 'Pilih Kecamatan',
    village: 'Pilih Desa',
};

const resetRegionSelect = (level, disabled = true) => {
    const select = studentRegions[level];
    if (!select) return;
    select.replaceChildren(new Option(regionPlaceholder[level], ''));
    select.disabled = disabled;
};

const fillRegionSelect = (level, regions, selectedName = '') => {
    const select = studentRegions[level];
    if (!select) return;
    const options = [new Option(regionPlaceholder[level], '')];
    regions.forEach((region) => {
        const option = new Option(region.name, region.name);
        option.dataset.regionId = region.id;
        options.push(option);
    });
    if (selectedName && !regions.some((region) => region.name === selectedName)) {
        options.push(new Option(selectedName, selectedName));
    }
    select.replaceChildren(...options);
    select.disabled = false;
    select.value = selectedName;
};

const loadRegions = async (level, path, selectedName = '') => {
    const select = studentRegions[level];
    if (!select) return;
    select.replaceChildren(new Option('Memuat pilihan...', ''));
    select.disabled = true;
    try {
        const response = await fetch(`${regionApiBase}/${path}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Daftar wilayah gagal dimuat.');
        fillRegionSelect(level, await response.json(), selectedName);
    } catch {
        fillRegionSelect(level, [], selectedName);
        select.options[0].textContent = 'Daftar wilayah tidak tersedia';
    }
};

const selectedRegionId = (level) => studentRegions[level]?.selectedOptions[0]?.dataset.regionId;

const restoreStudentRegions = async (record = {}) => {
    if (!studentRegions.province) return;
    const values = {
        province: record.province ?? studentRegions.province.value,
        city: record.city ?? studentRegions.city.value,
        district: record.district ?? studentRegions.district.value,
        village: record.village ?? studentRegions.village.value,
    };
    await loadRegions('province', 'provinces.json', values.province);
    const provinceId = selectedRegionId('province');
    if (!provinceId) {
        Object.values(studentRegions).forEach((select) => { if (select?.value) select.disabled = false; });
        return;
    }
    await loadRegions('city', `regencies/${provinceId}.json`, values.city);
    const cityId = selectedRegionId('city');
    if (!cityId) {
        Object.values(studentRegions).forEach((select) => { if (select?.value) select.disabled = false; });
        return;
    }
    await loadRegions('district', `districts/${cityId}.json`, values.district);
    const districtId = selectedRegionId('district');
    if (!districtId) {
        Object.values(studentRegions).forEach((select) => { if (select?.value) select.disabled = false; });
        return;
    }
    await loadRegions('village', `villages/${districtId}.json`, values.village);
};

let studentRegionsReady = false;
const ensureStudentRegions = async (record = null) => {
    if (!studentRegions.province) return;
    if (record) {
        studentRegionsReady = true;
        await restoreStudentRegions(record);
        return;
    }
    if (studentRegionsReady) return;
    studentRegionsReady = true;
    await restoreStudentRegions();
};

const toggleInactiveFields = () => {
    if (!studentStatus || !inactiveFields) return;
    const inactive = !studentStatus.checked;
    inactiveFields.hidden = !inactive;
    inactiveFields.querySelectorAll('input').forEach((input) => { input.required = inactive; });
};

const toggleExistingStudentFields = () => {
    if (!existingStudent || !newStudentFields.length) return;
    const usingExistingStudent = Boolean(existingStudent.value);
    newStudentFields.forEach((section) => {
        section.hidden = usingExistingStudent;
        section.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = usingExistingStudent;
        });
    });
};

const setExistingStudentEditMode = (editing) => {
    if (!existingStudent) return;
    const field = existingStudent.closest('[data-existing-student-field]');
    if (editing) existingStudent.value = '';
    existingStudent.disabled = editing;
    if (field) field.hidden = editing;
    toggleExistingStudentFields();
};

const syncDiscountPaymentFields = () => {
    if (!discountPayment || !discountSource || !discountFeeType) return;
    const [sourceType, feeTypeId = ''] = discountPayment.value.split(':');
    discountSource.value = sourceType;
    discountFeeType.value = sourceType === 'fee_type' ? feeTypeId : '';
};

const syncDiscountPaymentControl = () => {
    if (!discountPayment || !discountSource || !discountFeeType) return;
    discountPayment.value = discountSource.value === 'fee_type' && discountFeeType.value
        ? `fee_type:${discountFeeType.value}`
        : 'spp';
    syncDiscountPaymentFields();
};

const toggleDiscountValueFormat = () => {
    if (!discountType || !discountValue) return;
    const percentage = discountType.value === 'percentage';
    discountValue.dataset.currencyDisabled = String(percentage);
    discountValue.value = percentage ? digitsOnly(discountValue.value) : formatThousands(discountValue.value);
    discountValue.placeholder = percentage ? 'Contoh: 50' : 'Contoh: 300.000';
};

const filterStudentClasses = (selectedClass = '') => {
    if (!studentUnit || (!studentClass && !registrationClassList)) return;
    const unitId = studentUnit.value;
    const selectedClasses = Array.isArray(selectedClass)
        ? selectedClass.map(String)
        : (selectedClass ? [String(selectedClass)] : (registrationAllClasses?.checked ? ['all'] : []));
    if (registrationClassList) {
        let visibleCount = 0;
        const allClassesSelected = selectedClasses.includes('all');
        registrationClassRows.forEach((row) => {
            const input = row.querySelector('input[type="checkbox"]');
            const visible = Boolean(unitId) && row.dataset.unitId === unitId;
            row.hidden = !visible;
            if (input) {
                input.disabled = !visible || allClassesSelected;
                input.checked = visible && !allClassesSelected && selectedClasses.includes(input.value);
            }
            if (visible) visibleCount += 1;
        });
        if (registrationAllClasses && registrationAllClassesRow) {
            const available = Boolean(unitId) && visibleCount > 0;
            registrationAllClassesRow.hidden = !available;
            registrationAllClasses.disabled = !available;
            registrationAllClasses.checked = available && allClassesSelected;
        }
        if (registrationAllClasses && !registrationAllClassesRow) {
            registrationAllClasses.checked = allClassesSelected;
            if (registrationSelectedClasses) registrationSelectedClasses.checked = !allClassesSelected;
        }
        if (registrationClassEmpty) {
            registrationClassEmpty.hidden = visibleCount > 0;
            registrationClassEmpty.textContent = unitId ? 'Belum ada kelas untuk unit pendidikan ini.' : 'Pilih unit pendidikan terlebih dahulu.';
        }
    }
    if (!studentClass) return;
    studentClassOptions.forEach((option) => {
        option.hidden = Boolean(option.value) && !option.hasAttribute('data-all-classes') && option.dataset.unitId !== unitId;
        option.disabled = option.hidden;
    });
    if (studentClass.multiple) {
        studentClassOptions.forEach((option) => {
            option.selected = selectedClasses.includes(option.value) && !option.hidden;
        });
        return;
    }
    if (selectedClasses[0] && studentClassOptions.some((option) => option.value === selectedClasses[0] && !option.hidden)) {
        studentClass.value = selectedClasses[0];
    } else if (studentClass.selectedOptions[0]?.hidden) {
        studentClass.value = '';
    }
};

const syncRegistrationScope = () => {
    if (!registrationAllClasses || !registrationClassList) return;
    const allClasses = registrationAllClasses.checked;
    const classScopeField = registrationClassList.closest('.fee-type-simple-field');
    if (registrationScopeSelect) registrationScopeSelect.value = allClasses ? 'all' : 'selected';
    registrationClassList.hidden = allClasses;
    classScopeField?.classList.toggle('is-class-specific', !allClasses);
    if (registrationScopeValue) registrationScopeValue.value = allClasses ? 'all' : '';
    if (feeScopeHelp) {
        feeScopeHelp.textContent = allClasses
            ? 'Kategori akan berlaku untuk seluruh kelas pada unit yang dipilih.'
            : 'Pilih satu atau beberapa kelas pada unit pendidikan ini.';
    }
    registrationClassRows.forEach((row) => {
        const input = row.querySelector('input[type="checkbox"]');
        if (input) input.disabled = allClasses || row.hidden;
    });
};

const syncFeeCategory = () => {
    if (!feeCategories.length || !feePeriod) return;
    const groupControl = feeCategories.find((input) => input.matches('select')) ?? feeCategories.find((input) => input.checked);
    const group = groupControl?.value ?? 'spp';
    const createsBill = masterForm?.querySelector('input[name="creates_bill"]:checked')?.value ?? '1';
    const settings = {
        spp: ['Bulanan', 'Tagihan bulanan', 'SPP akan masuk ke tagihan siswa setiap bulan.'],
        'daftar-ulang': ['Sekali Bayar', 'Tagihan satu kali', 'Daftar ulang akan muncul satu kali sebagai kewajiban siswa.'],
        laundry: ['Bulanan', 'Transaksi sesuai keikutsertaan', 'Laundry tidak membuat tagihan otomatis; pembayaran dicatat hanya pada bulan yang diikuti.'],
    };

    feeBillingChoice.hidden = group !== 'lain-lain';
    if (group === 'lain-lain') {
        feePeriodField.hidden = createsBill !== '1';
        feeBehaviorTitle.textContent = createsBill === '1' ? 'Tagihan sesuai periode' : 'Transaksi langsung';
        feeBehaviorDescription.textContent = createsBill === '1'
            ? 'Pembayaran akan muncul sebagai kewajiban siswa sesuai periode yang dipilih.'
            : 'Pembayaran dicatat saat diterima dan tidak membuat tagihan otomatis.';
        if (createsBill !== '1') feePeriod.value = 'Sekali Bayar';
        return;
    }

    const [period, title, description] = settings[group];
    feePeriod.value = period;
    feePeriodField.hidden = true;
    feeBehaviorTitle.textContent = title;
    feeBehaviorDescription.textContent = description;
};

registrationAllClasses?.addEventListener('change', () => {
    registrationClassRows.forEach((row) => {
        const input = row.querySelector('input[type="checkbox"]');
        if (!input || row.hidden) return;
        input.checked = false;
        input.disabled = registrationAllClasses.checked;
    });
    syncRegistrationScope();
});
registrationSelectedClasses?.addEventListener('change', syncRegistrationScope);
registrationScopeSelect?.addEventListener('change', () => {
    if (registrationAllClasses) registrationAllClasses.checked = registrationScopeSelect.value === 'all';
    if (registrationSelectedClasses) registrationSelectedClasses.checked = registrationScopeSelect.value === 'selected';
    syncRegistrationScope();
});
registrationClassRows.forEach((row) => {
    row.querySelector('input[type="checkbox"]')?.addEventListener('change', (event) => {
        if (event.currentTarget.checked && registrationAllClasses) registrationAllClasses.checked = false;
    });
});
studentUnit?.addEventListener('change', () => filterStudentClasses());
if (registrationClassList) {
    const selectedRegistrationClasses = registrationClassRows
            .map((row) => row.querySelector('input[type="checkbox"]'))
            .filter((input) => input?.checked)
            .map((input) => input.value);
    filterStudentClasses(registrationAllClasses?.checked ? 'all' : selectedRegistrationClasses);
    syncRegistrationScope();
}
feeCategories.forEach((input) => input.addEventListener('change', syncFeeCategory));
masterForm?.querySelectorAll('input[name="creates_bill"]').forEach((input) => input.addEventListener('change', syncFeeCategory));
syncFeeCategory();
const filterStudentListClasses = (filterPair, preserveSelection = false) => {
    if (!filterPair) return;
    const { unitSelect, classSelect, options } = filterPair;
    const unitId = unitSelect.value;
    const currentClass = preserveSelection ? classSelect.value : '';
    classSelect.disabled = false;
    options.forEach((option) => {
        if (!option.value) {
            option.textContent = 'semua';
            option.hidden = false;
            return;
        }
        option.hidden = Boolean(unitId) && option.dataset.unitId !== unitId;
        option.disabled = option.hidden;
    });
    classSelect.value = currentClass && options.some((option) => option.value === currentClass && !option.hidden)
        ? currentClass
        : '';
};
studentFilterPairs.forEach((filterPair) => {
    filterPair.unitSelect.addEventListener('change', () => filterStudentListClasses(filterPair));
    filterStudentListClasses(filterPair, true);
});
const filterStudentExportClasses = () => {
    if (!studentExportUnit || !studentExportClass) return;
    const unitId = studentExportUnit.value;
    studentExportClass.disabled = !unitId;
    studentExportClassOptions.forEach((option) => {
        if (!option.value) {
            option.textContent = unitId ? 'Semua Kelas' : 'Pilih Unit Pendidikan Dahulu';
            option.hidden = false;
            return;
        }
        option.hidden = option.dataset.unitId !== unitId;
        option.disabled = option.hidden;
    });
    studentExportClass.value = '';
};
studentExportUnit?.addEventListener('change', filterStudentExportClasses);
filterStudentExportClasses();
studentStatus?.addEventListener('change', toggleInactiveFields);
existingStudent?.addEventListener('change', toggleExistingStudentFields);
toggleExistingStudentFields();
discountPayment?.addEventListener('change', syncDiscountPaymentFields);
syncDiscountPaymentFields();
discountType?.addEventListener('change', toggleDiscountValueFormat);
toggleDiscountValueFormat();
document.querySelector('[data-copy-father-whatsapp]')?.addEventListener('click', () => {
    if (!fatherWhatsapp || !motherWhatsapp) return;
    motherWhatsapp.value = fatherWhatsapp.value;
    motherWhatsapp.focus();
});
studentRegions.province?.addEventListener('change', async () => {
    resetRegionSelect('city');
    resetRegionSelect('district');
    resetRegionSelect('village');
    const id = selectedRegionId('province');
    if (id) await loadRegions('city', `regencies/${id}.json`);
});
studentRegions.city?.addEventListener('change', async () => {
    resetRegionSelect('district');
    resetRegionSelect('village');
    const id = selectedRegionId('city');
    if (id) await loadRegions('district', `districts/${id}.json`);
});
studentRegions.district?.addEventListener('change', async () => {
    resetRegionSelect('village');
    const id = selectedRegionId('district');
    if (id) await loadRegions('village', `villages/${id}.json`);
});
studentRegions.province?.closest('form')?.addEventListener('submit', () => {
    Object.values(studentRegions).forEach((select) => { if (select) select.disabled = false; });
});
if (Object.values(studentRegions).some((select) => select?.value)) {
    ensureStudentRegions();
} else {
    studentRegions.province?.addEventListener('focus', () => ensureStudentRegions(), { once: true });
    studentRegions.province?.addEventListener('pointerdown', () => ensureStudentRegions(), { once: true });
}

document.querySelectorAll('[data-student-picker]').forEach((picker) => {
    const search = picker.querySelector('[data-student-search]');
    const select = picker.querySelector('[data-student-source]');
    const results = picker.querySelector('[data-student-results]');
    const optional = picker.hasAttribute('data-student-optional');
    const options = Array.from(select.options).filter((option) => option.value);
    const selected = options.find((option) => option.selected);
    const normalizedStudentText = (value) => value.trim().toLocaleLowerCase('id-ID').replace(/\s+/g, ' ');

    if (selected) search.value = selected.textContent.trim();

    const syncStudentSelection = () => {
        if (select.value) return select.selectedOptions[0] ?? null;

        const query = normalizedStudentText(search.value);
        if (!query) return null;

        const exactMatch = options.find((option) => normalizedStudentText(option.textContent) === query);
        const partialMatches = exactMatch
            ? []
            : options.filter((option) => normalizedStudentText(option.textContent).includes(query));
        const match = exactMatch ?? (partialMatches.length === 1 ? partialMatches[0] : null);

        if (match) {
            select.value = match.value;
            search.value = match.textContent.trim();
            search.setCustomValidity('');
        }

        return match;
    };

    const chooseStudent = (option) => {
        select.value = option.value;
        search.value = option.textContent.trim();
        search.setCustomValidity('');
        results.hidden = true;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const renderStudentResults = () => {
        const query = search.value.trim().toLocaleLowerCase('id-ID');
        const matches = options.filter((option) => option.textContent.toLocaleLowerCase('id-ID').includes(query)).slice(0, 100);
        results.replaceChildren(...matches.map((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = option.textContent.trim();
            button.addEventListener('mousedown', (event) => event.preventDefault());
            button.addEventListener('click', () => chooseStudent(option));
            return button;
        }));
        if (!matches.length) {
            const empty = document.createElement('span');
            empty.textContent = 'Siswa tidak ditemukan';
            results.append(empty);
        }
        results.hidden = false;
    };

    search.addEventListener('focus', renderStudentResults);
    search.addEventListener('input', () => {
        const exactMatch = options.find((option) => normalizedStudentText(option.textContent) === normalizedStudentText(search.value));
        select.value = exactMatch?.value ?? '';
        search.setCustomValidity(exactMatch || optional ? '' : 'Pilih siswa dari hasil pencarian.');
        renderStudentResults();
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') results.hidden = true;
        if (event.key === 'Enter' && !results.hidden) {
            const firstResult = results.querySelector('button');
            if (firstResult) {
                event.preventDefault();
                firstResult.click();
            }
        }
    });
    search.addEventListener('blur', () => window.setTimeout(() => {
        const match = syncStudentSelection();
        if (match) select.dispatchEvent(new Event('change', { bubbles: true }));
        results.hidden = true;
    }, 120));
    search.closest('form')?.addEventListener('submit', (event) => {
        syncStudentSelection();
        if (optional && ! search.value.trim()) {
            select.value = '';
            search.setCustomValidity('');

            return;
        }
        if (optional) {
            search.setCustomValidity('');

            return;
        }
        if (!select.value) {
            event.preventDefault();
            search.setCustomValidity('Pilih siswa dari hasil pencarian.');
            search.reportValidity();
        }
    });

    picker.syncStudentSelection = syncStudentSelection;
});

const closeModal = () => modal?.classList.remove('show');
document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
document.querySelector('[data-modal-open]')?.addEventListener('click', () => {
    masterForm?.reset();
    setExistingStudentEditMode(false);
    masterForm.action = masterForm.dataset.storeAction;
    formMethod.value = 'POST';
    modalTitle.textContent = document.querySelector('[data-modal-open]').textContent.trim();
    filterStudentClasses();
    syncRegistrationScope();
    syncFeeCategory();
    toggleInactiveFields();
    syncDiscountPaymentFields();
    toggleDiscountValueFormat();
    masterForm?.querySelectorAll('[data-currency-input]').forEach(formatCurrencyInput);
    modal?.classList.add('show');
});
modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });

document.querySelectorAll('[data-edit-record]').forEach((button) => {
    button.addEventListener('click', () => {
        const record = JSON.parse(button.dataset.editRecord);
        masterForm.action = button.dataset.updateAction;
        formMethod.value = 'PUT';
        setExistingStudentEditMode(true);
        modalTitle.textContent = 'Edit Data';
        const selectedClass = studentClassOptions.find((option) => option.value === String(record.school_class_id));
        const allClasses = studentClassOptions.find((option) => option.hasAttribute('data-all-classes'));
        if (studentUnit && record.education_unit_id) studentUnit.value = String(record.education_unit_id);
        else if (studentUnit && selectedClass) studentUnit.value = selectedClass.dataset.unitId;
        filterStudentClasses(record.school_class_id ? [record.school_class_id] : (registrationAllClasses ? 'all' : allClasses?.value));
        const permissionValues = Array.isArray(record.permissions) ? record.permissions.map(String) : [];
        masterForm.querySelectorAll('input[name="permissions[]"]').forEach((input) => {
            input.checked = permissionValues.includes(input.value);
        });
        Object.entries(record).forEach(([key, value]) => {
            if (key === 'permissions') return;
            const field = masterForm.elements.namedItem(key);
            if (!field) return;
            if (key === 'school_class_id' && value === null && registrationAllClasses) {
                registrationAllClasses.checked = true;
                return;
            }
            if (key === 'school_class_id' && value === null && allClasses) {
                field.value = allClasses.value;
                return;
            }
            if (field.type === 'checkbox') field.checked = Boolean(value);
            else if (field.type === 'date') field.value = value ? String(value).slice(0, 10) : '';
            else field.value = value ?? '';
        });
        if (registrationClassList) filterStudentClasses(record.school_class_id ? [record.school_class_id] : 'all');
        syncRegistrationScope();
        syncFeeCategory();
        toggleInactiveFields();
        syncDiscountPaymentControl();
        toggleDiscountValueFormat();
        masterForm.querySelectorAll('[data-student-picker]').forEach((picker) => {
            const select = picker.querySelector('[data-student-source]');
            const search = picker.querySelector('[data-student-search]');
            search.value = select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent.trim() : '';
            search.setCustomValidity('');
        });
        masterForm.querySelectorAll('[data-currency-input]').forEach(formatCurrencyInput);
        ensureStudentRegions(record);
        modal?.classList.add('show');
    });
});

const sppForm = document.querySelector('[data-spp-form]');
if (sppForm) {
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const student = sppForm.querySelector('[data-spp-student]');
    const monthCountInput = sppForm.querySelector('[data-spp-month-count-input]');
    const hiddenMonths = sppForm.querySelector('[data-spp-hidden-months]');
    const message = sppForm.querySelector('[data-spp-message]');
    const arrearsNotice = sppForm.querySelector('[data-spp-arrears-notice]');
    const periodOutput = sppForm.querySelector('[data-spp-period]');
    const paidUntilOutput = sppForm.querySelector('[data-spp-paid-until]');
    const monthCountOutput = sppForm.querySelector('[data-spp-month-count]');
    const outputs = {
        base: sppForm.querySelector('[data-spp-base]'),
        original: sppForm.querySelector('[data-spp-original]'),
        discount: sppForm.querySelector('[data-spp-discount]'),
        total: sppForm.querySelector('[data-spp-total]'),
        paid: sppForm.querySelector('[data-spp-paid]'),
        remaining: sppForm.querySelector('[data-spp-remaining]'),
    };
    const paymentStatus = sppForm.querySelector('[data-spp-status]');
    const paidInput = sppForm.querySelector('[data-spp-paid-input]');
    let paidInputEditedManually = Boolean(digitsOnly(paidInput.value));
    let monthCountEditedManually = false;
    let maxMonthCount = 0;
    const transactionTime = sppForm.querySelector('[data-spp-time]');
    const transactionClock = sppForm.querySelector('[data-wib-clock]');
    const initializeTransactionClock = () => {
        const clock = currentWibClock();
        if (transactionClock && !transactionClock.value) {
            transactionClock.value = transactionClock.type === 'time' ? clock.value.slice(0, 5) : clock.display;
        }
        if (transactionTime && !transactionTime.value) {
            transactionTime.value = transactionTime.type === 'time' ? clock.value.slice(0, 5) : clock.value;
        }
    };
    initializeTransactionClock();

    const resetQuote = (text = 'Pilih siswa dan bulan untuk menghitung pembayaran.') => {
        Object.values(outputs).forEach((output) => { output.textContent = currency.format(0); });
        periodOutput.textContent = '-';
        if (paidUntilOutput) paidUntilOutput.textContent = '-';
        monthCountOutput.textContent = '0 bulan';
        paymentStatus.textContent = 'Belum Lunas';
        paidInput.removeAttribute('max');
        if (!paidInputEditedManually) paidInput.value = '';
        message.textContent = text;
        message.classList.remove('error');
    };

    const monthLabel = (month) => month?.month_name || `Bulan ${month?.month ?? '-'}`;

    const syncHiddenPeriods = (items) => {
        hiddenMonths.innerHTML = '';
    };

    const formatPeriodLabel = (items) => {
        if (!items.length) return '-';
        const first = items[0];
        const last = items[items.length - 1];
        return first.month === last.month && first.year === last.year
            ? `${monthLabel(first)} ${first.year}`
            : `${monthLabel(first)} ${first.year} - ${monthLabel(last)} ${last.year}`;
    };

    const updatePeriodText = (items) => {
        if (!items.length) {
            periodOutput.textContent = '-';
            if (paidUntilOutput) paidUntilOutput.textContent = '-';
            monthCountOutput.textContent = '0 bulan';
            return;
        }
        const last = items[items.length - 1];
        periodOutput.textContent = formatPeriodLabel(items);
        if (paidUntilOutput) paidUntilOutput.textContent = `${monthLabel(last)} ${last.year}`;
        monthCountOutput.textContent = `${items.length} bulan`;
    };

    const fillPaidInputFromQuote = (amount) => {
        if (paidInputEditedManually) return;
        paidInput.value = Number(amount) > 0 ? formatThousands(amount) : '';
    };

    const updateQuote = async () => {
        const monthCount = Number(monthCountInput.value);
        if (!student.value || !monthCount) {
            syncHiddenPeriods([]);
            resetQuote(student.value ? 'Isi jumlah bulan pembayaran.' : 'Pilih siswa untuk melihat tagihan SPP berikutnya.');
            return;
        }
        if (maxMonthCount > 0 && monthCount > maxMonthCount) {
            monthCountInput.value = maxMonthCount;
            await updateQuote();
            return;
        }
        const params = new URLSearchParams({ student_id: student.value, month_count: monthCount });
        message.textContent = 'Menghitung nominal dan keringanan...';
        message.classList.remove('error');
        try {
            const response = await fetch(`${sppForm.dataset.quoteUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'Perhitungan gagal.');
            syncHiddenPeriods(data.items || []);
            updatePeriodText(data.items || []);
            outputs.base.textContent = currency.format(data.items[0]?.original_amount ?? 0);
            outputs.original.textContent = currency.format(data.original_amount);
            outputs.discount.textContent = currency.format(data.discount_amount);
            outputs.total.textContent = currency.format(data.remaining_amount);
            outputs.paid.textContent = currency.format(data.paid_amount);
            outputs.remaining.textContent = currency.format(data.remaining_amount);
            paymentStatus.textContent = data.payment_status;
            paidInput.max = data.remaining_amount;
            fillPaidInputFromQuote(data.remaining_amount);
            message.textContent = data.remaining_amount > 0
                ? `Pembayaran ${monthCount} bulan siap diproses. Total bayar otomatis ${currency.format(data.remaining_amount)} dan tetap bisa diedit.`
                : 'Seluruh bulan yang dipilih sudah lunas.';
        } catch (error) {
            syncHiddenPeriods([]);
            resetQuote(error.message);
            message.classList.add('error');
        }
    };

    const loadPaymentPlan = async () => {
        resetQuote(student.value ? 'Memuat status pembayaran bulan...' : 'Pilih siswa untuk melihat bulan yang dapat dibayar.');
        if (!student.value) {
            return;
        }

        const params = new URLSearchParams({ student_id: student.value });
        try {
            const response = await fetch(`${sppForm.dataset.monthsUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'Status bulan gagal dimuat.');
            const oldest = data.oldest_outstanding;
            maxMonthCount = Number(data.max_month_count || 0);
            monthCountInput.max = maxMonthCount || 120;
            if (arrearsNotice) {
                arrearsNotice.hidden = !oldest;
                arrearsNotice.textContent = oldest
                    ? `SPP yang belum dibayar dimulai dari ${oldest.month_name} ${oldest.year}.`
                    : '';
            }
            if (maxMonthCount > 0) {
                const defaultMonthCount = Math.min(
                    Number(data.default_month_count || 1),
                    maxMonthCount,
                );
                const currentMonthCount = Number(monthCountInput.value);
                if (!monthCountEditedManually || !currentMonthCount || currentMonthCount > maxMonthCount) {
                    monthCountInput.value = defaultMonthCount;
                }
                await updateQuote();
                return;
            }
            monthCountInput.value = '';
            monthCountInput.max = 1;
            resetQuote('Seluruh pembayaran SPP sudah lunas.');
        } catch (error) {
            resetQuote(error.message);
            message.classList.add('error');
        }
    };

    paidInput.addEventListener('input', () => {
        paidInputEditedManually = true;
        if (summaryTotal) summaryTotal.textContent = currency.format(Number(digitsOnly(paidInput.value) || 0));
    });
    student.addEventListener('change', () => {
        paidInputEditedManually = false;
        monthCountEditedManually = false;
        loadPaymentPlan();
    });
    monthCountInput.addEventListener('input', () => {
        paidInputEditedManually = false;
        monthCountEditedManually = true;
        updateQuote();
    });
    sppForm.addEventListener('reset', () => window.setTimeout(() => {
        paidInputEditedManually = false;
        monthCountEditedManually = false;
        resetQuote();
        loadPaymentPlan();
    }, 0));
    loadPaymentPlan();
}

const laundryForm = document.querySelector('[data-laundry-form]');
if (laundryForm) {
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const student = laundryForm.querySelector('[data-laundry-student]');
    const fee = laundryForm.querySelector('[data-laundry-fee]');
    const year = laundryForm.querySelector('[data-laundry-year]');
    const monthCountInput = laundryForm.querySelector('[data-laundry-month-count]');
    const monthValues = laundryForm.querySelector('[data-laundry-month-values]');
    const periodEnd = laundryForm.querySelector('[data-laundry-period-end]');
    const startNote = laundryForm.querySelector('[data-laundry-start-note]');
    const message = laundryForm.querySelector('[data-laundry-message]');
    const paidInput = laundryForm.querySelector('[data-laundry-paid-input]');
    const paymentStatus = laundryForm.querySelector('[data-laundry-status]');
    const summaryName = laundryForm.querySelector('[data-laundry-summary-name]');
    const summaryMeta = laundryForm.querySelector('[data-laundry-summary-meta]');
    const summaryCategory = laundryForm.querySelector('[data-laundry-summary-category]');
    const feeOptions = Array.from(fee.options).filter((option) => option.value);
    let payableMonths = [];
    const buildLaundryUrl = (baseUrl, params) => {
        const url = new URL(baseUrl, window.location.origin);
        params.forEach((value, key) => url.searchParams.append(key, value));
        return url.toString();
    };
    const outputs = {
        base: laundryForm.querySelector('[data-laundry-base]'),
        original: laundryForm.querySelector('[data-laundry-original]'),
        discount: laundryForm.querySelector('[data-laundry-discount]'),
        total: laundryForm.querySelector('[data-laundry-total]'),
        paid: laundryForm.querySelector('[data-laundry-paid]'),
        remaining: laundryForm.querySelector('[data-laundry-remaining]'),
    };
    const updateLaundrySummary = () => {
        const selectedStudent = student.selectedOptions[0];
        const selectedFee = fee.selectedOptions[0];
        if (summaryName) summaryName.textContent = selectedStudent?.dataset.name || '-';
        if (summaryMeta) {
            summaryMeta.textContent = selectedStudent?.value
                ? `${selectedStudent.dataset.nis || '-'} · ${selectedStudent.dataset.unitCode || '-'} · ${selectedStudent.dataset.className || '-'}`
                : 'Pilih siswa terlebih dahulu';
        }
        if (summaryCategory) summaryCategory.textContent = selectedFee?.value ? selectedFee.textContent.trim() : 'Laundry';
    };
    const monthLabel = (item) => item ? `${item.month_name} ${item.year}` : '-';
    const selectedLaundryMonths = () => payableMonths.slice(0, Math.max(0, Number(monthCountInput.value || 0)));
    const renderHiddenMonths = (months = []) => {
        if (!monthValues) return;
        monthValues.innerHTML = '';
        months.forEach((item) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'months[]';
            input.value = item.month;
            monthValues.appendChild(input);
        });
    };
    const updateLaundryPeriod = () => {
        const selected = selectedLaundryMonths();
        renderHiddenMonths(selected);
        if (periodEnd) periodEnd.textContent = monthLabel(selected.at(-1));
        if (startNote) {
            startNote.textContent = payableMonths.length
                ? `Laundry yang belum dibayar dimulai dari ${monthLabel(payableMonths[0])}.`
                : 'Pilih siswa dan kategori Laundry untuk melihat periode pembayaran.';
        }
    };
    const resetQuote = (text = 'Pilih siswa, kategori Laundry, dan jumlah bulan untuk menghitung pembayaran.') => {
        Object.values(outputs).forEach((output) => { output.textContent = currency.format(0); });
        paymentStatus.textContent = 'Belum Lunas';
        paidInput.removeAttribute('max');
        updateLaundrySummary();
        updateLaundryPeriod();
        message.textContent = text;
        message.classList.remove('error');
    };
    const filterFees = () => {
        const selectedStudent = student.selectedOptions[0];
        let available = 0;
        feeOptions.forEach((option) => {
            const hidden = !student.value
                || option.dataset.unitId !== selectedStudent?.dataset.unitId
                || (option.dataset.classId && option.dataset.classId !== selectedStudent?.dataset.classId)
                || (option.dataset.yearId && option.dataset.yearId !== selectedStudent?.dataset.yearId);
            option.hidden = hidden;
            option.disabled = hidden;
            if (!hidden) available += 1;
        });
        if (fee.selectedOptions[0]?.hidden) fee.value = '';
        if (available === 1 && !fee.value) fee.value = feeOptions.find((option) => !option.hidden)?.value ?? '';
        fee.disabled = !student.value || available === 0;
        fee.options[0].textContent = !student.value
            ? 'Pilih siswa terlebih dahulu'
            : (available ? 'Pilih set Laundry...' : 'Set Laundry untuk siswa belum tersedia');
        updateLaundrySummary();
    };
    const updateQuote = async () => {
        const months = selectedLaundryMonths();
        updateLaundryPeriod();
        if (!student.value || !fee.value || !year.value || months.length === 0) {
            resetQuote();
            return;
        }
        const params = new URLSearchParams({
            student_id: student.value,
            fee_type_id: fee.value,
            year: year.value,
        });
        months.forEach((month) => params.append('months[]', month.month));
        try {
            message.textContent = 'Menghitung nominal dan keringanan...';
            const response = await fetch(buildLaundryUrl(laundryForm.dataset.quoteUrl, params), { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'Perhitungan gagal.');
            outputs.base.textContent = currency.format(data.items[0]?.original_amount ?? 0);
            outputs.original.textContent = currency.format(data.original_amount);
            outputs.discount.textContent = currency.format(data.discount_amount);
            outputs.total.textContent = currency.format(data.total_amount);
            outputs.paid.textContent = currency.format(data.paid_amount);
            outputs.remaining.textContent = currency.format(data.remaining_amount);
            paymentStatus.textContent = data.payment_status;
            paidInput.max = data.remaining_amount;
            message.textContent = data.remaining_amount > 0
                ? `${months.length} bulan Laundry siap diproses. Maksimal pembayaran ${currency.format(data.remaining_amount)}.`
                : 'Periode Laundry yang dipilih sudah lunas.';
        } catch (error) {
            resetQuote(error.message);
            message.classList.add('error');
        }
    };
    const loadMonths = async (clearSelection = false) => {
        if (clearSelection) monthCountInput.value = '';
        monthCountInput.removeAttribute('max');
        payableMonths = [];
        updateLaundryPeriod();
        if (!student.value || !fee.value || !year.value) {
            resetQuote('Pilih siswa dan kategori Laundry untuk melihat periode yang dapat dibayar.');
            return;
        }
        const params = new URLSearchParams({
            student_id: student.value,
            fee_type_id: fee.value,
            year: year.value,
        });
        try {
            const response = await fetch(buildLaundryUrl(laundryForm.dataset.monthsUrl, params), { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'Status bulan gagal dimuat.');
            payableMonths = data.months.filter((month) => Number(month.remaining_amount) > 0);
            monthCountInput.max = Math.max(1, payableMonths.length);
            if (!monthCountInput.value && payableMonths.length) monthCountInput.value = 1;
            if (Number(monthCountInput.value) > payableMonths.length) monthCountInput.value = payableMonths.length;
            updateLaundryPeriod();
            if (payableMonths.length) {
                updateQuote();
            } else {
                resetQuote('Seluruh pembayaran Laundry pada tahun ini sudah lunas.');
            }
        } catch (error) {
            resetQuote(error.message);
            message.classList.add('error');
        }
    };

    student.addEventListener('change', () => {
        filterFees();
        loadMonths(true);
    });
    fee.addEventListener('change', () => loadMonths(true));
    year.addEventListener('change', () => loadMonths(true));
    monthCountInput.addEventListener('input', () => {
        if (monthCountInput.value === '') {
            updateLaundryPeriod();
            resetQuote();
            return;
        }
        const max = Number(monthCountInput.max || payableMonths.length || 12);
        const value = Math.max(1, Math.min(max, Number(monthCountInput.value || 1)));
        if (String(value) !== monthCountInput.value) monthCountInput.value = value;
        updateQuote();
    });
    filterFees();
    updateLaundrySummary();
    loadMonths();
}

const sppDetailModal = document.querySelector('[data-spp-detail-modal]');
const sppEditModal = document.querySelector('[data-spp-edit-modal]');
const sppCorrectionModal = document.querySelector('[data-spp-correction-modal]');
const sppDeleteModal = document.querySelector('[data-spp-delete-modal]');
const sppCrudModals = [sppDetailModal, sppEditModal, sppCorrectionModal, sppDeleteModal].filter(Boolean);
const sppCurrency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const sppMonthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const formatSppPeriods = (items) => {
    const grouped = new Map();
    [...items]
        .sort((a, b) => (Number(a.year) * 100 + Number(a.month)) - (Number(b.year) * 100 + Number(b.month)))
        .forEach((item) => {
            const year = String(item.year);
            if (!grouped.has(year)) grouped.set(year, []);
            grouped.get(year).push(sppMonthNames[item.month]);
        });

    return Array.from(grouped.entries()).map(([year, months]) => `${months.join(', ')} ${year}`).join('; ');
};
const escapeHtml = (value) => String(value ?? '-').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
const closeSppCrudModals = () => sppCrudModals.forEach((modal) => modal.classList.remove('show'));

document.querySelectorAll('[data-spp-row-toggle]').forEach((button) => button.addEventListener('click', () => {
    const detail = document.querySelector(`[data-spp-row-detail="${button.dataset.sppRowToggle}"]`);
    const isOpen = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!isOpen));
    button.textContent = isOpen ? 'Lihat' : 'Tutup';
    button.classList.toggle('open', !isOpen);
    detail.hidden = isOpen;
}));

document.querySelectorAll('[data-spp-crud-close]').forEach((button) => button.addEventListener('click', closeSppCrudModals));
sppCrudModals.forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeSppCrudModals(); }));

document.querySelectorAll('[data-spp-detail-url]').forEach((button) => button.addEventListener('click', async () => {
    const content = document.querySelector('[data-spp-detail-content]');
    content.innerHTML = '<p class="spp-crud-loading">Memuat detail transaksi...</p>';
    sppDetailModal.classList.add('show');
    try {
        const response = await fetch(button.dataset.sppDetailUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Detail transaksi gagal dimuat.');
        const items = data.items.map((item) => `<tr><td>${escapeHtml(sppMonthNames[item.month])} ${escapeHtml(item.year)}</td><td>${sppCurrency.format(item.total_amount)}</td><td>${sppCurrency.format(item.paid_amount)}</td><td>${sppCurrency.format(item.remaining_amount)}</td><td><span class="status ${item.payment_status === 'Lunas' ? 'success' : 'neutral'}">${escapeHtml(item.payment_status)}</span></td></tr>`).join('');
        const corrections = data.corrections.length
            ? `<div class="spp-correction-history"><strong>Histori Koreksi</strong>${data.corrections.map((correction) => `<div><span>${escapeHtml(correction.corrected_at)} · ${escapeHtml(correction.reason)}</span><b>${sppCurrency.format(correction.old_paid_amount)} → ${sppCurrency.format(correction.new_paid_amount)}</b><small>Refund ${sppCurrency.format(correction.refund_amount)}</small></div>`).join('')}</div>`
            : '';
        content.innerHTML = `<div class="spp-detail-person"><strong>${escapeHtml(data.student.name)}</strong><span>${escapeHtml(data.student.nis)} · ${escapeHtml(data.student.unit)} · ${escapeHtml(data.student.class)}</span></div><div class="spp-detail-grid"><div><span>Waktu Transaksi</span><strong>${escapeHtml(data.transaction_at)}</strong></div><div><span>Cara Bayar</span><strong>${escapeHtml(data.payment_method)}</strong></div><div><span>Status Penerimaan</span><strong>${escapeHtml(data.status)}</strong></div><div><span>Status Pembayaran</span><strong>${escapeHtml(data.payment_status)}</strong></div><div><span>Total Wajib</span><strong>${sppCurrency.format(data.total_amount)}</strong></div><div><span>Dibayar Sekarang</span><strong>${sppCurrency.format(data.paid_amount)}</strong></div><div><span>Keringanan</span><strong>${sppCurrency.format(data.discount_amount)}</strong></div><div><span>Sisa Tagihan</span><strong>${sppCurrency.format(data.remaining_amount)}</strong></div></div><div class="table-wrap spp-detail-table"><table class="data-table"><thead><tr><th>Bulan</th><th>Wajib</th><th>Dibayar</th><th>Sisa</th><th>Status</th></tr></thead><tbody>${items}</tbody></table></div>${corrections}`;
    } catch (error) {
        content.innerHTML = `<p class="spp-crud-error">${escapeHtml(error.message)}</p>`;
    }
}));

document.querySelectorAll('[data-spp-edit-url]').forEach((button) => button.addEventListener('click', async () => {
    const form = document.querySelector('[data-spp-edit-form]');
    try {
        const response = await fetch(button.dataset.sppEditUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Data transaksi gagal dimuat.');
        form.action = button.dataset.sppUpdateUrl;
        form.elements.transaction_date.value = data.transaction_date;
        form.elements.transaction_time.value = data.transaction_time.slice(0, 5).replace(':', '.');
        form.elements.payment_method.value = data.payment_method;
        form.elements.status.value = data.status;
        form.elements.paid_amount.value = data.paid_amount;
        formatCurrencyInput(form.elements.paid_amount);
        const months = formatSppPeriods(data.items);
        document.querySelector('[data-spp-edit-summary]').textContent = `${data.student.name} · ${months} · Total wajib ${sppCurrency.format(data.total_amount)}`;
        sppEditModal.classList.add('show');
    } catch (error) {
        window.alert(error.message);
    }
}));

document.querySelectorAll('[data-spp-delete-url]').forEach((button) => button.addEventListener('click', () => {
    document.querySelector('[data-spp-delete-form]').action = button.dataset.sppDeleteUrl;
    document.querySelector('[data-spp-delete-name]').textContent = button.dataset.sppDeleteName;
    sppDeleteModal.classList.add('show');
}));

document.querySelectorAll('[data-spp-correction-url]').forEach((button) => button.addEventListener('click', () => {
    const form = document.querySelector('[data-spp-correction-form]');
    const amount = Number(button.dataset.sppCorrectionAmount);
    form.reset();
    form.action = button.dataset.sppCorrectionUrl;
    document.querySelector('[data-spp-correction-name]').value = button.dataset.sppCorrectionName;
    document.querySelector('[data-spp-correction-old]').value = sppCurrency.format(amount);
    const newAmount = document.querySelector('[data-spp-correction-new]');
    newAmount.value = amount;
    formatCurrencyInput(newAmount);
    newAmount.dataset.max = Math.max(0, amount - 1);
    sppCorrectionModal.classList.add('show');
}));

const otherEditModal = document.querySelector('[data-other-edit-modal]');
const otherDeleteModal = document.querySelector('[data-other-delete-modal]');
const otherCrudModals = [otherEditModal, otherDeleteModal].filter(Boolean);
const closeOtherCrudModals = () => otherCrudModals.forEach((modal) => modal.classList.remove('show'));

document.querySelectorAll('[data-other-crud-close]').forEach((button) => button.addEventListener('click', closeOtherCrudModals));
otherCrudModals.forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeOtherCrudModals(); }));

document.querySelectorAll('[data-other-edit-url]').forEach((button) => button.addEventListener('click', async () => {
    const form = document.querySelector('[data-other-edit-form]');
    try {
        const response = await fetch(button.dataset.otherEditUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Data transaksi gagal dimuat.');
        form.action = button.dataset.otherUpdateUrl;
        form.elements.transaction_date.value = data.transaction_date;
        form.elements.transaction_time.value = data.transaction_time.slice(0, 5).replace(':', '.');
        form.elements.payment_method.value = data.payment_method;
        form.elements.status.value = data.status;
        document.querySelector('[data-other-edit-summary]').textContent = `${data.student_name} · ${data.payment_name}`;
        otherEditModal.classList.add('show');
    } catch (error) {
        window.alert(error.message);
    }
}));

document.querySelectorAll('[data-other-delete-url]').forEach((button) => button.addEventListener('click', () => {
    document.querySelector('[data-other-delete-form]').action = button.dataset.otherDeleteUrl;
    document.querySelector('[data-other-delete-name]').textContent = button.dataset.otherDeleteName;
    otherDeleteModal.classList.add('show');
}));

const otherForm = document.querySelector('[data-other-form]');
if (otherForm) {
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const student = otherForm.querySelector('[data-other-student]');
    const studentSearch = otherForm.querySelector('[data-other-student-search]');
    const fee = otherForm.querySelector('[data-other-fee]');
    const academicYear = otherForm.querySelector('[data-other-academic-year]');
    const message = otherForm.querySelector('[data-other-message]');
    const original = otherForm.querySelector('[data-other-original]');
    const discount = otherForm.querySelector('[data-other-discount]');
    const paid = otherForm.querySelector('[data-other-paid]');
    const total = otherForm.querySelector('[data-other-total]');
    const paidInput = otherForm.querySelector('[data-other-paid-input]');
    const submitButton = otherForm.querySelector('[data-other-submit]');
    const summaryName = otherForm.querySelector('[data-other-summary-name]');
    const summaryMeta = otherForm.querySelector('[data-other-summary-meta]');
    const summaryCategory = otherForm.querySelector('[data-other-summary-category]');
    const summaryTotal = otherForm.querySelector('[data-other-summary-total]');
    const summaryStatus = otherForm.querySelector('[data-other-summary-status]');
    const autoFillPaidInput = otherForm.dataset.paymentCategory === 'daftar-ulang';
    let paidInputEditedManually = Boolean(digitsOnly(paidInput.value));
    const time = otherForm.querySelector('[data-other-time]');
    const clockDisplay = otherForm.querySelector('[data-wib-clock]');
    const feeOptions = Array.from(fee.options).filter((option) => option.value);
    const feePlaceholder = fee.querySelector('option:not([value])');
    const studentPicker = student.closest('[data-student-picker]');
    const syncOtherStudent = () => {
        const match = studentPicker?.syncStudentSelection?.();
        if (match && !student.value) student.value = match.value;

        return student.value;
    };
    const updateClock = () => {
        const clock = currentWibClock();
        if (time && time.type === 'time') {
            if (!time.value) time.value = clock.value.slice(0, 5);
        } else if (time) {
            time.value = clock.value;
        }
        if (clockDisplay) clockDisplay.value = clock.display;
    };
    const updateOtherSummary = () => {
        const selectedStudent = student.selectedOptions[0];
        const selectedFee = fee.selectedOptions[0];
        if (summaryName) summaryName.textContent = selectedStudent?.dataset.name || '-';
        if (summaryMeta) {
            summaryMeta.textContent = selectedStudent?.value
                ? `${selectedStudent.dataset.nis || '-'} · ${selectedStudent.dataset.unitCode || '-'} · ${selectedStudent.dataset.className || '-'}`
                : 'Pilih siswa terlebih dahulu';
        }
        if (summaryCategory) summaryCategory.textContent = selectedFee?.value ? selectedFee.textContent.trim() : '-';
    };
    const setOtherPaymentClosed = (closed) => {
        if (!autoFillPaidInput) return;
        otherForm.dataset.paymentClosed = closed ? 'true' : 'false';
        paidInput.readOnly = closed;
        paidInput.dataset.currencyDisabled = String(closed);
        paidInput.classList.toggle('is-settled', closed);
        if (closed) paidInput.value = 'Sudah Lunas';
        if (submitButton) {
            submitButton.disabled = closed;
            submitButton.textContent = closed ? 'Sudah Lunas' : 'Simpan Pembayaran';
        }
    };
    const resetOtherQuote = (text = 'Pilih siswa dan kategori pembayaran untuk menghitung nominal.') => {
        setOtherPaymentClosed(false);
        original.textContent = currency.format(0);
        discount.textContent = currency.format(0);
        paid.textContent = currency.format(0);
        total.textContent = currency.format(0);
        if (summaryTotal) summaryTotal.textContent = currency.format(0);
        if (summaryStatus) summaryStatus.textContent = 'Belum Lunas';
        updateOtherSummary();
        paidInput.dataset.max = '';
        if (autoFillPaidInput && !paidInputEditedManually) paidInput.value = '';
        message.textContent = text;
        message.classList.remove('error');
        message.classList.remove('success');
    };
    const fillOtherPaidInputFromQuote = (amount) => {
        if (paidInputEditedManually) return;
        paidInput.value = Number(amount) > 0 ? formatThousands(amount) : '';
    };
    const filterOtherFees = () => {
        syncOtherStudent();
        const classId = student.selectedOptions[0]?.dataset.classId;
        const unitId = student.selectedOptions[0]?.dataset.unitId;
        const yearId = academicYear?.value || student.selectedOptions[0]?.dataset.yearId;
        let availableFees = 0;
        feeOptions.forEach((option) => {
            const hidden = !classId
                || option.dataset.unitId !== unitId
                || (option.dataset.classId && option.dataset.classId !== classId)
                || (option.dataset.yearId && option.dataset.yearId !== yearId);
            option.hidden = hidden;
            option.disabled = hidden;
            if (!hidden) availableFees += 1;
        });
        if (fee.selectedOptions[0]?.hidden) fee.value = '';
        fee.disabled = !student.value || availableFees === 0;
        if (feePlaceholder) {
            feePlaceholder.textContent = !student.value
                ? 'Pilih siswa terlebih dahulu'
                : (availableFees > 0 ? 'Pilih kategori pembayaran...' : 'Tidak ada pembayaran sesuai unit, kelas, dan tahun pelajaran');
        }
    };
    const updateOtherQuote = async () => {
        if (!syncOtherStudent() || !fee.value) {
            resetOtherQuote();
            return;
        }
        const quoteUrl = new URL(otherForm.dataset.quoteUrl, window.location.origin);
        quoteUrl.searchParams.set('category', otherForm.dataset.paymentCategory);
        quoteUrl.searchParams.set('student_id', student.value);
        quoteUrl.searchParams.set('student_search', studentSearch?.value ?? '');
        quoteUrl.searchParams.set('fee_type_id', fee.value);
        try {
            message.textContent = 'Menghitung nominal dan keringanan...';
            const response = await fetch(quoteUrl.toString(), { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'Perhitungan gagal.');
            original.textContent = currency.format(data.original_amount);
            discount.textContent = currency.format(data.discount_amount);
            paid.textContent = currency.format(data.paid_amount);
            total.textContent = currency.format(data.remaining_amount);
            if (summaryTotal) summaryTotal.textContent = currency.format(data.remaining_amount);
            paidInput.dataset.max = data.remaining_amount;
            const isSettled = Number(data.remaining_amount) <= 0;
            if (summaryStatus) summaryStatus.textContent = isSettled ? 'Lunas' : 'Belum Lunas';
            if (autoFillPaidInput) {
                setOtherPaymentClosed(isSettled);
                if (!isSettled) fillOtherPaidInputFromQuote(data.remaining_amount);
            }
            updateOtherSummary();
            message.classList.toggle('success', isSettled);
            message.textContent = !isSettled
                ? (autoFillPaidInput
                    ? `Total Bayar otomatis terisi ${currency.format(data.remaining_amount)} dan tetap bisa diedit untuk pembayaran sebagian.`
                    : 'Masukkan pembayaran maksimal sebesar sisa tagihan.')
                : 'Pembayaran daftar ulang ini sudah lunas. Tidak perlu membuat transaksi baru.';
        } catch (error) {
            resetOtherQuote(error.message);
            message.classList.add('error');
        }
    };
    paidInput.addEventListener('input', () => {
        paidInputEditedManually = true;
    });
    student.addEventListener('change', () => {
        paidInputEditedManually = false;
        filterOtherFees();
        updateOtherQuote();
    });
    fee.addEventListener('change', () => {
        paidInputEditedManually = false;
        updateOtherQuote();
    });
    academicYear?.addEventListener('change', () => {
        paidInputEditedManually = false;
        filterOtherFees();
        updateOtherQuote();
    });
    otherForm.addEventListener('submit', (event) => {
        if (otherForm.dataset.paymentClosed === 'true') event.preventDefault();
    });
    updateClock();
    window.setInterval(updateClock, 1000);
    filterOtherFees();
    updateOtherQuote();
}

const studentImportToolbar = document.querySelector('[data-student-import-toolbar]');
if (studentImportToolbar) {
    const rows = Array.from(document.querySelectorAll('[data-student-import-row]'));
    const limit = studentImportToolbar.querySelector('[data-student-import-limit]');
    const search = studentImportToolbar.querySelector('[data-student-import-search]');
    const statusButtons = Array.from(studentImportToolbar.querySelectorAll('[data-student-import-status]'));
    const summary = document.querySelector('[data-student-import-summary]');
    const showAll = document.querySelector('[data-student-import-show-all]');
    let activeStatus = 'all';

    const normalizeImportSearch = (value) => value.trim().toLocaleLowerCase('id-ID').replace(/\s+/g, ' ');
    const renderStudentImportRows = () => {
        const query = normalizeImportSearch(search.value);
        const matchingRows = rows.filter((row) => {
            const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            const matchesSearch = !query || normalizeImportSearch(row.dataset.search ?? '').includes(query);

            return matchesStatus && matchesSearch;
        });
        const visibleLimit = limit.value === 'all' ? matchingRows.length : Number(limit.value);
        const visibleRows = new Set(matchingRows.slice(0, visibleLimit));
        rows.forEach((row) => { row.hidden = !visibleRows.has(row); });

        const displayed = Math.min(visibleLimit, matchingRows.length);
        summary.textContent = matchingRows.length
            ? `Menampilkan ${displayed.toLocaleString('id-ID')} dari ${matchingRows.length.toLocaleString('id-ID')} hasil`
            : 'Tidak ada data yang sesuai dengan filter.';
        showAll.hidden = displayed >= matchingRows.length;
    };

    statusButtons.forEach((button) => button.addEventListener('click', () => {
        activeStatus = button.dataset.studentImportStatus;
        statusButtons.forEach((item) => item.classList.toggle('active', item === button));
        renderStudentImportRows();
    }));
    limit.addEventListener('change', renderStudentImportRows);
    search.addEventListener('input', renderStudentImportRows);
    showAll.addEventListener('click', () => {
        limit.value = 'all';
        renderStudentImportRows();
    });
    renderStudentImportRows();
}

const classMovementForm = document.querySelector('[data-class-movement-form]');
if (classMovementForm) {
    const studentCheckboxes = Array.from(classMovementForm.querySelectorAll('[data-class-movement-student]'));
    const studentRows = Array.from(classMovementForm.querySelectorAll('[data-class-movement-row]'));
    const emptyRow = classMovementForm.querySelector('[data-class-movement-empty]');
    const checkAll = classMovementForm.querySelector('[data-class-movement-check-all]');
    const countLabel = classMovementForm.querySelector('[data-class-movement-count]');
    const limitSelect = classMovementForm.querySelector('[data-class-movement-limit]');
    const searchInput = classMovementForm.querySelector('[data-class-movement-search]');
    const submitButton = classMovementForm.querySelector('[data-class-movement-submit]');
    const targetClass = classMovementForm.querySelector('[data-class-movement-target]');
    const actionLabel = classMovementForm.dataset.classMovementActionLabel || 'proses';

    const normalizeClassMovementSearch = (value) => value.trim().toLocaleLowerCase('id-ID').replace(/\s+/g, ' ');
    const selectedStudents = () => studentCheckboxes.filter((checkbox) => checkbox.checked);
    const visibleStudentRows = () => studentRows.filter((row) => !row.hidden);
    const renderClassMovementSelection = () => {
        const selectedCount = selectedStudents().length;
        const visibleRows = visibleStudentRows();
        const visibleCheckboxes = visibleRows
            .map((row) => row.querySelector('[data-class-movement-student]'))
            .filter(Boolean);
        const selectedVisibleCount = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;
        if (countLabel) countLabel.textContent = selectedCount.toLocaleString('id-ID');
        if (submitButton) submitButton.disabled = selectedCount < 1;
        if (checkAll) {
            checkAll.checked = visibleCheckboxes.length > 0 && selectedVisibleCount === visibleCheckboxes.length;
            checkAll.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < visibleCheckboxes.length;
        }
        studentCheckboxes.forEach((checkbox) => {
            checkbox.closest('tr')?.classList.toggle('is-selected', checkbox.checked);
        });
    };
    const renderClassMovementRows = () => {
        const query = normalizeClassMovementSearch(searchInput?.value ?? '');
        const matchingRows = studentRows.filter((row) => !query || normalizeClassMovementSearch(row.dataset.search ?? '').includes(query));
        const visibleLimit = !limitSelect || limitSelect.value === 'all' ? matchingRows.length : Number(limitSelect.value);
        const visibleRows = new Set(matchingRows.slice(0, visibleLimit));

        studentRows.forEach((row) => {
            row.hidden = !visibleRows.has(row);
        });
        if (emptyRow) emptyRow.hidden = visibleRows.size > 0;
        renderClassMovementSelection();
    };

    checkAll?.addEventListener('change', () => {
        visibleStudentRows().forEach((row) => {
            const checkbox = row.querySelector('[data-class-movement-student]');
            if (checkbox) checkbox.checked = checkAll.checked;
        });
        renderClassMovementSelection();
    });

    studentCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', renderClassMovementSelection);
    });
    limitSelect?.addEventListener('change', renderClassMovementRows);
    searchInput?.addEventListener('input', renderClassMovementRows);

    classMovementForm.addEventListener('submit', (event) => {
        const selectedCount = selectedStudents().length;
        if (selectedCount < 1) {
            event.preventDefault();
            return;
        }
        const targetLabel = targetClass?.selectedOptions?.[0]?.textContent?.trim();
        if (targetClass && !targetClass.value) return;
        if (!window.confirm(`Yakin ${actionLabel} ${selectedCount.toLocaleString('id-ID')} siswa ke ${targetLabel}?`)) {
            event.preventDefault();
        }
    });

    renderClassMovementRows();
}

const billBuilder = document.querySelector('[data-bill-builder]');
const setBillBuilder = (open) => {
    if (!billBuilder) return;
    billBuilder.hidden = !open;
    if (open) billBuilder.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
document.querySelector('[data-bill-panel-toggle]')?.addEventListener('click', () => setBillBuilder(true));
document.querySelector('[data-bill-panel-close]')?.addEventListener('click', () => setBillBuilder(false));
document.querySelectorAll('[data-bill-tab]').forEach((button) => button.addEventListener('click', () => {
    document.querySelectorAll('[data-bill-tab]').forEach((tab) => tab.classList.toggle('active', tab === button));
    document.querySelectorAll('[data-bill-panel]').forEach((panel) => { panel.hidden = panel.dataset.billPanel !== button.dataset.billTab; });
}));
