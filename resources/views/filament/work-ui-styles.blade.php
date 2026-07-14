@once
    <style>
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) {
            color: #0f172a;
            background:
                radial-gradient(circle at 8% 0%, rgba(29, 124, 242, .13), transparent 32%),
                radial-gradient(circle at 88% 12%, rgba(14, 165, 233, .10), transparent 34%),
                linear-gradient(180deg, #f7fbff 0%, #eef7ff 46%, #f8fbff 100%);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app)))::before,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app)))::after {
            content: "";
            position: fixed;
            z-index: -1;
            pointer-events: none;
            border-radius: 999px;
            filter: blur(4px);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app)))::before {
            top: 18%;
            right: 12%;
            width: 360px;
            height: 360px;
            background: rgba(125, 211, 252, .18);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app)))::after {
            bottom: 8%;
            left: 16%;
            width: 300px;
            height: 300px;
            background: rgba(186, 230, 253, .22);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-layout,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-main-ctn,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-main,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-page,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-page-content {
            background: transparent;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-main {
            padding-top: clamp(18px, 2vw, 30px);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-page {
            gap: 20px;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-ctn,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-form-actions,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-wi-widget,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-modal-window,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-dropdown-panel {
            overflow: hidden;
            border: 1px solid rgba(191, 219, 254, .72);
            border-radius: 28px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .82), rgba(232, 244, 255, .60)),
                radial-gradient(circle at 12% 0%, rgba(255, 255, 255, .92), transparent 38%);
            box-shadow: 0 24px 70px rgba(31, 80, 143, .12);
            backdrop-filter: blur(22px) saturate(1.16);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header {
            position: relative;
            padding: clamp(22px, 2.4vw, 34px);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header::before,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section::before,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-ctn::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .70), transparent 42%),
                radial-gradient(circle at 88% 0%, rgba(29, 124, 242, .10), transparent 34%);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header > *,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section > *,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-ctn > * {
            position: relative;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header-heading {
            color: #0f172a;
            font-size: clamp(30px, 3vw, 44px);
            line-height: 1.04;
            font-weight: 850;
            letter-spacing: 0;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-breadcrumbs,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-breadcrumbs a,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-breadcrumbs-item,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section-header-description {
            color: #64748b;
            font-weight: 700;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar {
            border-right: 1px solid rgba(191, 219, 254, .72);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .78), rgba(230, 244, 255, .58)),
                radial-gradient(circle at 16% 4%, rgba(255, 255, 255, .86), transparent 28%);
            box-shadow: 16px 0 50px rgba(31, 80, 143, .10);
            backdrop-filter: blur(24px) saturate(1.12);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-header {
            border-bottom: 1px solid rgba(191, 219, 254, .60);
            background: transparent;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(29, 124, 242, .45) transparent;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-nav::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(29, 124, 242, .42);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-group-label {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: .02em;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-item-button {
            min-height: 42px;
            border-radius: 16px;
            color: #334155;
            font-weight: 760;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-item-button:hover,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-sidebar-item-active > .fi-sidebar-item-button {
            background: rgba(255, 255, 255, .76);
            color: #0b64d8;
            box-shadow: inset 0 0 0 1px rgba(191, 219, 254, .68), 0 14px 34px rgba(31, 80, 143, .10);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-topbar-ctn,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-topbar {
            background: rgba(247, 251, 255, .72);
            border-bottom: 1px solid rgba(191, 219, 254, .48);
            backdrop-filter: blur(20px) saturate(1.10);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-btn {
            min-height: 42px;
            border-radius: 15px;
            font-weight: 850;
            box-shadow: 0 12px 30px rgba(31, 80, 143, .08);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-btn-color-primary {
            background: linear-gradient(135deg, #2f8af7, #0b64d8);
            color: #fff;
            box-shadow: 0 16px 34px rgba(29, 124, 242, .24);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-input-wrp,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-select-input,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-content,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-fo-field-wrp {
            border-radius: 18px;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-input-wrp,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-select-input {
            background: rgba(255, 255, 255, .72);
            box-shadow: inset 0 0 0 1px rgba(191, 219, 254, .58);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-fo-field-wrp-label,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-fo-field-wrp-label span {
            color: #1e293b;
            font-weight: 780;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section {
            position: relative;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section-header {
            border-color: rgba(191, 219, 254, .52);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section-header-heading {
            color: #0f172a;
            font-weight: 850;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-ctn {
            position: relative;
            border-radius: 28px;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-header,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-toolbar,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-footer {
            border-color: rgba(191, 219, 254, .52);
            background: transparent;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-table thead,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-row:hover {
            background: rgba(239, 246, 255, .62);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-cell,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-row {
            border-color: rgba(191, 219, 254, .42);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-badge {
            border-radius: 999px;
            font-weight: 820;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-tabs {
            gap: 6px;
            padding: 6px;
            border: 1px solid rgba(191, 219, 254, .58);
            border-radius: 18px;
            background: rgba(255, 255, 255, .50);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-tabs-item {
            border-radius: 13px;
            font-weight: 820;
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-tabs-item-active {
            background: rgba(255, 255, 255, .82);
            color: #0b64d8;
            box-shadow: 0 10px 24px rgba(31, 80, 143, .08);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-pagination,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-form-actions {
            border-color: rgba(191, 219, 254, .52);
            background: rgba(255, 255, 255, .54);
        }

        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-modal-window,
        :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-dropdown-panel {
            border-radius: 24px;
        }

        @media (max-width: 1024px) {
            :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-main {
                padding-inline: 14px;
            }

            :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header {
                border-radius: 24px;
                padding: 22px;
            }

            :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-header-heading {
                font-size: clamp(27px, 8vw, 38px);
            }

            :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-section,
            :where(body:not(.nik-employee-edit-page):not(:has(.nik-work-app))) .fi-ta-ctn {
                border-radius: 24px;
            }
        }
    </style>
@endonce
