export const initStudents = ({ confirmAppAction }) => {
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

const studentImportConfirmForm = document.querySelector('[data-student-import-confirm]');
if (studentImportConfirmForm) {
    const progressUrl = studentImportConfirmForm.dataset.progressUrl;
    const progressCard = document.querySelector('[data-student-import-progress]');
    const confirmButton = studentImportConfirmForm.querySelector('button[type="submit"], button:not([type])');
    const confirmLabel = studentImportConfirmForm.querySelector('[data-student-import-confirm-label]');
    const progressStatus = progressCard?.querySelector('[data-student-import-progress-status]');
    const progressMessage = progressCard?.querySelector('[data-student-import-progress-message]');
    const progressPercent = progressCard?.querySelector('[data-student-import-progress-percent]');
    const progressBar = progressCard?.querySelector('[data-student-import-progress-bar]');
    const progressProcessed = progressCard?.querySelector('[data-student-import-progress-processed]');
    const progressTotal = progressCard?.querySelector('[data-student-import-progress-total]');
    const progressCreated = progressCard?.querySelector('[data-student-import-progress-created]');
    const progressUpdated = progressCard?.querySelector('[data-student-import-progress-updated]');
    const progressFailed = progressCard?.querySelector('[data-student-import-progress-failed]');
    const numberFormat = new Intl.NumberFormat('id-ID');
    let progressTimer = null;

    const renderStudentImportProgress = (progress = {}) => {
        const percent = Math.max(0, Math.min(100, Number(progress.percent ?? 0)));
        const status = progress.status || 'processing';
        const statusLabel = {
            pending: 'Menunggu proses',
            processing: 'Sedang mengimpor',
            completed: 'Import selesai',
            failed: 'Import gagal',
        }[status] || status;

        if (progressCard) {
            progressCard.hidden = false;
            progressCard.dataset.status = status;
        }
        if (progressStatus) progressStatus.textContent = statusLabel;
        if (progressMessage) progressMessage.textContent = progress.error_message || progress.message || 'Memproses data siswa.';
        if (progressPercent) progressPercent.textContent = `${percent}%`;
        if (progressBar) progressBar.style.width = `${percent}%`;
        if (progressProcessed) progressProcessed.textContent = numberFormat.format(Number(progress.processed_items ?? 0));
        if (progressTotal) progressTotal.textContent = numberFormat.format(Number(progress.total_items ?? 0));
        if (progressCreated) progressCreated.textContent = numberFormat.format(Number(progress.created_items ?? 0));
        if (progressUpdated) progressUpdated.textContent = numberFormat.format(Number(progress.updated_items ?? 0));
        if (progressFailed) progressFailed.textContent = numberFormat.format(Number(progress.failed_items ?? 0));
    };

    const stopStudentImportPolling = () => {
        if (progressTimer) window.clearInterval(progressTimer);
        progressTimer = null;
    };

    const pollStudentImportProgress = async () => {
        if (!progressUrl) return;

        try {
            const response = await fetch(progressUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) return;

            const progress = await response.json();
            renderStudentImportProgress(progress);
            if (['completed', 'failed'].includes(progress.status)) stopStudentImportPolling();
        } catch {
            // The submit response will still show the final state if polling is interrupted.
        }
    };

    studentImportConfirmForm.addEventListener('submit', async (event) => {
        if (!window.fetch || studentImportConfirmForm.dataset.importSubmitting === 'true') {
            if (studentImportConfirmForm.dataset.importSubmitting === 'true') event.preventDefault();
            return;
        }

        event.preventDefault();
        studentImportConfirmForm.dataset.importSubmitting = 'true';
        confirmButton?.setAttribute('disabled', 'disabled');
        if (confirmLabel) confirmLabel.textContent = 'Mengimpor...';
        renderStudentImportProgress({ status: 'processing', message: 'Memulai import data siswa.', percent: 0 });
        stopStudentImportPolling();
        progressTimer = window.setInterval(pollStudentImportProgress, 2000);
        pollStudentImportProgress();

        try {
            const response = await fetch(studentImportConfirmForm.action, {
                method: 'POST',
                body: new FormData(studentImportConfirmForm),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json().catch(() => ({}));
            if (data.progress) renderStudentImportProgress(data.progress);

            if (response.ok && data.redirect) {
                stopStudentImportPolling();
                window.setTimeout(() => {
                    window.location.href = data.redirect;
                }, 700);
                return;
            }

            stopStudentImportPolling();
            renderStudentImportProgress(data.progress || {
                status: 'failed',
                percent: 100,
                error_message: data.message || 'Import data siswa gagal diproses.',
            });
            studentImportConfirmForm.dataset.importSubmitting = 'false';
            confirmButton?.removeAttribute('disabled');
            if (confirmLabel) confirmLabel.textContent = 'Konfirmasi';
        } catch {
            stopStudentImportPolling();
            renderStudentImportProgress({
                status: 'failed',
                error_message: 'Koneksi terputus saat import data siswa. Periksa kembali data setelah halaman dimuat ulang.',
            });
            studentImportConfirmForm.dataset.importSubmitting = 'false';
            confirmButton?.removeAttribute('disabled');
            if (confirmLabel) confirmLabel.textContent = 'Konfirmasi';
        }
    });
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
        if (classMovementForm.dataset.classMovementConfirmed === 'true') {
            delete classMovementForm.dataset.classMovementConfirmed;
            return;
        }

        const selectedCount = selectedStudents().length;
        if (selectedCount < 1) {
            event.preventDefault();
            return;
        }
        const targetLabel = targetClass?.selectedOptions?.[0]?.textContent?.trim();
        if (targetClass && !targetClass.value) return;
        event.preventDefault();
        confirmAppAction({
            title: 'Konfirmasi Perubahan Siswa',
            message: `Yakin ${actionLabel} ${selectedCount.toLocaleString('id-ID')} siswa ke ${targetLabel}?`,
            confirmLabel: 'Konfirmasi',
        }).then((confirmed) => {
            if (!confirmed) return;
            classMovementForm.dataset.classMovementConfirmed = 'true';
            classMovementForm.requestSubmit();
        });
    });

    renderClassMovementRows();
}


};
