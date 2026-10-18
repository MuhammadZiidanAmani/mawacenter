export const initFinance = ({ digitsOnly, currentWibClock, formatCurrencyInput, formatThousands, showAppAlert }) => {
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
    const editPaymentId = sppForm.dataset.sppEditPayment || '';
    let paidInputEditedManually = Boolean(digitsOnly(paidInput.value));
    let monthCountEditedManually = Boolean(monthCountInput.value);
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
        if (editPaymentId) params.set('edit_payment', editPaymentId);
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
        if (editPaymentId) params.set('edit_payment', editPaymentId);
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
        showAppAlert(error.message);
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
        showAppAlert(error.message);
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
        const intakeStatus = student.selectedOptions[0]?.dataset.intakeStatus || 'returning';
        let availableFees = 0;
        feeOptions.forEach((option) => {
            const studentScope = option.dataset.studentScope || 'all';
            const scopeMismatch = (studentScope === 'returning' && intakeStatus !== 'returning')
                || (studentScope === 'new_transfer' && !['new', 'transfer'].includes(intakeStatus));
            const hidden = !classId
                || option.dataset.unitId !== unitId
                || (option.dataset.classId && option.dataset.classId !== classId)
                || (option.dataset.yearId && option.dataset.yearId !== yearId)
                || scopeMismatch;
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


};
