@once
    <style>
        body.nik-employee-edit-page {
            background:
                radial-gradient(circle at top left, rgba(29, 124, 242, .16), transparent 34%),
                radial-gradient(circle at 82% 8%, rgba(14, 165, 233, .10), transparent 30%),
                linear-gradient(180deg, #f6fbff 0%, #eef7ff 48%, #f8fbff 100%);
        }

        body.nik-employee-edit-page .fi-layout,
        body.nik-employee-edit-page .fi-main,
        body.nik-employee-edit-page .fi-page,
        body.nik-employee-edit-page .fi-page-content {
            background: transparent;
        }

        body.nik-employee-edit-page .fi-main {
            padding-top: 22px;
        }

        body.nik-employee-edit-page .fi-page {
            gap: 18px;
        }

        body.nik-employee-edit-page .fi-header {
            position: relative;
            overflow: hidden;
            padding: 26px 28px;
            border: 1px solid rgba(255, 255, 255, .66);
            border-radius: 30px;
            background: rgba(255, 255, 255, .68);
            box-shadow: 0 24px 70px rgba(31, 80, 143, .12);
            backdrop-filter: blur(24px);
        }

        body.nik-employee-edit-page .fi-header::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 8% 10%, rgba(29, 124, 242, .12), transparent 28%),
                radial-gradient(circle at 86% 0%, rgba(6, 182, 212, .10), transparent 30%);
        }

        body.nik-employee-edit-page .fi-header > * {
            position: relative;
        }

        body.nik-employee-edit-page .fi-header-heading {
            color: #0f172a;
            font-size: clamp(28px, 3vw, 42px);
            line-height: 1.05;
            font-weight: 850;
            letter-spacing: 0;
        }

        body.nik-employee-edit-page .fi-breadcrumbs,
        body.nik-employee-edit-page .fi-breadcrumbs a,
        body.nik-employee-edit-page .fi-breadcrumbs-item {
            color: #64748b;
            font-weight: 750;
        }

        body.nik-employee-edit-page .fi-ac .fi-btn,
        body.nik-employee-edit-page .fi-form-actions .fi-btn {
            min-height: 42px;
            border-radius: 15px;
            font-weight: 850;
        }

        body.nik-employee-edit-page .fi-section,
        body.nik-employee-edit-page .fi-fo-field-wrp,
        body.nik-employee-edit-page .fi-sc-component-ctn > .fi-sc-component {
            --tw-ring-shadow: 0 0 #0000;
        }

        body.nik-employee-edit-page .fi-section {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .66);
            border-radius: 28px;
            background: rgba(255, 255, 255, .72);
            box-shadow: 0 22px 62px rgba(31, 80, 143, .10);
            backdrop-filter: blur(22px);
        }

        body.nik-employee-edit-page .fi-section-header {
            padding: 18px 22px;
            border-bottom: 1px solid rgba(226, 232, 240, .65);
            background: rgba(248, 251, 255, .48);
        }

        body.nik-employee-edit-page .fi-section-header-heading {
            color: #0f172a;
            font-size: 16px;
            font-weight: 850;
        }

        body.nik-employee-edit-page .fi-section-header-description {
            color: #64748b;
            font-weight: 700;
        }

        body.nik-employee-edit-page .fi-section-content {
            padding: 22px;
        }

        body.nik-employee-edit-page .fi-input-wrp,
        body.nik-employee-edit-page .fi-select-input,
        body.nik-employee-edit-page .fi-ta-ctn {
            border-radius: 16px;
            border-color: rgba(148, 163, 184, .24);
            background: rgba(255, 255, 255, .78);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72);
        }

        body.nik-employee-edit-page .fi-input,
        body.nik-employee-edit-page .fi-select-input,
        body.nik-employee-edit-page .fi-ta {
            color: #0f172a;
            font-weight: 700;
        }

        body.nik-employee-edit-page .fi-fo-field-wrp-label,
        body.nik-employee-edit-page .fi-fo-field-wrp-label span {
            color: #334155;
            font-weight: 850;
        }

        body.nik-employee-edit-page .fi-fo-field-wrp-helper-text,
        body.nik-employee-edit-page .fi-fo-field-wrp-error-message {
            font-weight: 750;
        }

        body.nik-employee-edit-page .fi-form-actions {
            position: sticky;
            bottom: 18px;
            z-index: 20;
            width: fit-content;
            max-width: 100%;
            margin-top: 18px;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, .66);
            border-radius: 20px;
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 18px 50px rgba(31, 80, 143, .14);
            backdrop-filter: blur(20px);
        }

        body.nik-employee-edit-page .fi-sidebar {
            background: rgba(255, 255, 255, .74);
            border-right: 1px solid rgba(255, 255, 255, .62);
            box-shadow: 18px 0 60px rgba(31, 80, 143, .08);
            backdrop-filter: blur(22px);
        }

        @media (max-width: 900px) {
            body.nik-employee-edit-page .fi-main {
                padding: 12px;
            }

            body.nik-employee-edit-page .fi-header {
                padding: 20px;
                border-radius: 26px;
            }

            body.nik-employee-edit-page .fi-header-heading {
                font-size: 28px;
            }

            body.nik-employee-edit-page .fi-section {
                border-radius: 24px;
            }

            body.nik-employee-edit-page .fi-section-content {
                padding: 16px;
            }

            body.nik-employee-edit-page .fi-form-actions {
                left: 12px;
                right: 12px;
                bottom: 12px;
                width: auto;
                justify-content: stretch;
            }
        }

        body.nik-employee-edit-page {
            background:
                radial-gradient(circle at 78% 18%, rgba(244, 183, 126, .30), transparent 21rem),
                radial-gradient(circle at 92% 42%, rgba(114, 176, 237, .36), transparent 25rem),
                radial-gradient(circle at 10% 84%, rgba(73, 148, 226, .30), transparent 27rem),
                linear-gradient(112deg, transparent 0 18%, rgba(255, 255, 255, .46) 30%, transparent 43% 100%),
                linear-gradient(135deg, #f7fbff 0%, #eef6ff 46%, #f7fbff 100%);
            background-attachment: fixed;
        }

        body.nik-employee-edit-page .fi-header,
        body.nik-employee-edit-page .fi-section,
        body.nik-employee-edit-page .fi-form-actions,
        body.nik-employee-edit-page .fi-sidebar {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-color: rgba(255, 255, 255, .78);
            background:
                radial-gradient(circle at 18% 0%, rgba(255, 255, 255, .52), transparent 35%),
                radial-gradient(circle at 92% 100%, rgba(22, 119, 255, .18), transparent 42%),
                radial-gradient(circle at 0% 100%, rgba(34, 197, 94, .10), transparent 34%),
                linear-gradient(135deg, rgba(255, 255, 255, .34), rgba(226, 246, 255, .16) 48%, rgba(255, 255, 255, .25));
            background-attachment: fixed;
            box-shadow:
                0 34px 92px rgba(20, 82, 148, .18),
                0 14px 38px rgba(22, 119, 255, .12),
                inset 0 1px 0 rgba(255, 255, 255, .96),
                inset 0 -22px 42px rgba(22, 119, 255, .10);
            backdrop-filter: blur(34px) saturate(205%) contrast(1.06);
            -webkit-backdrop-filter: blur(34px) saturate(205%) contrast(1.06);
        }

        body.nik-employee-edit-page .fi-header::before,
        body.nik-employee-edit-page .fi-section::before,
        body.nik-employee-edit-page .fi-form-actions::before,
        body.nik-employee-edit-page .fi-sidebar::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            pointer-events: none;
            background:
                radial-gradient(circle at 18% 0%, rgba(255, 255, 255, .92), transparent 19%),
                linear-gradient(126deg, rgba(255, 255, 255, .76) 0 7%, rgba(255, 255, 255, .30) 8% 18%, rgba(255, 255, 255, 0) 42%),
                linear-gradient(112deg, transparent 0 45%, rgba(255, 255, 255, .28) 48%, transparent 56%);
            mix-blend-mode: screen;
            opacity: .90;
        }

        body.nik-employee-edit-page .fi-header > *,
        body.nik-employee-edit-page .fi-section > *,
        body.nik-employee-edit-page .fi-form-actions > *,
        body.nik-employee-edit-page .fi-sidebar > * {
            position: relative;
            z-index: 1;
        }

        body.nik-employee-edit-page .fi-input-wrp,
        body.nik-employee-edit-page .fi-select-input,
        body.nik-employee-edit-page .fi-ta-ctn {
            border-color: rgba(255, 255, 255, .62);
            background:
                radial-gradient(circle at 24% 0%, rgba(255, 255, 255, .78), transparent 42%),
                linear-gradient(135deg, rgba(255, 255, 255, .42), rgba(232, 247, 255, .20));
            background-attachment: fixed;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .82),
                inset 0 -1px 0 rgba(22, 119, 255, .08);
            backdrop-filter: blur(22px) saturate(170%);
            -webkit-backdrop-filter: blur(22px) saturate(170%);
        }

        @media (max-width: 900px) {
            html,
            body.nik-employee-edit-page {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }

            body.nik-employee-edit-page .fi-layout {
                display: block;
                width: 100%;
                max-width: 100vw;
                overflow-x: hidden;
            }

            body.nik-employee-edit-page .fi-sidebar.fi-main-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: min(16rem, 84vw);
                max-width: 84vw;
                transform: translateX(-100%);
                pointer-events: none;
            }

            body.nik-employee-edit-page .fi-main,
            body.nik-employee-edit-page .fi-page,
            body.nik-employee-edit-page .fi-page-content {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin-inline: 0;
                transform: none;
            }

            body.nik-employee-edit-page .fi-main {
                padding: 12px;
            }

            body.nik-employee-edit-page .fi-page-content,
            body.nik-employee-edit-page .fi-section-content,
            body.nik-employee-edit-page .fi-sc-component-ctn,
            body.nik-employee-edit-page .fi-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            body.nik-employee-edit-page .fi-section,
            body.nik-employee-edit-page .fi-section-content,
            body.nik-employee-edit-page .fi-sc-component,
            body.nik-employee-edit-page .fi-fo-field-wrp,
            body.nik-employee-edit-page .fi-input-wrp,
            body.nik-employee-edit-page .fi-select-input {
                min-width: 0;
                max-width: 100%;
            }

            body.nik-employee-edit-page [style*="grid-template-columns: repeat(2"] {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>
@endonce
