<style>
    @page { size: A4 portrait; margin: 5mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #111; background: #eef1f5; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.2; }
    .receipt-actions { width: min(210mm, calc(100% - 24px)); margin: 14px auto 8px; display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap; }
    .receipt-actions button, .receipt-actions a, .receipt-actions summary { min-height: 36px; padding: 0 14px; display: inline-flex; align-items: center; justify-content: center; color: #0d5f36; background: #f3fbf6; border: 1px solid #b9dcc7; border-radius: 8px; cursor: pointer; font: inherit; font-weight: 700; text-decoration: none; list-style: none; }
    .receipt-actions summary::-webkit-details-marker { display: none; }
    .receipt-actions .primary, .receipt-actions .print { color: #fff; background: #157144; border-color: #157144; }
    .receipt-actions .primary:hover, .receipt-actions .print:hover { background: #0d5f36; border-color: #0d5f36; }
    .next-unit { position: relative; }
    .next-unit div { position: absolute; top: 42px; right: 0; z-index: 5; min-width: 220px; padding: 8px; display: grid; gap: 6px; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .next-unit div a { justify-content: flex-start; min-height: 34px; padding: 0 12px; border: 0; font-weight: 600; }
    .receipt-sheet-viewport { width: min(210mm, calc(100% - 24px)); margin: 0 auto 12mm; overflow: hidden; }
    .receipt-sheet-paper { position: relative; left: 50%; width: 210mm; min-height: 297mm; margin-left: -105mm; padding: 5mm; background: #fff; box-shadow: 0 8px 30px #17203314; transform: scale(var(--receipt-screen-scale, 1)); transform-origin: top center; }
    .receipt-print-wrapper, .page { width: 100%; margin: 0; }
    .page { min-height: 0; background: #fff; }
    .receipt-header { padding: 0 1mm 1.3mm; display: grid; grid-template-columns: 12mm 1fr 34mm; align-items: center; gap: 2mm; border-bottom: .4mm solid #777; break-inside: avoid; page-break-inside: avoid; }
    .receipt-logo { width: 10mm; height: 10mm; display: block; object-fit: contain; }
    .institution h1 { margin: 0 0 .3mm; font-size: 17px; line-height: 1.05; }
    .institution p { margin: 0; font-size: 11px; line-height: 1.15; }
    .keep-note { padding: .7mm 2mm; border: 1px solid #333; font-size: 11px; line-height: 1.1; text-align: center; white-space: nowrap; }
    .receipt-title { margin: 1mm 0 1.4mm; text-align: center; break-inside: avoid; page-break-inside: avoid; }
    .receipt-title h2 { width: max-content; margin: 0 auto; border-bottom: 1px solid #333; font-size: 13.5px; line-height: 1.08; }
    .receipt-title p { margin: .2mm auto 0; max-width: 170mm; font-size: 11px; line-height: 1.15; overflow-wrap: anywhere; }
    .student-info { margin: 0 0 1.5mm; display: grid; grid-template-columns: .88fr 1.12fr; gap: .45mm 6mm; break-inside: avoid; page-break-inside: avoid; }
    .info-line { display: grid; grid-template-columns: 28mm 3mm 1fr; align-items: start; min-width: 0; font-size: 12px; line-height: 1.18; }
    .student-info .info-line:nth-child(odd) { grid-template-columns: 14mm 3mm 1fr; }
    .info-line strong { white-space: nowrap; }
    .info-line span:last-child { min-width: 0; overflow-wrap: anywhere; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead { display: table-header-group; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    th, td { height: 5.4mm; padding: .7mm 1.1mm; border: 1px solid #555; text-align: left; font-size: 12px; line-height: 1.15; vertical-align: middle; overflow-wrap: anywhere; }
    th { height: 5.2mm; font-weight: 700; text-align: center; }
    th.number { text-align: center; }
    .transaction-time { white-space: nowrap; text-align: center; }
    .payment-method { text-align: center; }
    .number { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .totals td { height: 4.6mm; border-top: 0; }
    .totals-label { text-align: right; }
    .grand-total { font-weight: 700; }
    .receipt-notes { margin: 1.8mm 1mm 0; display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; font-size: 11px; line-height: 1.15; break-inside: avoid; page-break-inside: avoid; }
    .receipt-notes div:last-child { text-align: right; }
    .signatures { margin: 2mm 1mm 0; display: grid; grid-template-columns: 1fr 1fr; gap: 14mm; text-align: center; font-size: 11px; line-height: 1.15; break-inside: avoid; page-break-inside: avoid; }
    .signatures p { margin: 0; }
    .signature-space { height: 14mm; }
    .signature-name { font-weight: 700; overflow-wrap: anywhere; }
    .receipt-footer { margin: 2mm 1mm 0; border-bottom: 1px dashed #333; }
    @media (max-width: 820px) {
        .receipt-actions { justify-content: flex-start; }
        .receipt-sheet-viewport { width: calc(100% - 16px); }
    }
    @media print {
        html, body { width: auto; min-height: 0; background: #fff; }
        .receipt-actions { display: none; }
        .receipt-sheet-viewport { width: auto; height: auto !important; margin: 0; overflow: visible; }
        .receipt-sheet-paper { position: static; left: auto; width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; transform: none; }
        .receipt-print-wrapper, .page { width: auto; min-height: 0; margin: 0; }
    }
</style>
