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

document.querySelector('[data-spp-import-file]')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    const label = document.querySelector('[data-spp-import-filename]');
    if (label) label.textContent = file?.name || 'Ketuk untuk pilih berkas';
    event.target.closest('.spp-import-dropzone')?.classList.toggle('has-file', Boolean(file));
});

const sppImportToggle = document.querySelector('[data-spp-import-toggle]');
const sppImportPanel = document.querySelector('[data-spp-import-panel]');
const setSppImportModal = (open) => {
    if (!sppImportPanel) return;
    sppImportPanel.hidden = !open;
    sppImportPanel.classList.toggle('show', open);
    sppImportToggle?.classList.toggle('active', open);
    sppImportToggle?.setAttribute('aria-expanded', String(open));
    document.body.style.overflow = open ? 'hidden' : '';
};
sppImportToggle?.addEventListener('click', () => {
    setSppImportModal(sppImportPanel?.hidden ?? true);
});
document.querySelectorAll('[data-spp-import-close]').forEach((button) => button.addEventListener('click', () => setSppImportModal(false)));
sppImportPanel?.addEventListener('click', (event) => {
    if (event.target === sppImportPanel) setSppImportModal(false);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sppImportPanel && !sppImportPanel.hidden) setSppImportModal(false);
});
if (sppImportPanel && !sppImportPanel.hidden) document.body.style.overflow = 'hidden';

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
const studentFilterUnit = document.querySelector('[data-student-filter-unit]');
const studentFilterClass = document.querySelector('[data-student-filter-class]');
const studentFilterClassOptions = studentFilterClass ? Array.from(studentFilterClass.options) : [];
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
const discountSource = document.querySelector('[data-discount-source]');
const discountFeeType = document.querySelector('[data-discount-fee-type]');
const discountType = document.querySelector('[data-discount-type]');
const discountValue = document.querySelector('[data-discount-value]');
const fatherWhatsapp = document.querySelector('[data-father-whatsapp]');
const motherWhatsapp = document.querySelector('[data-mother-whatsapp]');
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

const toggleInactiveFields = () => {
    if (!studentStatus || !inactiveFields) return;
    const inactive = !studentStatus.checked;
    inactiveFields.hidden = !inactive;
    inactiveFields.querySelectorAll('input').forEach((input) => { input.required = inactive; });
};

const toggleDiscountFeeType = () => {
    if (!discountSource || !discountFeeType) return;
    const show = discountSource.value === 'fee_type';
    discountFeeType.hidden = !show;
    const select = discountFeeType.querySelector('select');
    if (select) {
        select.required = show;
        if (!show) select.value = '';
    }
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
        : (selectedClass ? [String(selectedClass)] : []);
    if (registrationClassList) {
        let visibleCount = 0;
        registrationClassRows.forEach((row) => {
            const input = row.querySelector('input[type="checkbox"]');
            const visible = Boolean(unitId) && row.dataset.unitId === unitId;
            row.hidden = !visible;
            if (input) {
                input.disabled = !visible;
                input.checked = visible && selectedClasses.includes(input.value);
            }
            if (visible) visibleCount += 1;
        });
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

studentUnit?.addEventListener('change', () => filterStudentClasses());
if (registrationClassList) {
    filterStudentClasses(
        registrationClassRows
            .map((row) => row.querySelector('input[type="checkbox"]'))
            .filter((input) => input?.checked)
            .map((input) => input.value),
    );
}
const filterStudentListClasses = (preserveSelection = false) => {
    if (!studentFilterUnit || !studentFilterClass) return;
    const unitId = studentFilterUnit.value;
    const currentClass = preserveSelection ? studentFilterClass.value : '';
    studentFilterClass.disabled = !unitId;
    studentFilterClassOptions.forEach((option) => {
        if (!option.value) {
            option.textContent = unitId ? 'Semua Kelas' : 'Pilih Unit Pendidikan Dahulu';
            option.hidden = false;
            return;
        }
        option.hidden = option.dataset.unitId !== unitId;
        option.disabled = option.hidden;
    });
    studentFilterClass.value = currentClass && studentFilterClassOptions.some((option) => option.value === currentClass && !option.hidden)
        ? currentClass
        : '';
};
studentFilterUnit?.addEventListener('change', () => filterStudentListClasses());
filterStudentListClasses(true);
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
discountSource?.addEventListener('change', toggleDiscountFeeType);
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
restoreStudentRegions();

document.querySelectorAll('[data-student-picker]').forEach((picker) => {
    const search = picker.querySelector('[data-student-search]');
    const select = picker.querySelector('[data-student-source]');
    const results = picker.querySelector('[data-student-results]');
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
        search.setCustomValidity(exactMatch ? '' : 'Pilih siswa dari hasil pencarian.');
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
    masterForm.action = masterForm.dataset.storeAction;
    formMethod.value = 'POST';
    modalTitle.textContent = document.querySelector('[data-modal-open]').textContent.trim();
    filterStudentClasses();
    toggleInactiveFields();
    toggleDiscountFeeType();
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
        modalTitle.textContent = 'Edit Data';
        const selectedClass = studentClassOptions.find((option) => option.value === String(record.school_class_id));
        const allClasses = studentClassOptions.find((option) => option.hasAttribute('data-all-classes'));
        if (studentUnit && record.education_unit_id) studentUnit.value = String(record.education_unit_id);
        else if (studentUnit && selectedClass) studentUnit.value = selectedClass.dataset.unitId;
        filterStudentClasses(record.school_class_id ? [record.school_class_id] : allClasses?.value);
        Object.entries(record).forEach(([key, value]) => {
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
        if (registrationClassList) filterStudentClasses(record.school_class_id ? [record.school_class_id] : []);
        toggleInactiveFields();
        toggleDiscountFeeType();
        toggleDiscountValueFormat();
        masterForm.querySelectorAll('[data-currency-input]').forEach(formatCurrencyInput);
        restoreStudentRegions(record);
        modal?.classList.add('show');
    });
});

const sppForm = document.querySelector('[data-spp-form]');
if (sppForm) {
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const student = sppForm.querySelector('[data-spp-student]');
    const year = sppForm.querySelector('[data-spp-year]');
    const monthInputs = Array.from(sppForm.querySelectorAll('input[name="months[]"]'));
    const message = sppForm.querySelector('[data-spp-message]');
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
    const transactionTime = sppForm.querySelector('[data-spp-time]');
    const transactionClock = sppForm.querySelector('[data-wib-clock]');
    const updateTransactionClock = () => {
        const clock = currentWibClock();
        transactionTime.value = clock.value;
        transactionClock.value = clock.display;
    };
    updateTransactionClock();
    window.setInterval(updateTransactionClock, 1000);

    const resetQuote = (text = 'Pilih siswa dan bulan untuk menghitung pembayaran.') => {
        Object.values(outputs).forEach((output) => { output.textContent = currency.format(0); });
        paymentStatus.textContent = 'Belum Lunas';
        paidInput.removeAttribute('max');
        message.textContent = text;
        message.classList.remove('error');
    };

    const updateQuote = async () => {
        const months = monthInputs.filter((input) => input.checked).map((input) => input.value);
        if (!student.value || !year.value || months.length === 0) {
            resetQuote();
            return;
        }
        const params = new URLSearchParams({ student_id: student.value, year: year.value });
        months.forEach((month) => params.append('months[]', month));
        message.textContent = 'Menghitung nominal dan keringanan...';
        message.classList.remove('error');
        try {
            const response = await fetch(`${sppForm.dataset.quoteUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
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
                ? `${months.length} bulan dipilih. Masukkan titipan atau pelunasan maksimal ${currency.format(data.remaining_amount)}.`
                : 'Seluruh bulan yang dipilih sudah lunas.';
        } catch (error) {
            resetQuote(error.message);
            message.classList.add('error');
        }
    };

    const applyMonthAvailability = () => {
        monthInputs.forEach((input) => {
            const label = input.closest('label');
            const status = input.dataset.paymentStatus || 'Belum Dibayar';
            const statusLabel = label.querySelector('.spp-month-status');
            label.classList.toggle('is-paid', status === 'Lunas');
            label.classList.toggle('is-partial', status === 'Belum Lunas');
            label.classList.toggle('is-unpaid', status === 'Belum Dibayar');
            statusLabel.textContent = status === 'Lunas' ? 'Sudah Dibayar' : status;
            input.disabled = input.dataset.paymentStatus === 'Lunas' || !input.dataset.paymentStatus;
        });
        const payableInputs = monthInputs.filter((input) => input.dataset.paymentStatus && input.dataset.paymentStatus !== 'Lunas');
        payableInputs.forEach((input, index) => {
            const previousSelected = index === 0 || payableInputs[index - 1].checked;
            input.disabled = !previousSelected;
            if (!previousSelected) input.checked = false;
        });
    };

    const loadMonthAvailability = async (clearSelection = false) => {
        if (clearSelection) monthInputs.forEach((input) => { input.checked = false; });
        monthInputs.forEach((input) => {
            input.disabled = true;
            delete input.dataset.paymentStatus;
            const label = input.closest('label');
            label.classList.remove('is-paid', 'is-partial', 'is-unpaid');
            label.querySelector('.spp-month-status').textContent = 'Memuat...';
        });
        resetQuote(student.value ? 'Memuat status pembayaran bulan...' : 'Pilih siswa untuk melihat bulan yang dapat dibayar.');
        if (!student.value || !year.value) {
            monthInputs.forEach((input) => { input.closest('label').querySelector('.spp-month-status').textContent = 'Pilih siswa'; });
            return;
        }

        const params = new URLSearchParams({ student_id: student.value, year: year.value });
        try {
            const response = await fetch(`${sppForm.dataset.monthsUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'Status bulan gagal dimuat.');
            data.months.forEach((month) => {
                const input = monthInputs.find((item) => Number(item.value) === Number(month.month));
                if (input) input.dataset.paymentStatus = month.payment_status;
            });
            applyMonthAvailability();
            const firstPayable = data.months.find((month) => Number(month.month) === Number(data.first_payable_month));
            resetQuote(firstPayable
                ? `Pembayaran berikutnya dimulai dari bulan ${firstPayable.month_name}. Pilih bulan secara berurutan.`
                : 'Seluruh pembayaran SPP pada tahun ini sudah lunas.');
            if (monthInputs.some((input) => input.checked)) updateQuote();
        } catch (error) {
            resetQuote(error.message);
            message.classList.add('error');
        }
    };

    student.addEventListener('change', () => loadMonthAvailability(true));
    year.addEventListener('change', () => loadMonthAvailability(true));
    monthInputs.forEach((input) => input.addEventListener('change', () => {
        applyMonthAvailability();
        updateQuote();
    }));
    sppForm.addEventListener('reset', () => window.setTimeout(resetQuote, 0));
    loadMonthAvailability();
}

const sppDetailModal = document.querySelector('[data-spp-detail-modal]');
const sppEditModal = document.querySelector('[data-spp-edit-modal]');
const sppCorrectionModal = document.querySelector('[data-spp-correction-modal]');
const sppDeleteModal = document.querySelector('[data-spp-delete-modal]');
const sppCrudModals = [sppDetailModal, sppEditModal, sppCorrectionModal, sppDeleteModal].filter(Boolean);
const sppCurrency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const sppMonthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const escapeHtml = (value) => String(value ?? '-').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
const closeSppCrudModals = () => sppCrudModals.forEach((modal) => modal.classList.remove('show'));

document.querySelectorAll('[data-spp-row-toggle]').forEach((button) => button.addEventListener('click', () => {
    const detail = document.querySelector(`[data-spp-row-detail="${button.dataset.sppRowToggle}"]`);
    const isOpen = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!isOpen));
    button.textContent = isOpen ? '+' : '−';
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
        form.elements.transaction_date.value = formatIndonesianDate(data.transaction_date);
        form.elements.transaction_date.dispatchEvent(new Event('input', { bubbles: true }));
        form.elements.transaction_time.value = data.transaction_time.slice(0, 5).replace(':', '.');
        form.elements.payment_method.value = data.payment_method;
        form.elements.status.value = data.status;
        form.elements.paid_amount.value = data.paid_amount;
        formatCurrencyInput(form.elements.paid_amount);
        const months = data.items.map((item) => `${sppMonthNames[item.month]} ${item.year}`).join(', ');
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
        form.elements.transaction_date.value = formatIndonesianDate(data.transaction_date);
        form.elements.transaction_date.dispatchEvent(new Event('input', { bubbles: true }));
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
    const message = otherForm.querySelector('[data-other-message]');
    const original = otherForm.querySelector('[data-other-original]');
    const discount = otherForm.querySelector('[data-other-discount]');
    const paid = otherForm.querySelector('[data-other-paid]');
    const total = otherForm.querySelector('[data-other-total]');
    const paidInput = otherForm.querySelector('[data-other-paid-input]');
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
        time.value = clock.value;
        clockDisplay.value = clock.display;
    };
    const resetOtherQuote = (text = 'Pilih siswa dan jenis pembayaran untuk menghitung nominal.') => {
        original.textContent = currency.format(0);
        discount.textContent = currency.format(0);
        paid.textContent = currency.format(0);
        total.textContent = currency.format(0);
        paidInput.dataset.max = '';
        message.textContent = text;
        message.classList.remove('error');
    };
    const filterOtherFees = () => {
        syncOtherStudent();
        const classId = student.selectedOptions[0]?.dataset.classId;
        const unitId = student.selectedOptions[0]?.dataset.unitId;
        const yearId = student.selectedOptions[0]?.dataset.yearId;
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
                : (availableFees > 0 ? 'Pilih jenis pembayaran...' : 'Tidak ada pembayaran sesuai unit, kelas, dan tahun siswa');
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
            paidInput.dataset.max = data.remaining_amount;
            message.textContent = data.remaining_amount > 0 ? 'Masukkan pembayaran maksimal sebesar sisa tagihan.' : 'Kategori pembayaran ini sudah lunas.';
        } catch (error) {
            resetOtherQuote(error.message);
            message.classList.add('error');
        }
    };
    student.addEventListener('change', () => { filterOtherFees(); updateOtherQuote(); });
    fee.addEventListener('change', updateOtherQuote);
    updateClock();
    window.setInterval(updateClock, 1000);
    filterOtherFees();
    updateOtherQuote();
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
