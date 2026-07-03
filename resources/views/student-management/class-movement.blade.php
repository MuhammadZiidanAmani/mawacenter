<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html body .class-movement-page {
            display: grid !important;
            gap: 0 !important;
            min-height: calc(100vh - 64px) !important;
            padding: 48px 48px 56px !important;
            background: #ffffff !important;
            color: #020617 !important;
        }

        html body .class-movement-page .student-list-filter-card {
            width: 100% !important;
            margin: 0 0 16px !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .student-list-table-card {
            width: 100% !important;
            margin: 0 0 16px !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .class-movement-filter-card {
            display: grid !important;
            gap: 16px !important;
            margin: 0 0 16px !important;
        }

        html body .class-movement-page .student-flat-header {
            display: flex !important;
            align-items: flex-end !important;
            justify-content: space-between !important;
            gap: 16px !important;
            margin: 0 0 16px !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .student-master-heading {
            display: grid !important;
            gap: 2px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        html body .class-movement-page .student-master-heading h1 {
            margin: 0 !important;
            color: #111c2c !important;
            font-size: 24px !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            letter-spacing: 0 !important;
        }

        html body .class-movement-page .student-master-heading p {
            margin: 0 !important;
            color: #404942 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.4 !important;
        }

        html body .class-movement-page .student-reference-filter {
            display: grid !important;
            grid-template-columns: 160px 140px 140px 130px minmax(220px, 260px) !important;
            grid-template-rows: auto auto !important;
            align-items: end !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 16px !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 12px !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .student-reference-filter-grid {
            display: contents !important;
        }

        html body .class-movement-page .student-reference-filter-grid label,
        html body .class-movement-page .student-reference-search {
            display: grid !important;
            gap: 6px !important;
            min-width: 0 !important;
            margin: 0 !important;
            color: #4a5568 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
            text-transform: none !important;
        }

        html body .class-movement-page .student-reference-filter-grid label:nth-child(1) { grid-column: 1 !important; grid-row: 1 !important; }
        html body .class-movement-page .student-reference-filter-grid label:nth-child(2) { grid-column: 2 !important; grid-row: 1 !important; }
        html body .class-movement-page .student-reference-filter-grid label:nth-child(3) { grid-column: 3 !important; grid-row: 1 !important; }
        html body .class-movement-page .student-reference-filter-grid label:nth-child(4) { grid-column: 4 !important; grid-row: 1 !important; }

        html body .class-movement-page .student-reference-search {
            position: relative !important;
            grid-column: 5 !important;
            grid-row: 1 !important;
            display: block !important;
            width: 100% !important;
            align-self: end !important;
        }

        html body .class-movement-page .student-reference-filter-grid label > span {
            color: #4a5568 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
            text-transform: none !important;
        }

        html body .class-movement-page .student-reference-search > span {
            display: none !important;
        }

        html body .class-movement-page .student-reference-filter select,
        html body .class-movement-page .student-reference-search input {
            box-sizing: border-box !important;
            width: 100% !important;
            height: 40px !important;
            min-height: 40px !important;
            margin: 0 !important;
            color: #111c2c !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 40px !important;
        }

        html body .class-movement-page .student-reference-filter select {
            padding: 0 10px !important;
        }

        html body .class-movement-page .student-reference-search input {
            padding: 0 12px 0 40px !important;
        }

        html body .class-movement-page .student-reference-search .icon {
            position: absolute !important;
            left: 12px !important;
            top: 50% !important;
            width: 18px !important;
            height: 18px !important;
            color: #4a5568 !important;
            transform: translateY(-50%) !important;
            pointer-events: none !important;
        }

        html body .class-movement-page .student-reference-filter .student-filter-actions {
            grid-column: 1 !important;
            grid-row: 2 !important;
            display: inline-flex !important;
            align-items: end !important;
            justify-content: flex-start !important;
            gap: 10px !important;
            width: auto !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        html body .class-movement-page .student-reference-filter .student-fee-card-search-button,
        html body .class-movement-page .student-reference-filter .student-fee-card-reset-button,
        html body .class-movement-page .student-reference-filter .fee-type-card-search-button,
        html body .class-movement-page .student-reference-filter .fee-type-card-reset-button {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 96px !important;
            min-width: 96px !important;
            max-width: 96px !important;
            height: 40px !important;
            min-height: 40px !important;
            max-height: 40px !important;
            margin: 0 !important;
            padding: 0 14px !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            text-decoration: none !important;
        }

        html body .class-movement-page .student-reference-filter .student-fee-card-search-button,
        html body .class-movement-page .student-reference-filter .fee-type-card-search-button {
            color: #ffffff !important;
            background: #004528 !important;
            border: 1px solid #004528 !important;
        }

        html body .class-movement-page .student-reference-filter .student-fee-card-search-button:hover,
        html body .class-movement-page .student-reference-filter .fee-type-card-search-button:hover {
            color: #ffffff !important;
            background: #1b5e3c !important;
            border-color: #1b5e3c !important;
        }

        html body .class-movement-page .student-reference-filter .student-fee-card-reset-button,
        html body .class-movement-page .student-reference-filter .fee-type-card-reset-button {
            color: #404942 !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
        }

        html body .class-movement-page .class-transfer-pagination,
        html body .class-movement-page .class-promotion-pagination {
            display: none !important;
        }

        html body .class-movement-page .class-movement-v6-card {
            display: grid !important;
            gap: 14px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        html body .class-movement-page .class-movement-list-card {
            display: grid !important;
            gap: 14px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 16px !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 12px !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .class-movement-card-count {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 14px !important;
            width: 100% !important;
            margin: 0 0 12px !important;
            padding: 0 !important;
            color: #64748b !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.35 !important;
        }

        html body .class-movement-page .student-reference-card-length,
        html body .class-movement-page .student-reference-card-length label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 !important;
            padding: 0 !important;
            color: #64748b !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.35 !important;
        }

        html body .class-movement-page .student-reference-card-length select {
            width: 78px !important;
            min-width: 78px !important;
            height: 34px !important;
            min-height: 34px !important;
            padding: 0 10px !important;
            color: #111c2c !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 500 !important;
        }

        html body .class-movement-page .class-transfer-list-head,
        html body .class-movement-page .class-promotion-list-head {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .class-transfer-list-head > strong,
        html body .class-movement-page .class-promotion-list-head > strong {
            color: #111c2c !important;
            font-size: 18px !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
        }

        html body .class-movement-page .class-transfer-check-all,
        html body .class-movement-page .class-promotion-check-all {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 !important;
            color: #64748b !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1 !important;
            text-transform: none !important;
        }

        html body .class-movement-page .class-transfer-student-list,
        html body .class-movement-page .class-promotion-student-list {
            display: grid !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        html body .class-movement-page .class-transfer-student-card,
        html body .class-movement-page .class-promotion-student-card {
            display: grid !important;
            grid-template-columns: 22px minmax(0, 1fr) max-content !important;
            align-items: start !important;
            gap: 12px !important;
            min-height: 86px !important;
            margin: 0 !important;
            padding: 16px !important;
            color: #111c2c !important;
            background: #ffffff !important;
            border: 1px solid #bfc9c0 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 2px rgba(17, 28, 44, .04) !important;
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease !important;
        }

        html body .class-movement-page .class-transfer-student-card:hover,
        html body .class-movement-page .class-promotion-student-card:hover,
        html body .class-movement-page .class-transfer-student-card:has(input:checked),
        html body .class-movement-page .class-promotion-student-card:has(input:checked) {
            border-color: #93a69b !important;
            box-shadow: 0 8px 18px rgba(17, 28, 44, .08) !important;
            transform: translateY(-1px) !important;
        }

        html body .class-movement-page .class-transfer-student-card input[type="checkbox"],
        html body .class-movement-page .class-promotion-student-card input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            margin: 1px 0 0 !important;
            accent-color: #004528 !important;
        }

        html body .class-movement-page .class-transfer-student-main,
        html body .class-movement-page .class-promotion-student-main {
            display: grid !important;
            gap: 6px !important;
            min-width: 0 !important;
        }

        html body .class-movement-page .class-transfer-student-main > strong,
        html body .class-movement-page .class-promotion-student-main > strong {
            margin: 0 !important;
            color: #004528 !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
            word-break: break-word !important;
        }

        html body .class-movement-page .class-transfer-student-meta,
        html body .class-movement-page .class-promotion-student-meta {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px 14px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        html body .class-movement-page .class-transfer-student-meta span,
        html body .class-movement-page .class-promotion-student-meta span {
            display: grid !important;
            gap: 2px !important;
            min-width: 0 !important;
            color: #404942 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.35 !important;
        }

        html body .class-movement-page .class-transfer-student-meta small,
        html body .class-movement-page .class-promotion-student-meta small {
            color: #707971 !important;
            font-size: 12px !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
        }

        html body .class-movement-page .class-transfer-student-meta b,
        html body .class-movement-page .class-promotion-student-meta b,
        html body .class-movement-page .class-transfer-nis,
        html body .class-movement-page .class-promotion-nis {
            color: #404942 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.35 !important;
            white-space: nowrap !important;
        }

        html body .class-movement-page .class-transfer-empty,
        html body .class-movement-page .class-promotion-empty {
            display: grid !important;
            place-items: center !important;
            gap: 6px !important;
            min-height: 128px !important;
            margin: 0 !important;
            padding: 30px 20px !important;
            color: #64748b !important;
            background: #ffffff !important;
            border: 1px dashed #d1d5db !important;
            border-radius: 12px !important;
            box-shadow: none !important;
            text-align: center !important;
        }

        html body .class-movement-page .class-transfer-empty strong,
        html body .class-movement-page .class-promotion-empty strong {
            color: #111c2c !important;
            font-size: 14px !important;
            font-weight: 600 !important;
        }

        html body .class-movement-page .class-transfer-empty span,
        html body .class-movement-page .class-promotion-empty span {
            color: #64748b !important;
            font-size: 14px !important;
            font-weight: 400 !important;
        }

        html body .class-movement-page .class-transfer-action-panel,
        html body .class-movement-page .class-promotion-action-panel {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 74px !important;
            gap: 12px 16px !important;
            align-items: end !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 16px !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 12px !important;
            box-shadow: none !important;
        }

        html body .class-movement-page.class-promotion-page .class-promotion-action-panel {
            grid-template-columns: minmax(180px, 220px) minmax(0, 1fr) 74px !important;
        }

        html body .class-movement-page .class-transfer-action-panel label,
        html body .class-movement-page .class-promotion-action-panel label {
            display: grid !important;
            gap: 6px !important;
            margin: 0 !important;
            color: #4a5568 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
            text-transform: none !important;
        }

        html body .class-movement-page .class-transfer-action-panel select,
        html body .class-movement-page .class-promotion-action-panel select {
            width: 100% !important;
            height: 40px !important;
            min-height: 40px !important;
            padding: 0 12px !important;
            color: #111c2c !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 400 !important;
        }

        html body .class-movement-page .class-transfer-selected-count,
        html body .class-movement-page .class-promotion-selected-count {
            display: grid !important;
            place-items: center !important;
            gap: 3px !important;
            min-height: 40px !important;
            margin: 0 !important;
            color: #64748b !important;
        }

        html body .class-movement-page .class-transfer-selected-count span,
        html body .class-movement-page .class-promotion-selected-count span {
            color: #64748b !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            text-transform: uppercase !important;
        }

        html body .class-movement-page .class-transfer-selected-count output,
        html body .class-movement-page .class-promotion-selected-count output {
            color: #004528 !important;
            font-size: 24px !important;
            font-weight: 800 !important;
            line-height: 1 !important;
        }

        html body .class-movement-page .class-transfer-action-panel .class-movement-submit,
        html body .class-movement-page .class-promotion-action-panel .class-movement-submit {
            grid-column: 1 / -1 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
            width: 100% !important;
            height: 46px !important;
            min-height: 46px !important;
            padding: 0 18px !important;
            color: #ffffff !important;
            background: #004528 !important;
            border: 1px solid #004528 !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            font-size: 14px !important;
            font-weight: 600 !important;
        }

        @media (width <= 1180px) {
            html body .class-movement-page .student-reference-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            html body .class-movement-page .student-reference-filter-grid label:nth-child(1),
            html body .class-movement-page .student-reference-filter-grid label:nth-child(2),
            html body .class-movement-page .student-reference-filter-grid label:nth-child(3),
            html body .class-movement-page .student-reference-filter-grid label:nth-child(4),
            html body .class-movement-page .student-reference-search,
            html body .class-movement-page .student-reference-filter .student-filter-actions {
                grid-column: auto !important;
                grid-row: auto !important;
            }
        }

        @media (width <= 760px) {
            html body .class-movement-page {
                padding: 24px 20px 34px !important;
            }

            html body .class-movement-page .student-list-filter-card,
            html body .class-movement-page .class-movement-filter-card {
                gap: 16px !important;
                margin-bottom: 16px !important;
            }

            html body .class-movement-page .student-reference-filter {
                grid-template-columns: 1fr !important;
            }

            html body .class-movement-page .student-reference-filter .student-filter-actions {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                align-items: stretch !important;
                gap: 10px !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            html body .class-movement-page .student-reference-filter .student-fee-card-search-button,
            html body .class-movement-page .student-reference-filter .student-fee-card-reset-button,
            html body .class-movement-page .student-reference-filter .fee-type-card-search-button,
            html body .class-movement-page .student-reference-filter .fee-type-card-reset-button {
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
            }

            html body .class-movement-page .class-movement-v6-card {
                margin-top: 0 !important;
            }

            html body .class-movement-page .class-movement-card-count,
            html body .class-movement-page .class-transfer-list-head,
            html body .class-movement-page .class-promotion-list-head {
                align-items: flex-start !important;
                flex-direction: column !important;
            }

            html body .class-movement-page .class-transfer-action-panel,
            html body .class-movement-page .class-promotion-action-panel,
            html body .class-movement-page.class-promotion-page .class-promotion-action-panel {
                grid-template-columns: 1fr !important;
            }

            html body .class-movement-page .class-transfer-student-card,
            html body .class-movement-page .class-promotion-student-card {
                grid-template-columns: 22px minmax(0, 1fr) !important;
            }

            html body .class-movement-page .class-transfer-nis,
            html body .class-movement-page .class-promotion-nis {
                grid-column: 2 !important;
            }
        }

        html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
            grid-template-columns: 160px 150px 120px minmax(220px, 300px) max-content !important;
            grid-template-rows: auto !important;
        }

        html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1) {
            grid-column: 1 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2) {
            grid-column: 2 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(3) {
            grid-column: 3 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search {
            grid-column: 4 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
            grid-column: 5 !important;
            grid-row: 1 !important;
            align-self: end !important;
            width: auto !important;
            min-width: 0 !important;
        }

        @media (width <= 760px) {
            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
                grid-template-columns: 1fr !important;
            }

            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1),
            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2),
            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(3),
            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search,
            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                grid-column: auto !important;
                grid-row: auto !important;
            }

            html body .app-shell .main-panel main.class-movement-page form#class-movement-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                width: 100% !important;
            }
        }

        html body .app-shell .main-panel main.class-movement-page > .student-list-filter-card,
        html body .app-shell .main-panel main.class-movement-page > .class-movement-data-card {
            width: min(100%, 912px) !important;
            max-width: 912px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        @media (width <= 760px) {
            html body .app-shell .main-panel main.class-movement-page > .student-list-filter-card,
            html body .app-shell .main-panel main.class-movement-page > .class-movement-data-card {
                width: 100% !important;
                max-width: none !important;
            }
        }
    </style>
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'switch' => '<path d="M7 7h11m0 0-4-4m4 4-4 4M17 17H6m0 0 4-4m-4 4 4 4"/>',
        'arrow-up' => '<path d="M12 19V5m0 0-5 5m5-5 5 5"/>',
        'filter' => '<path d="M4 5h16l-6 7v5l-4 2v-7Z"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $isPromotion = $mode === 'promotion';
    $actionRoute = $isPromotion ? route('student-management.class-promotion.store') : route('student-management.class-transfer.store');
    $indexRoute = $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index');
    $studentTotal = method_exists($students, 'total') ? $students->total() : $students->count();
    $studentFirst = $studentTotal > 0 ? (method_exists($students, 'firstItem') ? $students->firstItem() : 1) : 0;
    $studentLast = $studentTotal > 0 ? (method_exists($students, 'lastItem') ? $students->lastItem() : $students->count()) : 0;
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => $section,
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button" aria-label="Keluar">{!! $icon('logout') !!}</button>
        </header>

        <main class="student-page identity-standard-page class-movement-page {{ $isPromotion ? 'class-promotion-page' : 'class-transfer-screen' }}">
            @if (session('success'))
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal success-result">
                        <span class="result-icon">✓</span>
                        <strong>Sukses!</strong>
                        <p>{{ session('success') }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span>
                        <strong>Data tidak dapat dipindahkan</strong>
                        <p>{{ $errors->first() }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif

            <section class="student-workspace student-list-filter-card class-movement-v6-filter">
                <div class="student-flat-header">
                    <div class="student-master-heading">
                        <h1>{{ $title }}</h1>
                        <p>{{ $description }}</p>
                    </div>
                </div>

                <form id="class-movement-filter" method="GET" action="{{ $indexRoute }}" class="student-filter-panel student-reference-filter student-fee-card-filter class-movement-filter" data-student-filter-panel>
                    <div class="student-reference-filter-grid student-fee-card-filter-grid">
                        <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected($filters['class_id'] == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                        <label><span>Tahun Pelajaran</span><select name="year_id">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($filters['year_id'] == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                    </div>
                    <label class="student-reference-search student-fee-filter-search">
                        <span>Cari siswa</span>
                        {!! $icon('search') !!}
                        <input name="search" value="{{ $filters['search'] }}" placeholder="Nama atau NIS..." aria-label="Cari siswa">
                    </label>
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                    <div class="student-filter-actions student-fee-card-filter-actions fee-type-card-filter-actions">
                        <button class="button student-fee-card-search-button fee-type-card-search-button">Terapkan</button>
                        <a href="{{ $indexRoute }}" class="button student-fee-card-reset-button fee-type-card-reset-button">Reset</a>
                    </div>
                </form>
            </section>

            <form id="classMovementQueryForm" method="GET" action="{{ $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index') }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="year_id" value="{{ $filters['year_id'] }}">
                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
            </form>

            <section class="card master-card student-data-card student-list-table-card class-movement-data-card">
            <div class="student-reference-card-count">
                <form method="GET" action="{{ $indexRoute }}" class="student-reference-card-length">
                    @foreach(request()->except(['per_page', 'page', 'status']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>Tampilkan
                        <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah siswa yang ditampilkan">
                            @foreach([10, 25, 50, 100, 500] as $size)
                                <option value="{{ $size }}" @selected((string) $filters['per_page'] === (string) $size)>{{ $size }}</option>
                            @endforeach
                            <option value="all" @selected($filters['per_page'] === 'all')>All</option>
                        </select>
                        siswa
                    </label>
                </form>
                <span>
                    {{ $studentTotal > 0 ? 'Menampilkan '.number_format($studentFirst, 0, ',', '.').'-'.number_format($studentLast, 0, ',', '.').' dari '.number_format($studentTotal, 0, ',', '.').' siswa' : 'Menampilkan 0 dari 0 siswa' }}
                </span>
            </div>

            <form method="POST" action="{{ $actionRoute }}" class="class-movement-card class-movement-v6-card {{ $isPromotion ? '' : 'class-transfer-card-mode' }}" data-class-movement-form data-class-movement-action-label="{{ $isPromotion ? 'naikkan kelas' : 'pindahkan kelas' }}">
                @csrf
                <input type="hidden" name="source_year_id" value="{{ $filters['year_id'] }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">

                @if (! $isPromotion)
                <section class="class-movement-list-card">
                    <div class="class-transfer-list-head">
                        <strong>Daftar Siswa</strong>
                        <label class="class-transfer-check-all">
                            <span>Pilih Semua</span>
                            <input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all>
                        </label>
                    </div>

                    <div class="class-transfer-student-list">
                        @forelse ($students as $student)
                            <label class="class-transfer-student-card" data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" data-class-movement-student>
                                <span class="class-transfer-student-main">
                                    <strong>{{ $student->name }}</strong>
                                    <span class="class-transfer-student-meta">
                                        <span><small>Unit</small><b>{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</b></span>
                                        <span><small>Kelas Saat Ini</small><b>{{ $student->schoolClass?->name ?? '-' }}</b></span>
                                    </span>
                                </span>
                                <span class="class-transfer-nis">NIS: {{ $student->nis }}</span>
                            </label>
                        @empty
                            <div class="empty-state class-transfer-empty" data-class-movement-empty><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div>
                        @endforelse
                        @if($students->isNotEmpty())
                            <div class="empty-state class-transfer-empty" data-class-movement-empty hidden><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div>
                        @endif
                    </div>

                    @if(method_exists($students, 'links'))
                        <div class="pagination-wrap class-transfer-pagination">{{ $students->links() }}</div>
                    @endif
                </section>

                <div class="class-transfer-action-panel">
                    <label>Kelas Tujuan
                        <select name="target_class_id" required data-class-movement-target>
                            <option value="">Pilih kelas tujuan...</option>
                            @foreach ($targetClasses as $class)
                                <option value="{{ $class->id }}" @selected(old('target_class_id') == $class->id)>{{ $class->educationUnit?->code }} - {{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="class-transfer-selected-count">
                        <span>Terpilih</span>
                        <output data-class-movement-count aria-live="polite">0</output>
                    </div>
                    <input type="hidden" name="target_year_id" value="{{ $filters['year_id'] }}">
                    <button class="button button-primary class-movement-submit" data-class-movement-submit disabled>{!! $icon('switch') !!} Proses Pindah Kelas</button>
                </div>
                @else
                <section class="class-movement-list-card">
                    <div class="class-promotion-list-head">
                        <strong>Daftar Siswa</strong>
                        <label class="class-promotion-check-all">
                            <span>Pilih Semua</span>
                            <input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all>
                        </label>
                    </div>

                    <div class="class-promotion-student-list">
                        @forelse ($students as $student)
                            <label class="class-promotion-student-card" data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" data-class-movement-student>
                                <span class="class-promotion-student-main">
                                    <strong>{{ $student->name }}</strong>
                                    <span class="class-promotion-student-meta">
                                        <span><small>Unit</small><b>{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</b></span>
                                        <span><small>Kelas Saat Ini</small><b>{{ $student->schoolClass?->name ?? '-' }}</b></span>
                                        <span><small>Tahun Pelajaran</small><b>{{ $student->academicYear?->name ?? '-' }}</b></span>
                                    </span>
                                </span>
                                <span class="class-promotion-nis">NIS: {{ $student->nis }}</span>
                            </label>
                        @empty
                            <div class="empty-state class-promotion-empty" data-class-movement-empty><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div>
                        @endforelse
                        @if($students->isNotEmpty())
                            <div class="empty-state class-promotion-empty" data-class-movement-empty hidden><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div>
                        @endif
                    </div>

                    @if(method_exists($students, 'links'))
                        <div class="pagination-wrap class-promotion-pagination">{{ $students->links() }}</div>
                    @endif
                </section>

                <div class="class-promotion-action-panel">
                    <label>Tahun Pelajaran Tujuan
                        <select name="target_year_id" required>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(old('target_year_id', $targetYearId) == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Kelas Tujuan
                        <select name="target_class_id" required data-class-movement-target>
                            <option value="">Pilih kelas tujuan...</option>
                            @foreach ($targetClasses as $class)
                                <option value="{{ $class->id }}" @selected(old('target_class_id') == $class->id)>{{ $class->educationUnit?->code }} - {{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="class-promotion-selected-count">
                        <span>Terpilih</span>
                        <output data-class-movement-count aria-live="polite">0</output>
                    </div>
                    <button class="button button-primary class-movement-submit" data-class-movement-submit disabled>{!! $icon('arrow-up') !!} Proses Naik Kelas</button>
                </div>
                @endif
            </form>
            </section>
        </main>
    </div>
</div>
</body>
</html>
