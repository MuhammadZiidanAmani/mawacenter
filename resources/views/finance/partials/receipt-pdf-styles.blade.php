<style>
    @page { size: A4 portrait; margin: 5mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #202020; font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.15; }
    .receipt { width: 100%; border-bottom: 1px dashed #333; padding-bottom: 3px; }
    .header { width: 100%; border-collapse: collapse; border-bottom: 1.25px solid #555; page-break-inside: avoid; }
    .header td { border: 0; padding: 0 0 3px; vertical-align: middle; }
    .logo-cell { width: 42px; }
    .logo { width: 36px; height: 36px; object-fit: contain; }
    .institution h1 { margin: 0; font-size: 13pt; line-height: 1.02; }
    .institution p { margin: 0; font-size: 8.3pt; line-height: 1.08; }
    .keep-cell { width: 105px; text-align: right; }
    .keep-note { display: inline-block; padding: 2px 9px; border: 1px solid #333; font-size: 8.3pt; line-height: 1.05; }
    .title { margin: 2px 0 3px; text-align: center; page-break-inside: avoid; }
    .title h2 { display: inline-block; margin: 0; border-bottom: 1px solid #333; font-size: 10pt; line-height: 1.05; }
    .title p { margin: 0; font-size: 8.3pt; line-height: 1.08; }
    .student-info { width: 100%; margin-bottom: 4px; border-collapse: collapse; page-break-inside: avoid; }
    .student-info td { padding: .5px 2px; border: 0; font-size: 9pt; line-height: 1.12; vertical-align: top; }
    .student-info .label { width: 7%; font-weight: bold; white-space: nowrap; }
    .student-info .separator { width: 1.5%; padding-right: 4px; text-align: center; }
    .student-info .value-left { width: 38.5%; }
    .student-info .label-right { width: 17%; font-weight: bold; white-space: nowrap; }
    .student-info .value-right { width: 35.5%; }
    .payment-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .payment-table thead { display: table-header-group; }
    .payment-table tr { page-break-inside: avoid; }
    .payment-table th, .payment-table td { padding: 3px 4px; border: 1px solid #555; font-size: 9pt; line-height: 1.1; vertical-align: middle; }
    .payment-table th { background: #f5f5f5; font-weight: bold; text-align: center; }
    .payment-table .center { text-align: center; }
    .payment-table .number { text-align: right; white-space: nowrap; }
    .payment-table .totals-label { text-align: right; }
    .payment-table .grand-total { font-weight: bold; }
    .notes { width: 100%; margin-top: 5px; border-collapse: collapse; page-break-inside: avoid; }
    .notes td { width: 50%; padding: 0 3px; border: 0; font-size: 8.3pt; line-height: 1.1; vertical-align: top; }
    .notes .right { text-align: right; }
    .signatures { width: 100%; margin-top: 6px; border-collapse: collapse; page-break-inside: avoid; }
    .signatures td { width: 50%; padding: 0 6px; border: 0; text-align: center; font-size: 8.3pt; line-height: 1.1; vertical-align: top; }
    .signature-label { font-weight: bold; }
    .signature-space { height: 40px; }
    .signature-name { font-weight: bold; }
</style>
