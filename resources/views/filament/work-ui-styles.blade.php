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

        body:has(.nik-work-legacy-nav) > .fi-topbar-ctn,
        body:has(.nik-work-legacy-nav) .fi-main-sidebar {
            display: none;
        }

        body:has(.nik-work-legacy-nav) .fi-layout,
        body:has(.nik-work-legacy-nav) .fi-main-ctn {
            background: transparent;
        }

        body:has(.nik-work-legacy-nav) .fi-main {
            width: auto;
            max-width: none;
            margin-left: 256px;
            padding: 100px clamp(24px, 2vw, 34px) 48px;
        }

        body:has(.nik-work-legacy-nav.is-sidebar-collapsed) .fi-main {
            margin-left: 116px;
        }

        body:has(.nik-work-legacy-nav) .fi-page {
            max-width: none;
        }

        .nik-work-legacy-nav {
            position: relative;
            z-index: 60;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }

        .nik-work-legacy-nav [x-cloak] {
            display: none;
        }

        .nik-work-legacy-nav .nik-work-svg {
            width: 1em;
            height: 1em;
            flex: none;
        }

        .nik-work-legacy-nav .nik-work-sidebar {
            position: fixed;
            top: 24px;
            bottom: 24px;
            left: 22px;
            z-index: 65;
            display: flex;
            width: 210px;
            min-height: 0;
            padding: 18px 14px;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(191, 219, 254, .70);
            border-radius: 26px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .84), rgba(230, 244, 255, .64)),
                radial-gradient(circle at 15% 0%, rgba(255, 255, 255, .94), transparent 34%);
            box-shadow: 0 28px 80px rgba(31, 80, 143, .14);
            backdrop-filter: blur(24px) saturate(1.14);
            transition: width .2s ease, padding .2s ease;
        }

        .nik-work-legacy-nav .nik-work-logo {
            display: flex;
            min-height: 54px;
            align-items: center;
            padding: 0 8px 14px;
            border-bottom: 1px solid rgba(191, 219, 254, .52);
            text-decoration: none;
        }

        .nik-work-legacy-nav .nik-work-brand-logo {
            display: block;
            width: 132px;
            height: auto;
            object-fit: contain;
        }

        .nik-work-legacy-nav .nik-work-nav {
            display: flex;
            min-height: 0;
            margin: 14px -4px 14px;
            padding: 0 4px;
            flex: 1;
            flex-direction: column;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(29, 124, 242, .42) transparent;
        }

        .nik-work-legacy-nav .nik-work-nav::-webkit-scrollbar {
            width: 4px;
        }

        .nik-work-legacy-nav .nik-work-nav::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(29, 124, 242, .42);
        }

        .nik-work-legacy-nav .nik-work-nav-section {
            margin: 18px 8px 8px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 850;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .nik-work-legacy-nav .nik-work-nav-section:first-child {
            margin-top: 4px;
        }

        .nik-work-legacy-nav .nik-work-nav-link {
            display: grid;
            grid-template-columns: 26px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            min-height: 46px;
            padding: 8px 10px;
            border-radius: 16px;
            color: #334155;
            font-size: 14px;
            font-weight: 760;
            line-height: 1.18;
            text-decoration: none;
            transition: background .18s ease, color .18s ease, box-shadow .18s ease;
        }

        .nik-work-legacy-nav .nik-work-nav-link:hover,
        .nik-work-legacy-nav .nik-work-nav-link.is-active {
            background: rgba(255, 255, 255, .82);
            color: #0b64d8;
            box-shadow: inset 0 0 0 1px rgba(191, 219, 254, .72), 0 14px 30px rgba(31, 80, 143, .10);
        }

        .nik-work-legacy-nav .nik-work-nav-icon {
            display: grid;
            width: 26px;
            height: 26px;
            place-items: center;
            color: currentColor;
            font-size: 18px;
        }

        .nik-work-legacy-nav .nik-work-nav-label {
            min-width: 0;
        }

        .nik-work-legacy-nav .nik-work-nav-badge {
            display: inline-flex;
            min-width: 24px;
            height: 24px;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
            border-radius: 999px;
            background: rgba(219, 234, 254, .76);
            color: #0b64d8;
            font-size: 11px;
            font-weight: 850;
        }

        .nik-work-legacy-nav .nik-work-sidebar-toggle {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(191, 219, 254, .66);
            border-radius: 17px;
            background: rgba(255, 255, 255, .74);
            color: #334155;
            font-weight: 850;
            box-shadow: 0 14px 34px rgba(31, 80, 143, .08);
        }

        .nik-work-legacy-nav .nik-work-sidebar-toggle-icon {
            display: inline-grid;
            place-items: center;
            font-size: 18px;
        }

        .nik-work-legacy-nav .nik-work-sidebar-toggle-icon--expand {
            display: none;
        }

        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-sidebar {
            width: 72px;
            padding-inline: 10px;
        }

        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-brand-logo {
            width: 42px;
        }

        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-nav-link {
            display: flex;
            justify-content: center;
            padding-inline: 8px;
        }

        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-nav-section,
        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-nav-label,
        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-nav-badge,
        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-nav-trailing,
        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-sidebar-toggle-label,
        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-sidebar-toggle-icon--collapse {
            display: none;
        }

        .nik-work-legacy-nav.is-sidebar-collapsed .nik-work-sidebar-toggle-icon--expand {
            display: inline-grid;
        }

        .nik-work-legacy-actions {
            position: fixed;
            top: 26px;
            right: clamp(24px, 2vw, 34px);
            z-index: 64;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nik-work-legacy-search {
            display: flex;
            width: min(330px, 30vw);
            height: 52px;
            align-items: center;
            gap: 10px;
            padding: 0 18px;
            border: 1px solid rgba(191, 219, 254, .72);
            border-radius: 22px;
            background: rgba(255, 255, 255, .78);
            color: #64748b;
            box-shadow: 0 18px 46px rgba(31, 80, 143, .10);
            backdrop-filter: blur(20px);
        }

        .nik-work-legacy-search input {
            min-width: 0;
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #0f172a;
            font-weight: 720;
        }

        .nik-work-legacy-icon-button,
        .nik-work-legacy-avatar,
        .nik-work-user-menu > .nik-work-legacy-avatar {
            position: relative;
            display: inline-grid;
            width: 52px;
            height: 52px;
            place-items: center;
            border: 1px solid rgba(191, 219, 254, .72);
            border-radius: 50%;
            background: rgba(255, 255, 255, .78);
            color: #0f172a;
            font-size: 21px;
            font-weight: 850;
            box-shadow: 0 18px 46px rgba(31, 80, 143, .10);
            backdrop-filter: blur(20px);
        }

        .nik-work-legacy-avatar img,
        .nik-work-legacy-avatar > span:first-child {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            object-fit: cover;
        }

        .nik-work-legacy-avatar > span:first-child {
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #2f8af7, #0b64d8);
            color: #fff;
        }

        .nik-work-legacy-icon-button.has-badge > span {
            position: absolute;
            top: -3px;
            right: -2px;
            display: grid;
            min-width: 19px;
            height: 19px;
            place-items: center;
            padding: 0 5px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            font-weight: 900;
        }

        .nik-work-legacy-nav .nik-work-user-menu {
            position: relative;
        }

        .nik-work-legacy-nav .nik-work-user-chevron {
            display: none;
        }

        .nik-work-legacy-nav .nik-work-user-menu-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 90;
            min-width: 150px;
            padding: 8px;
            border: 1px solid rgba(191, 219, 254, .72);
            border-radius: 18px;
            background: rgba(255, 255, 255, .88);
            box-shadow: 0 22px 56px rgba(31, 80, 143, .16);
            backdrop-filter: blur(22px);
        }

        .nik-work-legacy-nav .nik-work-user-menu-panel button {
            width: 100%;
            min-height: 40px;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: #0f172a;
            font-weight: 850;
            text-align: left;
        }

        .nik-work-legacy-nav .nik-work-user-menu-panel button:hover {
            background: rgba(219, 234, 254, .68);
            color: #0b64d8;
        }

        .nik-work-legacy-mobile-head,
        .nik-work-legacy-nav .nik-work-mobile-bottom-bar,
        .nik-work-legacy-nav .nik-work-mobile-sheet,
        .nik-work-legacy-nav .nik-work-mobile-sheet-backdrop {
            display: none;
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

        @media (max-width: 900px) {
            html.nik-work-mobile-sheet-open,
            html.nik-work-mobile-sheet-open body {
                overflow: hidden;
            }

            body:has(.nik-work-legacy-nav) .fi-main {
                width: 100%;
                margin-left: 0;
                padding: 104px 16px calc(150px + env(safe-area-inset-bottom));
            }

            body:has(.nik-work-legacy-nav) .fi-page {
                gap: 18px;
            }

            body:has(.nik-work-legacy-nav) .fi-header {
                margin-top: 0;
            }

            .nik-work-legacy-nav .nik-work-sidebar,
            .nik-work-legacy-actions {
                display: none;
            }

            .nik-work-legacy-mobile-head {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 70;
                display: flex;
                height: 84px;
                align-items: center;
                justify-content: space-between;
                padding: calc(14px + env(safe-area-inset-top)) 16px 12px;
                border-bottom: 1px solid rgba(191, 219, 254, .58);
                background: rgba(247, 251, 255, .78);
                box-shadow: 0 18px 44px rgba(31, 80, 143, .08);
                backdrop-filter: blur(22px) saturate(1.12);
            }

            .nik-work-legacy-mobile-head > div {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .nik-work-legacy-mobile-logo img {
                display: block;
                width: 138px;
                height: auto;
            }

            .nik-work-legacy-icon-button,
            .nik-work-legacy-avatar,
            .nik-work-user-menu > .nik-work-legacy-avatar {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .nik-work-legacy-avatar img,
            .nik-work-legacy-avatar > span:first-child {
                width: 38px;
                height: 38px;
                border-radius: 13px;
            }

            .nik-work-legacy-nav .nik-work-mobile-bottom-bar {
                position: fixed;
                right: 16px;
                bottom: calc(16px + env(safe-area-inset-bottom));
                left: 16px;
                z-index: 72;
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                min-height: 78px;
                padding: 8px 10px;
                border: 1px solid rgba(255, 255, 255, .78);
                border-radius: 28px;
                background: rgba(255, 255, 255, .82);
                box-shadow: 0 20px 60px rgba(31, 80, 143, .18);
                backdrop-filter: blur(24px) saturate(1.12);
            }

            .nik-work-legacy-nav .nik-work-mobile-bottom-bar a,
            .nik-work-legacy-nav .nik-work-mobile-bottom-bar button {
                display: flex;
                min-width: 0;
                align-items: center;
                justify-content: center;
                gap: 3px;
                flex-direction: column;
                border: 0;
                border-radius: 22px;
                background: transparent;
                color: #64748b;
                font-size: 11px;
                font-weight: 850;
                line-height: 1.05;
                text-decoration: none;
            }

            .nik-work-legacy-nav .nik-work-mobile-bottom-bar .nik-work-svg {
                width: 24px;
                height: 24px;
            }

            .nik-work-legacy-nav .nik-work-mobile-bottom-bar a.is-active,
            .nik-work-legacy-nav .nik-work-mobile-bottom-bar button.is-active {
                background: rgba(219, 234, 254, .78);
                color: #0b70f0;
            }

            .nik-work-legacy-nav .nik-work-mobile-bottom-menu {
                position: relative;
                transform: translateY(-12px);
            }

            .nik-work-legacy-nav .nik-work-mobile-bottom-menu strong {
                display: grid;
                width: 58px;
                height: 58px;
                place-items: center;
                border-radius: 50%;
                background: linear-gradient(135deg, #2f8af7, #0b64d8);
                color: #fff;
                box-shadow: 0 16px 36px rgba(29, 124, 242, .34);
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-backdrop {
                position: fixed;
                inset: 0;
                z-index: 80;
                display: block;
                background: rgba(15, 23, 42, .22);
                backdrop-filter: blur(8px);
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 85;
                display: block;
                max-height: min(82vh, 760px);
                overflow: auto;
                padding: 18px 18px calc(112px + env(safe-area-inset-bottom));
                border: 1px solid rgba(255, 255, 255, .78);
                border-radius: 32px 32px 0 0;
                background:
                    linear-gradient(145deg, rgba(255, 255, 255, .90), rgba(232, 244, 255, .76)),
                    radial-gradient(circle at 12% 0%, rgba(255, 255, 255, .96), transparent 38%);
                box-shadow: 0 -22px 70px rgba(31, 80, 143, .20);
                backdrop-filter: blur(26px) saturate(1.14);
                transform: translateY(100%);
                opacity: 0;
                transition: transform .22s ease, opacity .22s ease;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet[hidden] {
                display: none;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-handle {
                width: 54px;
                height: 6px;
                margin: 0 auto 20px;
                border-radius: 999px;
                background: rgba(100, 116, 139, .26);
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 18px;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-head span,
            .nik-work-legacy-nav .nik-work-mobile-menu-groups h3 {
                color: #64748b;
                font-size: 13px;
                font-weight: 850;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-head h2 {
                margin: 2px 0 0;
                color: #0f172a;
                font-size: 34px;
                line-height: 1.02;
                font-weight: 900;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-head button {
                display: grid;
                width: 58px;
                height: 58px;
                place-items: center;
                border: 0;
                border-radius: 50%;
                background: rgba(255, 255, 255, .82);
                color: #64748b;
                box-shadow: 0 14px 36px rgba(31, 80, 143, .12);
                transform: rotate(45deg);
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-tabs,
            .nik-work-legacy-nav .nik-work-mobile-sheet-actions,
            .nik-work-legacy-nav .nik-work-mobile-request-stats {
                display: grid;
                gap: 10px;
                margin-bottom: 14px;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-tabs {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-tabs span,
            .nik-work-legacy-nav .nik-work-mobile-sheet-actions a,
            .nik-work-legacy-nav .nik-work-mobile-menu-list a,
            .nik-work-legacy-nav .nik-work-mobile-menu-list button,
            .nik-work-legacy-nav .nik-work-mobile-menu-list > span,
            .nik-work-legacy-nav .nik-work-mobile-request-row,
            .nik-work-legacy-nav .nik-work-mobile-request-empty,
            .nik-work-legacy-nav .nik-work-mobile-sheet-placeholder {
                border: 1px solid rgba(191, 219, 254, .62);
                border-radius: 20px;
                background: rgba(255, 255, 255, .66);
                color: #0f172a;
                text-decoration: none;
                box-shadow: 0 12px 32px rgba(31, 80, 143, .08);
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-tabs span {
                display: grid;
                min-height: 44px;
                place-items: center;
                font-weight: 850;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-tabs span.is-active {
                background: rgba(219, 234, 254, .82);
                color: #0b70f0;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-grid {
                display: grid;
                gap: 12px;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-placeholder {
                display: grid;
                min-height: 132px;
                place-items: center;
                padding: 18px;
                text-align: center;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-placeholder strong,
            .nik-work-legacy-nav .nik-work-mobile-request-empty strong {
                font-size: 18px;
                font-weight: 900;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-placeholder p,
            .nik-work-legacy-nav .nik-work-mobile-request-empty span {
                margin: 4px 0 0;
                color: #64748b;
                font-weight: 700;
            }

            .nik-work-legacy-nav .nik-work-mobile-menu-groups {
                display: grid;
                gap: 16px;
            }

            .nik-work-legacy-nav .nik-work-mobile-menu-list {
                display: grid;
                gap: 8px;
                margin-top: 8px;
            }

            .nik-work-legacy-nav .nik-work-mobile-menu-list a,
            .nik-work-legacy-nav .nik-work-mobile-menu-list button,
            .nik-work-legacy-nav .nik-work-mobile-menu-list > span {
                display: grid;
                grid-template-columns: 44px minmax(0, 1fr) auto;
                min-height: 58px;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                font-weight: 850;
                text-align: left;
            }

            .nik-work-legacy-nav .nik-work-mobile-menu-list a.is-active {
                background: rgba(219, 234, 254, .82);
                color: #0b70f0;
            }

            .nik-work-legacy-nav .nik-work-mobile-menu-list a > span,
            .nik-work-legacy-nav .nik-work-mobile-menu-list button > span,
            .nik-work-legacy-nav .nik-work-mobile-menu-list > span > span,
            .nik-work-legacy-nav .nik-work-mobile-request-icon {
                display: grid;
                width: 42px;
                height: 42px;
                place-items: center;
                border-radius: 15px;
                background: rgba(219, 234, 254, .72);
                color: #0b70f0;
                font-size: 20px;
            }

            .nik-work-legacy-nav .nik-work-mobile-menu-list em {
                padding: 5px 8px;
                border-radius: 999px;
                background: rgba(241, 245, 249, .9);
                color: #64748b;
                font-size: 11px;
                font-style: normal;
                font-weight: 850;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .nik-work-legacy-nav .nik-work-mobile-request-stats a {
                min-height: 94px;
                padding: 16px;
                border: 1px solid rgba(191, 219, 254, .62);
                border-radius: 22px;
                background: rgba(255, 255, 255, .66);
                color: #0f172a;
                text-decoration: none;
                box-shadow: 0 12px 32px rgba(31, 80, 143, .08);
            }

            .nik-work-legacy-nav .nik-work-mobile-request-stats span {
                color: #64748b;
                font-weight: 850;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-stats strong {
                display: block;
                margin-top: 10px;
                font-size: 34px;
                line-height: 1;
                font-weight: 900;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-actions a {
                display: inline-flex;
                min-height: 54px;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 0 12px;
                color: #0b70f0;
                font-weight: 900;
            }

            .nik-work-legacy-nav .nik-work-mobile-sheet-actions a:first-child {
                background: linear-gradient(135deg, #2f8af7, #0b64d8);
                color: #fff;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-list {
                display: grid;
                gap: 10px;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-row {
                display: grid;
                grid-template-columns: 42px minmax(0, 1fr) auto 22px;
                align-items: center;
                gap: 10px;
                min-height: 72px;
                padding: 10px;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-row strong,
            .nik-work-legacy-nav .nik-work-mobile-request-row small {
                display: block;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-row small {
                margin-top: 3px;
                color: #64748b;
                font-weight: 700;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-row em {
                max-width: 108px;
                padding: 6px 8px;
                border-radius: 999px;
                background: rgba(219, 234, 254, .76);
                color: #0b64d8;
                font-size: 11px;
                font-style: normal;
                font-weight: 900;
                text-align: center;
            }

            .nik-work-legacy-nav .nik-work-mobile-request-row.is-green em { background: rgba(220, 252, 231, .82); color: #15803d; }
            .nik-work-legacy-nav .nik-work-mobile-request-row.is-red em { background: rgba(254, 226, 226, .82); color: #dc2626; }
            .nik-work-legacy-nav .nik-work-mobile-request-row.is-amber em { background: rgba(254, 243, 199, .86); color: #b45309; }
            .nik-work-legacy-nav .nik-work-mobile-request-row.is-gray em { background: rgba(241, 245, 249, .9); color: #64748b; }

            .nik-work-legacy-nav .nik-work-mobile-request-empty {
                display: grid;
                min-height: 160px;
                place-items: center;
                padding: 22px;
                text-align: center;
                border-style: dashed;
            }

            .nik-work-legacy-nav .nik-work-user-menu-panel {
                top: auto;
                right: 0;
                bottom: calc(100% + 10px);
            }
        }
    </style>
@endonce
