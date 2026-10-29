export const initMaster = ({ digitsOnly, formatCurrencyInput, formatThousands }) => {
const modal = document.querySelector('[data-modal]');
const masterForm = document.querySelector('[data-master-form]');
const formMethod = document.querySelector('[data-form-method]');
const modalTitle = document.querySelector('[data-modal-title]');
const studentUnit = document.querySelector('[data-student-unit]');
const studentClass = document.querySelector('[data-student-class]');
const studentClassOptions = studentClass ? Array.from(studentClass.options) : [];
const studentEntryDate = document.querySelector('[data-student-entry-date]');
const studentBillingStart = document.querySelector('[data-student-billing-start]');
const registrationScopeSelect = document.querySelector('[data-registration-scope-select]');
const registrationLevelField = document.querySelector('[data-registration-level-field]');
const registrationLevelSelect = document.querySelector('[data-registration-level-select]');
const registrationLevelOptions = registrationLevelSelect ? Array.from(registrationLevelSelect.options) : [];
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
const reportFeeTypePairs = Array.from(document.querySelectorAll('[data-report-payment-type]'))
    .map((paymentTypeSelect) => {
        const scope = paymentTypeSelect.closest('form') || document;
        const feeTypeSelect = scope.querySelector('[data-report-fee-type]');

        return feeTypeSelect ? { paymentTypeSelect, feeTypeSelect, options: Array.from(feeTypeSelect.options) } : null;
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
const augustBillingStartUnits = new Set(['MTS', 'MA', 'PONPES']);
let automaticBillingStart = studentBillingStart?.dataset.autoSuggested === 'true'
    ? studentBillingStart.value
    : '';

const syncStudentBillingStart = () => {
    if (!studentUnit || !studentEntryDate || !studentBillingStart) return;

    const currentValue = studentBillingStart.value;
    if (currentValue && currentValue !== automaticBillingStart) return;

    const unitCode = studentUnit.selectedOptions[0]?.dataset.unitCode?.toUpperCase() || '';
    const entryYear = studentEntryDate.value.slice(0, 4);
    const suggestedValue = augustBillingStartUnits.has(unitCode) && /^\d{4}$/.test(entryYear)
        ? `${entryYear}-08-01`
        : '';

    studentBillingStart.value = suggestedValue;
    automaticBillingStart = suggestedValue;
};

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
    if (!studentUnit || !studentClass) return;
    const unitId = studentUnit.value;
    const selectedClasses = Array.isArray(selectedClass)
        ? selectedClass.map(String)
        : (selectedClass ? [String(selectedClass)] : []);
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

const filterRegistrationLevels = (selectedLevel = '') => {
    if (!studentUnit || !registrationLevelSelect) return;
    const unitId = studentUnit.value;
    registrationLevelOptions.forEach((option) => {
        if (!option.value) return;
        option.hidden = option.dataset.unitId !== unitId;
        option.disabled = option.hidden;
    });
    registrationLevelSelect.value = selectedLevel
        && registrationLevelOptions.some((option) => option.value === String(selectedLevel) && !option.hidden)
        ? String(selectedLevel)
        : '';
};

const syncRegistrationScope = () => {
    if (!registrationScopeSelect || !registrationLevelField || !registrationLevelSelect) return;
    const allLevels = registrationScopeSelect.value === 'all';
    registrationLevelField.hidden = allLevels;
    registrationLevelSelect.disabled = allLevels;
    registrationLevelSelect.required = !allLevels;
    if (feeScopeHelp) {
        feeScopeHelp.textContent = allLevels
            ? 'Kategori akan berlaku untuk seluruh tingkat pada unit yang dipilih.'
            : 'Kategori berlaku untuk semua rombel pada tingkat yang dipilih.';
    }
};

const syncFeeCategory = () => {
    if (!feeCategories.length || !feePeriod) return;
    const groupControl = feeCategories.find((input) => input.matches('select')) ?? feeCategories.find((input) => input.checked);
    const group = groupControl?.value ?? 'spp';
    const createsBill = masterForm?.querySelector('input[name="creates_bill"]:checked')?.value ?? '1';
    const settings = {
        spp: ['Bulanan', 'Tagihan Wajib', 'SPP akan masuk ke tagihan siswa setiap bulan.'],
        'daftar-ulang': ['Sekali Bayar', 'Tagihan Wajib', 'Daftar ulang akan muncul satu kali sebagai kewajiban siswa.'],
        laundry: ['Bulanan', 'Pembayaran Opsional', 'Laundry tidak membuat tagihan otomatis; pembayaran dicatat hanya pada bulan yang diikuti.'],
    };

    feeBillingChoice.hidden = group !== 'lain-lain';
    if (group === 'lain-lain') {
        feePeriodField.hidden = createsBill !== '1';
        feeBehaviorTitle.textContent = createsBill === '1' ? 'Tagihan Wajib' : 'Pembayaran Opsional';
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

registrationScopeSelect?.addEventListener('change', syncRegistrationScope);
studentUnit?.addEventListener('change', () => {
    filterStudentClasses();
    filterRegistrationLevels();
    syncStudentBillingStart();
});
studentEntryDate?.addEventListener('change', () => syncStudentBillingStart());
studentBillingStart?.addEventListener('input', () => {
    if (studentBillingStart.value !== automaticBillingStart) automaticBillingStart = '';
});
syncStudentBillingStart();
if (registrationLevelSelect) {
    filterRegistrationLevels(registrationLevelSelect.value);
    syncRegistrationScope();
}
feeCategories.forEach((input) => input.addEventListener('change', syncFeeCategory));
masterForm?.querySelectorAll('input[name="creates_bill"]').forEach((input) => input.addEventListener('change', syncFeeCategory));
syncFeeCategory();
const filterStudentListClasses = (filterPair, preserveSelection = false) => {
    if (!filterPair) return;
    const { unitSelect, classSelect, options } = filterPair;
    const unitId = unitSelect.value;
    const requiresUnit = classSelect.hasAttribute('data-class-requires-unit');
    const currentClass = classSelect.value;
    classSelect.disabled = requiresUnit && !unitId;
    options.forEach((option) => {
        if (!option.value) {
            option.textContent = requiresUnit && !unitId ? 'Pilih Unit Pendidikan' : 'semua';
            option.hidden = false;
            return;
        }
        const shouldHide = (requiresUnit && !unitId) || (Boolean(unitId) && option.dataset.unitId !== unitId);
        option.hidden = option.value !== currentClass && shouldHide;
        option.disabled = option.hidden;
    });
    classSelect.value = currentClass;
};
studentFilterPairs.forEach((filterPair) => {
    filterPair.unitSelect.addEventListener('change', () => filterStudentListClasses(filterPair));
    filterStudentListClasses(filterPair, true);
});
const filterReportFeeTypes = (filterPair, preserveSelection = false) => {
    if (!filterPair) return;
    const { paymentTypeSelect, feeTypeSelect, options } = filterPair;
    const paymentType = paymentTypeSelect.value;
    const currentFeeType = feeTypeSelect.value;
    feeTypeSelect.disabled = !paymentType;
    options.forEach((option) => {
        if (!option.value) {
            option.textContent = paymentType ? 'Semua' : 'Pilih Kategori Pembayaran';
            option.hidden = false;
            return;
        }
        const shouldHide = !paymentType || option.dataset.paymentGroup !== paymentType;
        option.hidden = option.value !== currentFeeType && shouldHide;
        option.disabled = option.hidden;
    });
    feeTypeSelect.value = currentFeeType;
};
reportFeeTypePairs.forEach((filterPair) => {
    filterPair.paymentTypeSelect.addEventListener('change', () => filterReportFeeTypes(filterPair));
    filterReportFeeTypes(filterPair, true);
});

const reportRowToggleButtons = Array.from(document.querySelectorAll('[data-report-row-toggle]'));
reportRowToggleButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const detailRow = document.getElementById(button.dataset.reportRowToggle);
        if (!detailRow) return;

        const willOpen = detailRow.hidden;
        reportRowToggleButtons.forEach((item) => {
            const row = document.getElementById(item.dataset.reportRowToggle);
            if (!row) return;
            row.hidden = true;
            item.setAttribute('aria-expanded', 'false');
            item.classList.remove('open');
        });

        detailRow.hidden = !willOpen;
        button.setAttribute('aria-expanded', String(willOpen));
        button.classList.toggle('open', willOpen);
    });
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
masterForm?.querySelectorAll('[data-currency-input]').forEach(formatCurrencyInput);
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

document.querySelectorAll('[data-guardian-student-picker]').forEach((picker) => {
    const search = picker.querySelector('[data-guardian-student-search]');
    const results = picker.querySelector('[data-guardian-student-results]');
    const selected = picker.querySelector('[data-guardian-student-selected]');
    const searchUrl = picker.dataset.searchUrl;
    const initialOptions = Array.from(selected.options).map((option) => option.cloneNode(true));
    let requestController = null;
    let searchTimer = null;

    const showMessage = (message) => {
        const item = document.createElement('span');
        item.textContent = message;
        results.replaceChildren(item);
        results.hidden = false;
    };

    const chooseStudent = (student) => {
        let option = Array.from(selected.options).find((candidate) => candidate.value === String(student.id));
        if (!option) {
            option = new Option(student.text, String(student.id), true, true);
            selected.add(option);
        }
        option.selected = true;
        search.value = '';
        results.hidden = true;
        selected.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const renderMatches = async () => {
        const query = search.value.trim();
        if (query.length < 2) {
            showMessage('Ketik minimal 2 karakter nama, NIS, atau NISN siswa.');
            return;
        }

        requestController?.abort();
        requestController = new AbortController();
        showMessage('Mencari siswa...');

        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: requestController.signal,
            });
            if (!response.ok) throw new Error('Pencarian siswa gagal.');
            const payload = await response.json();
            const matches = Array.isArray(payload.data) ? payload.data : [];
            const buttons = matches.map((student) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = student.text;
                button.addEventListener('mousedown', (event) => event.preventDefault());
                button.addEventListener('click', () => chooseStudent(student));
                return button;
            });
            if (buttons.length) results.replaceChildren(...buttons);
            else showMessage('Siswa tidak ditemukan.');
            results.hidden = false;
        } catch (error) {
            if (error.name !== 'AbortError') showMessage('Pencarian siswa gagal. Coba lagi.');
        }
    };

    search.addEventListener('focus', () => {
        if (search.value.trim().length < 2) showMessage('Ketik minimal 2 karakter nama, NIS, atau NISN siswa.');
    });
    search.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(renderMatches, 200);
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
    search.addEventListener('blur', () => window.setTimeout(() => { results.hidden = true; }, 120));

    picker.setSelectedStudents = (students = []) => {
        selected.replaceChildren(...students.map((student) => {
            const unit = student.school_class?.education_unit?.code ?? '-';
            const identity = student.nis || student.nisn || '-';
            return new Option(`${unit} - ${identity} - ${student.name}`, String(student.id), true, true);
        }));
        search.value = '';
        results.hidden = true;
    };
    picker.resetGuardianStudents = () => {
        selected.replaceChildren(...initialOptions.map((option) => option.cloneNode(true)));
        search.value = '';
        results.hidden = true;
    };
});

document.querySelectorAll('[data-student-picker]').forEach((picker) => {
    const search = picker.querySelector('[data-student-search]');
    const select = picker.querySelector('[data-student-source]');
    const results = picker.querySelector('[data-student-results]');
    const optional = picker.hasAttribute('data-student-optional');
    const options = Array.from(select.options).filter((option) => option.value);
    const selected = options.find((option) => option.selected);
    const normalizedStudentText = (value) => value.trim().toLocaleLowerCase('id-ID').replace(/\s+/g, ' ');
    const minimumQueryLength = 2;
    const maximumVisibleResults = 8;

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

        if (query.length < minimumQueryLength) {
            const hint = document.createElement('span');
            hint.textContent = 'Ketik minimal 2 huruf nama atau NIS siswa.';
            results.replaceChildren(hint);
            results.hidden = false;

            return;
        }

        const allMatches = options.filter((option) => option.textContent.toLocaleLowerCase('id-ID').includes(query));
        const matches = allMatches.slice(0, maximumVisibleResults);
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
        if (allMatches.length > maximumVisibleResults) {
            const limit = document.createElement('span');
            limit.textContent = 'Persempit pencarian untuk melihat hasil lainnya.';
            results.append(limit);
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
    masterForm?.querySelectorAll('[data-guardian-student-picker]').forEach((picker) => picker.resetGuardianStudents?.());
    setExistingStudentEditMode(false);
    masterForm.action = masterForm.dataset.storeAction;
    formMethod.value = 'POST';
    modalTitle.textContent = document.querySelector('[data-modal-open]').textContent.trim();
    filterStudentClasses();
    filterRegistrationLevels();
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
        filterStudentClasses(record.school_class_id ? [record.school_class_id] : allClasses?.value);
        if (registrationScopeSelect) registrationScopeSelect.value = record.class_level ? 'level' : 'all';
        filterRegistrationLevels(record.class_level ?? '');
        const permissionValues = Array.isArray(record.permissions) ? record.permissions.map(String) : [];
        masterForm.querySelectorAll('input[name="permissions[]"]').forEach((input) => {
            input.checked = permissionValues.includes(input.value);
        });
        Object.entries(record).forEach(([key, value]) => {
            if (key === 'permissions') return;
            const field = masterForm.elements.namedItem(key);
            if (!field) return;
            if (key === 'school_class_id' && value === null && allClasses) {
                field.value = allClasses.value;
                return;
            }
            if (field.type === 'checkbox') field.checked = Boolean(value);
            else if (field.type === 'date') field.value = value ? String(value).slice(0, 10) : '';
            else field.value = value ?? '';
        });
        const unitValues = Array.isArray(record.education_units) ? record.education_units.map((unit) => String(unit.id)) : [];
        masterForm.querySelectorAll('select[name="education_unit_ids[]"] option').forEach((option) => {
            option.selected = unitValues.includes(option.value);
        });
        masterForm.querySelectorAll('[data-guardian-student-picker]').forEach((picker) => {
            picker.setSelectedStudents?.(Array.isArray(record.guardian_students) ? record.guardian_students : []);
        });
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


};
