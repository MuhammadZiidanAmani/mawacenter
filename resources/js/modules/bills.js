export const initBills = () => {
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

const billSyncProgress = document.querySelector('[data-bill-sync-progress]');
if (billSyncProgress) {
    const formatter = new Intl.NumberFormat('id-ID');
    const percent = billSyncProgress.querySelector('[data-bill-sync-percent]');
    const bar = billSyncProgress.querySelector('[data-bill-sync-bar]');
    const message = billSyncProgress.querySelector('[data-bill-sync-message]');
    const processed = billSyncProgress.querySelector('[data-bill-sync-processed]');
    const total = billSyncProgress.querySelector('[data-bill-sync-total]');
    const created = billSyncProgress.querySelector('[data-bill-sync-created]');
    const skipped = billSyncProgress.querySelector('[data-bill-sync-skipped]');
    const failed = billSyncProgress.querySelector('[data-bill-sync-failed]');
    const syncButton = document.querySelector('.bill-sync-button');
    let reloadedAfterComplete = false;

    const renderBillSyncProgress = (data) => {
        const value = Number(data.percent || 0);
        if (percent) percent.textContent = `${value}%`;
        if (bar) bar.style.width = `${Math.min(100, Math.max(0, value))}%`;
        if (message) message.textContent = data.error_message || data.message || 'Memproses sinkron tagihan.';
        if (processed) processed.textContent = formatter.format(Number(data.processed_items || 0));
        if (total) total.textContent = formatter.format(Number(data.total_items || 0));
        if (created) created.textContent = formatter.format(Number(data.created_items || 0));
        if (skipped) skipped.textContent = formatter.format(Number(data.skipped_items || 0) + Number(data.existing_items || 0));
        if (failed) failed.textContent = formatter.format(Number(data.failed_items || 0));
        if (syncButton) {
            const active = ['pending', 'processing'].includes(data.status);
            syncButton.disabled = active;
            const label = syncButton.querySelector('span');
            if (label) label.textContent = active ? 'Sinkron Berjalan' : 'Sinkron Tagihan';
        }
    };

    const tickBillSyncProgress = async () => {
        try {
            const response = await fetch(billSyncProgress.dataset.progressUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': billSyncProgress.dataset.csrf || '',
                },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Progress sinkron gagal diperbarui.');
            renderBillSyncProgress(data);

            if (['completed', 'failed'].includes(data.status)) {
                window.clearInterval(billSyncProgress.syncTimer);
                if (data.status === 'completed' && !reloadedAfterComplete && billSyncProgress.dataset.active === 'true') {
                    reloadedAfterComplete = true;
                    window.setTimeout(() => window.location.reload(), 900);
                }
            }
        } catch (error) {
            if (message) message.textContent = error.message;
        }
    };

    if (billSyncProgress.dataset.active === 'true') {
        tickBillSyncProgress();
        billSyncProgress.syncTimer = window.setInterval(tickBillSyncProgress, 2000);
    }
}

};
