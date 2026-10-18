export const initReports = ({ formatCurrencyInput, showAppAlert }) => {
const reportCorrectionModal = document.querySelector('[data-report-correction-modal]');
const reportCancelModal = document.querySelector('[data-report-cancel-modal]');
const reportPaymentModals = [reportCorrectionModal, reportCancelModal].filter(Boolean);
const closeReportPaymentModals = () => reportPaymentModals.forEach((modal) => modal.classList.remove('show'));

document.querySelectorAll('[data-report-payment-close]').forEach((button) => button.addEventListener('click', closeReportPaymentModals));
reportPaymentModals.forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeReportPaymentModals(); }));

document.querySelectorAll('[data-report-correction-url]').forEach((button) => button.addEventListener('click', async () => {
    const form = document.querySelector('[data-report-correction-form]');
    if (!form) return;

    try {
        const response = await fetch(button.dataset.reportPaymentDetailUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Data transaksi gagal dimuat.');

        const paidAmount = Number(data.paid_amount ?? button.dataset.reportPaymentAmount ?? 0);
        form.reset();
        form.action = button.dataset.reportCorrectionUrl;
        form.elements.return_url.value = button.dataset.reportPaymentReturnUrl ?? window.location.href;
        form.elements.transaction_date.value = data.transaction_date ?? button.dataset.reportPaymentDate ?? '';
        form.elements.transaction_time.value = String(data.transaction_time ?? button.dataset.reportPaymentTime ?? '').slice(0, 5).replace(':', '.');
        form.elements.payment_method.value = data.payment_method ?? button.dataset.reportPaymentMethod ?? 'Cash';
        form.elements.status.value = ['Diterima', 'Pending'].includes(data.status) ? data.status : 'Diterima';
        form.elements.new_paid_amount.value = paidAmount;
        formatCurrencyInput(form.elements.new_paid_amount);

        const studentName = data.student?.name ?? data.student_name ?? button.dataset.reportCorrectionSummary ?? 'Data transaksi';
        const paymentName = data.payment_name ?? button.dataset.reportPaymentName ?? 'Pembayaran';
        const studentMeta = data.student
            ? `${data.student.nis ?? '-'} · ${data.student.unit ?? '-'} · ${data.student.class ?? '-'}`
            : (button.dataset.reportCorrectionMeta ?? '');
        document.querySelector('[data-report-correction-summary]').textContent = `${studentName} · ${paymentName}`;
        document.querySelector('[data-report-correction-meta]').textContent = studentMeta;
        document.querySelector('[data-report-correction-old]').textContent = sppCurrency.format(paidAmount);
        reportCorrectionModal.classList.add('show');
    } catch (error) {
        showAppAlert(error.message);
    }
}));

document.querySelectorAll('[data-report-cancel-url]').forEach((button) => button.addEventListener('click', () => {
    const form = document.querySelector('[data-report-cancel-form]');
    if (!form) return;

    form.reset();
    form.action = button.dataset.reportCancelUrl;
    form.elements.return_url.value = button.dataset.reportPaymentReturnUrl ?? window.location.href;
    document.querySelector('[data-report-cancel-summary]').textContent = button.dataset.reportCancelSummary ?? button.dataset.reportPaymentName ?? 'Data transaksi';
    reportCancelModal.classList.add('show');
}));


};
