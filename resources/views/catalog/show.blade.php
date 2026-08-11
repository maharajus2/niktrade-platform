@extends('layouts.public')

@section('title', $product->seo_title ?: $product->name)

@if ($product->seo_description ?: $product->short_description)
    @section('meta_description', $product->seo_description ?: $product->short_description)
@endif

@push('styles')
    <style>
        body {
            min-width: 320px;
            background:
                radial-gradient(circle at 10% 4%, rgba(10, 132, 255, .24), transparent 25rem),
                radial-gradient(circle at 88% 10%, rgba(33, 201, 139, .20), transparent 24rem),
                radial-gradient(circle at 66% 88%, rgba(125, 211, 252, .24), transparent 30rem),
                linear-gradient(112deg, transparent 0 17%, rgba(255, 255, 255, .52) 30%, transparent 45% 100%),
                linear-gradient(135deg, #f8fcff 0%, #e9f7ff 44%, #fbfeff 100%);
            background-attachment: fixed;
            color: #10223f;
        }

        .public-header {
            position: sticky;
            top: 0;
            z-index: 30;
            border-bottom: 1px solid rgba(255, 255, 255, .72);
            background:
                radial-gradient(circle at 20% 0%, rgba(255, 255, 255, .94), transparent 44%),
                linear-gradient(135deg, rgba(255, 255, 255, .72), rgba(232, 247, 255, .42));
            box-shadow: 0 18px 46px rgba(20, 82, 148, .10), inset 0 1px 0 rgba(255, 255, 255, .92);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
        }

        .public-header__link,
        .public-header__logout,
        .public-header__cart {
            border: 1px solid rgba(255, 255, 255, .58);
            background:
                radial-gradient(circle at 22% 0%, rgba(255, 255, 255, .80), transparent 46%),
                linear-gradient(135deg, rgba(255, 255, 255, .52), rgba(232, 247, 255, .30));
            color: #10223f;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .78);
        }

        .public-header__cart {
            color: #006eea;
        }

        .nik-product-page,
        .nik-product-page * {
            box-sizing: border-box;
        }

        .nik-product-page {
            --liquid-blue: #0a84ff;
            --liquid-blue-deep: #006eea;
            --liquid-green: #21c98b;
            --liquid-ink: #10223f;
            --liquid-muted: rgba(16, 34, 63, .62);
            --liquid-border: rgba(255, 255, 255, .78);
            --liquid-shadow: 0 32px 92px rgba(20, 82, 148, .17), 0 14px 38px rgba(10, 132, 255, .11), 0 1px 0 rgba(255, 255, 255, .70);
            position: relative;
            isolation: isolate;
            overflow: hidden;
            min-height: calc(100vh - 64px);
            padding: 22px 0 56px;
        }

        .nik-product-page::before,
        .nik-product-page::after {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            content: "";
        }

        .nik-product-page::before {
            background:
                radial-gradient(circle at 2% 14%, rgba(255, 255, 255, .82) 0 18px, transparent 19px),
                radial-gradient(circle at 14% 70%, rgba(255, 255, 255, .46) 0 34px, transparent 36px),
                radial-gradient(circle at 62% 36%, rgba(255, 255, 255, .30) 0 54px, transparent 56px),
                radial-gradient(circle at 96% 18%, rgba(255, 255, 255, .58) 0 22px, transparent 23px),
                linear-gradient(118deg, rgba(255, 255, 255, .26), transparent 24%, rgba(33, 201, 139, .08) 64%, transparent),
                repeating-linear-gradient(105deg, rgba(255, 255, 255, .10) 0 1px, transparent 1px 42px);
            opacity: .92;
        }

        .nik-product-page::after {
            background-image:
                radial-gradient(ellipse at 23% 20%, rgba(255, 255, 255, .72), transparent 23rem),
                radial-gradient(ellipse at 78% 27%, rgba(10, 132, 255, .16), transparent 26rem),
                radial-gradient(ellipse at 58% 64%, rgba(33, 201, 139, .12), transparent 30rem),
                linear-gradient(120deg, rgba(255, 255, 255, .34), transparent 18%);
            filter: blur(18px);
            opacity: .90;
        }

        .nik-product-shell {
            width: min(1240px, calc(100% - 48px));
            margin: 0 auto;
        }

        .nik-product-glass {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--liquid-border);
            background:
                radial-gradient(circle at 18% 0%, rgba(255, 255, 255, .50), transparent 35%),
                radial-gradient(circle at 90% 100%, rgba(10, 132, 255, .18), transparent 42%),
                radial-gradient(circle at 0% 100%, rgba(33, 201, 139, .11), transparent 34%),
                linear-gradient(135deg, rgba(255, 255, 255, .36), rgba(226, 246, 255, .18) 48%, rgba(255, 255, 255, .25));
            background-attachment: fixed;
            box-shadow:
                var(--liquid-shadow),
                0 0 0 1px rgba(255, 255, 255, .22),
                inset 0 1px 0 rgba(255, 255, 255, .96),
                inset 1px 0 0 rgba(255, 255, 255, .48),
                inset 0 -22px 42px rgba(10, 132, 255, .09),
                inset 0 0 38px rgba(255, 255, 255, .18);
            backdrop-filter: blur(34px) saturate(195%) contrast(1.06);
            -webkit-backdrop-filter: blur(34px) saturate(195%) contrast(1.06);
            isolation: isolate;
        }

        .nik-product-glass::before,
        .nik-product-glass::after {
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            pointer-events: none;
            content: "";
        }

        .nik-product-glass::before {
            background:
                radial-gradient(circle at 18% 0%, rgba(255, 255, 255, .88), transparent 19%),
                linear-gradient(126deg, rgba(255, 255, 255, .68) 0 7%, rgba(255, 255, 255, .26) 8% 18%, rgba(255, 255, 255, 0) 42%),
                linear-gradient(112deg, transparent 0 45%, rgba(255, 255, 255, .26) 48%, transparent 56%),
                linear-gradient(315deg, rgba(10, 132, 255, .11), transparent 44%);
            mix-blend-mode: screen;
            opacity: .88;
        }

        .nik-product-glass::after {
            inset: 1px;
            background:
                radial-gradient(ellipse at 50% 106%, rgba(10, 132, 255, .16), transparent 51%),
                linear-gradient(to top, rgba(255, 255, 255, .30), rgba(255, 255, 255, 0) 40%),
                linear-gradient(90deg, rgba(255, 255, 255, .22), transparent 18%, transparent 82%, rgba(78, 169, 255, .16));
            box-shadow:
                inset 0 0 0 1px rgba(255, 255, 255, .20),
                inset 0 0 32px rgba(255, 255, 255, .22),
                inset 0 -28px 52px rgba(10, 132, 255, .10);
            opacity: .82;
        }

        .nik-product-glass > * {
            position: relative;
            z-index: 1;
        }

        .nik-product-breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 18px;
            color: rgba(16, 34, 63, .58);
            font-size: 13px;
            font-weight: 700;
        }

        .nik-product-breadcrumbs a {
            text-decoration: none;
        }

        .nik-product-breadcrumbs a:hover {
            color: var(--liquid-blue-deep);
        }

        .nik-product-breadcrumbs span {
            max-width: min(420px, 100%);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nik-product-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.02fr) minmax(360px, .98fr);
            gap: 24px;
            align-items: start;
        }

        .nik-product-gallery {
            border-radius: 28px;
            padding: 22px;
        }

        .nik-product-main-image,
        .nik-product-placeholder {
            display: grid;
            place-items: center;
            min-height: 560px;
            overflow: hidden;
            border-radius: 22px;
            background:
                radial-gradient(circle at 45% 12%, rgba(255, 255, 255, .68), transparent 34%),
                linear-gradient(135deg, rgba(255, 255, 255, .34), rgba(218, 244, 255, .20));
        }

        .nik-product-main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 18px;
            filter: drop-shadow(0 22px 34px rgba(26, 83, 140, .12));
        }

        .nik-product-placeholder {
            color: rgba(16, 34, 63, .48);
            font-weight: 800;
        }

        .nik-product-mobile-gallery {
            display: none;
        }

        .nik-product-thumbs {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .nik-product-thumb {
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .72);
            border-radius: 16px;
            background:
                radial-gradient(circle at 20% 0%, rgba(255, 255, 255, .80), transparent 46%),
                linear-gradient(135deg, rgba(255, 255, 255, .48), rgba(231, 247, 255, .26));
            aspect-ratio: 1;
            box-shadow: 0 12px 28px rgba(39, 99, 159, .10), inset 0 1px 0 rgba(255, 255, 255, .84);
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease;
        }

        .nik-product-thumb:hover,
        .nik-product-thumb:focus-visible {
            border-color: rgba(10, 132, 255, .42);
            outline: 0;
            transform: translateY(-1px);
        }

        .nik-product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nik-product-summary {
            display: grid;
            gap: 16px;
        }

        .nik-product-info {
            border-radius: 28px;
            padding: 28px;
        }

        .nik-product-brand {
            margin: 0 0 10px;
            color: var(--liquid-blue-deep);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .nik-product-title {
            margin: 0;
            color: var(--liquid-ink);
            font-size: clamp(2.15rem, 4vw, 4rem);
            font-weight: 900;
            line-height: .98;
            letter-spacing: 0;
        }

        .nik-product-intro {
            margin: 16px 0 0;
            color: rgba(16, 34, 63, .70);
            font-size: 1.02rem;
            line-height: 1.65;
            white-space: pre-line;
        }

        .nik-product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .nik-product-chip,
        .nik-product-status {
            display: inline-flex;
            min-height: 34px;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, .66);
            border-radius: 999px;
            background:
                radial-gradient(circle at 22% 0%, rgba(255, 255, 255, .82), transparent 46%),
                linear-gradient(135deg, rgba(255, 255, 255, .50), rgba(232, 247, 255, .30));
            color: rgba(16, 34, 63, .72);
            font-size: 13px;
            font-weight: 800;
            padding: 7px 12px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .76);
        }

        .nik-product-status {
            color: #087443;
        }

        .nik-product-status.is-out {
            color: #a16207;
        }

        .nik-product-status.is-discontinued {
            color: #b91c1c;
        }

        .nik-product-barcode {
            display: grid;
            gap: 7px;
            width: min(280px, 100%);
            margin-top: 18px;
        }

        .barcode-svg {
            width: 100%;
            height: 72px;
            border: 1px solid rgba(255, 255, 255, .76);
            border-radius: 14px;
            background: rgba(255, 255, 255, .66);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .90);
        }

        .nik-product-barcode-value {
            color: rgba(16, 34, 63, .54);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
        }

        .nik-product-buy {
            display: grid;
            gap: 18px;
            border-radius: 24px;
            padding: 22px;
        }

        .nik-product-price {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: baseline;
        }

        .nik-product-current-price {
            color: var(--liquid-blue-deep);
            font-size: clamp(2rem, 3vw, 2.8rem);
            font-weight: 900;
            line-height: 1;
        }

        .nik-product-old-price {
            color: rgba(16, 34, 63, .42);
            font-size: 1.08rem;
            font-weight: 800;
            text-decoration: line-through;
        }

        .nik-product-discount {
            display: inline-flex;
            min-height: 30px;
            align-items: center;
            border-radius: 999px;
            background: linear-gradient(180deg, #2a9dff, #006eea);
            color: #fff;
            font-size: .85rem;
            font-weight: 900;
            padding: 5px 10px;
            box-shadow: 0 12px 28px rgba(0, 112, 235, .26), inset 0 1px 0 rgba(255, 255, 255, .36);
        }

        .nik-product-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .nik-product-button,
        .nik-product-cart-link,
        .nik-product-quantity-button,
        .nik-product-doc-link {
            border: 1px solid rgba(255, 255, 255, .58);
            font: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .nik-product-button,
        .nik-product-cart-link {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-weight: 900;
            padding: 12px 18px;
        }

        .nik-product-button {
            min-width: 188px;
            background:
                radial-gradient(circle at 24% 0%, rgba(255, 255, 255, .46), transparent 34%),
                linear-gradient(180deg, #2a9dff, #006eea);
            color: #fff;
            box-shadow: 0 18px 38px rgba(0, 112, 235, .30), inset 0 1px 0 rgba(255, 255, 255, .52);
        }

        .nik-product-button:disabled {
            background:
                radial-gradient(circle at 22% 0%, rgba(255, 255, 255, .40), transparent 40%),
                linear-gradient(180deg, rgba(127, 143, 164, .72), rgba(92, 108, 132, .70));
            color: rgba(255, 255, 255, .90);
            cursor: not-allowed;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .32);
        }

        .nik-product-cart-link,
        .nik-product-doc-link {
            background:
                radial-gradient(circle at 22% 0%, rgba(255, 255, 255, .82), transparent 46%),
                linear-gradient(135deg, rgba(255, 255, 255, .52), rgba(232, 247, 255, .30));
            color: var(--liquid-blue-deep);
            box-shadow: 0 12px 30px rgba(39, 99, 159, .12), inset 0 1px 0 rgba(255, 255, 255, .86);
        }

        .nik-product-button:not(:disabled):hover,
        .nik-product-cart-link:hover,
        .nik-product-doc-link:hover,
        .nik-product-quantity-button:hover {
            border-color: rgba(10, 132, 255, .36);
            box-shadow: 0 18px 38px rgba(0, 112, 235, .18), inset 0 1px 0 rgba(255, 255, 255, .88);
            transform: translateY(-1px);
        }

        .nik-product-quantity {
            display: inline-grid;
            grid-template-columns: 46px minmax(52px, auto) 46px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .68);
            border-radius: 16px;
            background:
                radial-gradient(circle at 22% 0%, rgba(255, 255, 255, .80), transparent 46%),
                linear-gradient(135deg, rgba(255, 255, 255, .50), rgba(232, 247, 255, .30));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .82);
        }

        .nik-product-quantity-form {
            display: contents;
        }

        .nik-product-quantity-button,
        .nik-product-quantity-value {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
        }

        .nik-product-quantity-button {
            border: 0;
            background: rgba(255, 255, 255, .22);
            color: var(--liquid-blue-deep);
            font-size: 1.25rem;
            font-weight: 900;
        }

        .nik-product-quantity-value {
            border-inline: 1px solid rgba(255, 255, 255, .62);
            color: var(--liquid-ink);
            font-weight: 900;
            padding: 0 14px;
        }

        .nik-product-alert {
            width: fit-content;
            border: 1px solid rgba(255, 255, 255, .68);
            border-radius: 16px;
            background:
                radial-gradient(circle at 18% 0%, rgba(255, 255, 255, .78), transparent 46%),
                linear-gradient(135deg, rgba(234, 255, 247, .66), rgba(226, 246, 255, .34));
            color: #087443;
            font-weight: 800;
            padding: 11px 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .86);
        }

        .nik-product-specs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            border-radius: 24px;
            padding: 18px;
        }

        .nik-product-spec {
            min-width: 0;
            border-top: 1px solid rgba(255, 255, 255, .56);
            padding: 12px 4px 2px;
        }

        .nik-product-spec:nth-child(-n + 2) {
            border-top: 0;
        }

        .nik-product-spec-label {
            color: rgba(16, 34, 63, .54);
            font-size: .82rem;
            font-weight: 700;
        }

        .nik-product-spec-value {
            margin-top: 5px;
            color: var(--liquid-ink);
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .nik-product-sections {
            display: grid;
            gap: 18px;
            margin-top: 24px;
        }

        .nik-product-section {
            border-radius: 24px;
            padding: 24px;
        }

        .nik-product-section h2 {
            margin: 0 0 12px;
            color: var(--liquid-ink);
            font-size: 1.25rem;
            line-height: 1.2;
        }

        .nik-product-section p {
            margin: 0;
            color: rgba(16, 34, 63, .70);
            line-height: 1.7;
            white-space: pre-line;
        }

        .nik-product-docs {
            display: grid;
            gap: 12px;
        }

        .nik-product-doc {
            display: grid;
            gap: 7px;
            border-top: 1px solid rgba(255, 255, 255, .58);
            padding-top: 14px;
        }

        .nik-product-doc:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .nik-product-doc-title {
            color: var(--liquid-ink);
            font-weight: 900;
        }

        .nik-product-doc-meta {
            color: rgba(16, 34, 63, .58);
            font-size: .92rem;
            line-height: 1.45;
        }

        .nik-product-doc-link {
            display: inline-flex;
            width: fit-content;
            min-height: 38px;
            align-items: center;
            border-radius: 14px;
            font-weight: 900;
            padding: 9px 13px;
        }

        .nik-product-status-active {
            color: #087443;
            font-weight: 900;
        }

        .nik-product-status-soon {
            color: #a16207;
            font-weight: 900;
        }

        .nik-product-status-expired {
            color: #b91c1c;
            font-weight: 900;
        }

        @media (max-width: 1024px) {
            .nik-product-layout {
                grid-template-columns: 1fr;
            }

            .nik-product-main-image,
            .nik-product-placeholder {
                min-height: 440px;
            }
        }

        @media (max-width: 640px) {
            .public-header__inner {
                width: min(100% - 24px, 1180px);
            }

            .public-header__nav {
                gap: 8px;
                flex-wrap: wrap;
                overflow: visible;
                justify-content: flex-start;
            }

            .public-header__link,
            .public-header__logout,
            .public-header__cart {
                white-space: nowrap;
            }

            .nik-product-page {
                padding: 16px 0 calc(28px + env(safe-area-inset-bottom));
            }

            .nik-product-shell {
                width: min(100% - 24px, 1240px);
            }

            .nik-product-breadcrumbs {
                gap: 6px;
                margin-bottom: 12px;
                font-size: 12px;
            }

            .nik-product-breadcrumbs span {
                max-width: 210px;
            }

            .nik-product-gallery,
            .nik-product-info,
            .nik-product-buy,
            .nik-product-specs,
            .nik-product-section {
                border-radius: 20px;
            }

            .nik-product-gallery {
                padding: 14px;
            }

            .nik-product-main-image {
                display: none;
            }

            .nik-product-mobile-gallery {
                display: grid;
                grid-auto-columns: 100%;
                grid-auto-flow: column;
                gap: 12px;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
            }

            .nik-product-mobile-gallery::-webkit-scrollbar {
                display: none;
            }

            .nik-product-mobile-slide {
                display: grid;
                place-items: center;
                min-height: clamp(300px, 68vw, 410px);
                overflow: hidden;
                border-radius: 18px;
                background:
                    radial-gradient(circle at 45% 12%, rgba(255, 255, 255, .68), transparent 34%),
                    linear-gradient(135deg, rgba(255, 255, 255, .34), rgba(218, 244, 255, .20));
                scroll-snap-align: center;
            }

            .nik-product-mobile-slide img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                padding: 14px;
                filter: drop-shadow(0 18px 28px rgba(26, 83, 140, .12));
            }

            .nik-product-placeholder {
                min-height: 320px;
            }

            .nik-product-thumbs {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                padding-bottom: 2px;
                scrollbar-width: none;
            }

            .nik-product-thumbs::-webkit-scrollbar {
                display: none;
            }

            .nik-product-thumb {
                flex: 0 0 58px;
                width: 58px;
                height: 58px;
                border-radius: 14px;
            }

            .nik-product-info {
                padding: 22px;
            }

            .nik-product-title {
                font-size: clamp(2rem, 11vw, 3rem);
            }

            .nik-product-intro {
                font-size: .98rem;
            }

            .nik-product-buy {
                position: sticky;
                bottom: 12px;
                z-index: 10;
                padding: 16px;
            }

            .nik-product-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .nik-product-button,
            .nik-product-cart-link {
                width: 100%;
            }

            .nik-product-quantity {
                width: 100%;
                grid-template-columns: 48px minmax(0, 1fr) 48px;
            }

            .nik-product-specs {
                grid-template-columns: 1fr;
                padding: 16px;
            }

            .nik-product-spec:nth-child(-n + 2) {
                border-top: 1px solid rgba(255, 255, 255, .56);
            }

            .nik-product-spec:first-child {
                border-top: 0;
            }

            .nik-product-section {
                padding: 20px;
            }
        }

        @media (max-width: 360px) {
            .nik-product-shell {
                width: min(100% - 16px, 1240px);
            }

            .nik-product-info,
            .nik-product-section {
                padding: 18px;
            }

            .nik-product-current-price {
                font-size: 1.8rem;
            }
        }
    </style>
@endpush

@section('content')
    <main class="nik-product-page">
        @php
            $hasDiscount = $product->discounted_price !== null
                && $product->price !== null
                && $product->discounted_price < $product->price;

            $hasDiscountPercent = (float) ($product->discount_percent ?? 0) > 0;
            $directionLabel = $directions[$product->direction] ?? $product->direction;
            $availabilityLabels = [
                'in_stock' => 'В наличии',
                'out_of_stock' => 'Временно отсутствует',
                'discontinued' => 'Снят с производства',
            ];
            $availabilityStatus = $product->availability_status ?? 'in_stock';
            $availabilityLabel = $availabilityLabels[$availabilityStatus] ?? $availabilityLabels['in_stock'];
            $availabilityClass = match ($availabilityStatus) {
                'out_of_stock' => 'is-out',
                'discontinued' => 'is-discontinued',
                default => '',
            };
            $canAddToCart = $availabilityStatus === 'in_stock';
            $productVolume = \App\Support\ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit);
            $productWeight = \App\Support\ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
            $productShelfLife = \App\Support\ProductDisplayFormatter::formatShelfLife($product->shelf_life_value, $product->shelf_life_unit);
            $barcodeSvg = \App\Support\ProductDisplayFormatter::formatBarcodeSvg($product->barcode);
            $primaryPrice = $hasDiscount ? $product->discounted_price : $product->price;
        @endphp

        <div class="nik-product-shell">
            <nav class="nik-product-breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ url('/') }}">Главная</a>
                <span>/</span>
                <a href="{{ route('catalog.index') }}">Каталог</a>

                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('catalog.index', ['category' => $product->category->id]) }}">{{ $product->category->name }}</a>
                @endif

                @if ($product->brand)
                    <span>/</span>
                    <a href="{{ route('catalog.index', ['brand' => $product->brand->id]) }}">{{ $product->brand->name }}</a>
                @endif

                <span>/</span>
                <span aria-current="page">{{ $product->name }}</span>
            </nav>

            <section class="nik-product-layout">
                <div class="nik-product-gallery nik-product-glass" aria-label="Галерея товара">
                    @if ($mainImage)
                        <a
                            class="nik-product-main-image"
                            href="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                            target="_blank"
                            rel="noopener"
                            aria-label="Открыть изображение товара"
                        >
                            <img
                                src="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                                alt="{{ $mainImage->alt ?: $product->name }}"
                            >
                        </a>
                    @else
                        <div class="nik-product-placeholder">Нет изображения</div>
                    @endif

                    @if ($galleryImages->isNotEmpty())
                        <div class="nik-product-mobile-gallery" aria-label="Фотографии товара">
                            @foreach ($galleryImages as $image)
                                <a
                                    class="nik-product-mobile-slide"
                                    href="{{ Storage::disk('public')->url($image->file_path) }}"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Открыть фотографию товара"
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($image->file_path) }}"
                                        alt="{{ $image->alt ?: $product->name }}"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($galleryImages->count() > 1)
                        <div class="nik-product-thumbs" aria-label="Миниатюры товара">
                            @foreach ($galleryImages as $image)
                                <a
                                    class="nik-product-thumb"
                                    href="{{ Storage::disk('public')->url($image->file_path) }}"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Открыть фотографию товара"
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($image->file_path) }}"
                                        alt="{{ $image->alt ?: $product->name }}"
                                        loading="lazy"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="nik-product-summary">
                    <section class="nik-product-info nik-product-glass" aria-label="Информация о товаре">
                        @if ($product->brand)
                            <p class="nik-product-brand">{{ $product->brand->name }}</p>
                        @endif

                        <h1 class="nik-product-title">{{ $product->name }}</h1>

                        @if ($product->short_description)
                            <p class="nik-product-intro">{{ $product->short_description }}</p>
                        @endif

                        <div class="nik-product-meta">
                            @if ($product->article)
                                <span class="nik-product-chip">Артикул: {{ $product->article }}</span>
                            @endif

                            @if ($directionLabel)
                                <span class="nik-product-chip">{{ $directionLabel }}</span>
                            @endif

                            <span class="nik-product-status {{ $availabilityClass }}">{{ $availabilityLabel }}</span>
                        </div>

                        @if ($barcodeSvg)
                            <div class="nik-product-barcode">
                                {!! $barcodeSvg !!}
                                <div class="nik-product-barcode-value">{{ $product->barcode }}</div>
                            </div>
                        @endif
                    </section>

                    <section class="nik-product-buy nik-product-glass" aria-label="Покупка товара">
                        <div class="nik-product-price">
                            @if ($hasDiscount)
                                <span class="nik-product-current-price">{{ number_format((float) $product->discounted_price, 2, ',', ' ') }} ₽</span>
                                <span class="nik-product-old-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                            @elseif ($product->price !== null)
                                <span class="nik-product-current-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                            @endif

                            @if ($hasDiscountPercent)
                                <span class="nik-product-discount">Скидка {{ number_format((float) $product->discount_percent, 2, ',', ' ') }}%</span>
                            @endif
                        </div>

                        @if (session('success'))
                            <div class="nik-product-alert">{{ session('success') }}</div>
                        @endif

                        <div class="nik-product-actions">
                            @if ($canAddToCart)
                                @if ($cartProductItem)
                                    <div class="nik-product-quantity" aria-label="Количество товара в корзине">
                                        @if ($cartProductQuantity > 1)
                                            <form class="nik-product-quantity-form" method="POST" action="{{ route('cart.items.update', $cartProductItem) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $cartProductQuantity - 1 }}">
                                                <button class="nik-product-quantity-button" type="submit" aria-label="Уменьшить количество">-</button>
                                            </form>
                                        @else
                                            <form class="nik-product-quantity-form" method="POST" action="{{ route('cart.items.destroy', $cartProductItem) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="nik-product-quantity-button" type="submit" aria-label="Убрать товар из корзины">-</button>
                                            </form>
                                        @endif

                                        <span class="nik-product-quantity-value">{{ $cartProductQuantity }}</span>

                                        <form class="nik-product-quantity-form" method="POST" action="{{ route('cart.items.update', $cartProductItem) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $cartProductQuantity + 1 }}">
                                            <button class="nik-product-quantity-button" type="submit" aria-label="Увеличить количество">+</button>
                                        </form>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('cart.add', $product->slug ?: $product->id) }}">
                                        @csrf

                                        <button class="nik-product-button" type="submit">Добавить в корзину</button>
                                    </form>
                                @endif
                            @else
                                <button class="nik-product-button" type="button" disabled>
                                    {{ $availabilityStatus === 'out_of_stock' ? 'Временно отсутствует' : 'Снят с производства' }}
                                </button>
                            @endif

                            @if ($cartProductQuantity > 0)
                                <a class="nik-product-cart-link" href="{{ route('cart.index') }}">Перейти в корзину</a>
                            @endif
                        </div>
                    </section>

                    <section class="nik-product-specs nik-product-glass" aria-label="Характеристики товара">
                        <div class="nik-product-spec">
                            <div class="nik-product-spec-label">Статус наличия</div>
                            <div class="nik-product-spec-value">{{ $availabilityLabel }}</div>
                        </div>

                        @if ($product->brand)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Бренд</div>
                                <div class="nik-product-spec-value">{{ $product->brand->name }}</div>
                            </div>
                        @endif

                        @if ($product->category)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Категория</div>
                                <div class="nik-product-spec-value">{{ $product->category->name }}</div>
                            </div>
                        @endif

                        @if ($product->productType)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Тип товара</div>
                                <div class="nik-product-spec-value">{{ $product->productType->name }}</div>
                            </div>
                        @endif

                        @if ($product->productLine)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Линейка</div>
                                <div class="nik-product-spec-value">{{ $product->productLine->name }}</div>
                            </div>
                        @endif

                        @if ($productVolume)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Объем</div>
                                <div class="nik-product-spec-value">{{ $productVolume }}</div>
                            </div>
                        @endif

                        @if ($productWeight)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Вес</div>
                                <div class="nik-product-spec-value">{{ $productWeight }}</div>
                            </div>
                        @endif

                        @if ($productShelfLife)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Срок годности</div>
                                <div class="nik-product-spec-value">{{ $productShelfLife }}</div>
                            </div>
                        @endif
                    </section>
                </div>
            </section>

            <div class="nik-product-sections">
                @if ($product->description)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Описание</h2>
                        <p>{{ $product->description }}</p>
                    </section>
                @endif

                @if ($product->composition)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Состав</h2>
                        <p>{{ $product->composition }}</p>
                    </section>
                @endif

                @if ($product->usage_method)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Способ применения</h2>
                        <p>{{ $product->usage_method }}</p>
                    </section>
                @endif

                @if ($product->precautions)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Меры предосторожности</h2>
                        <p>{{ $product->precautions }}</p>
                    </section>
                @endif

                @if ($product->disposal_method)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Утилизация</h2>
                        <p>{{ $product->disposal_method }}</p>
                    </section>
                @endif

                @if ($product->storage_conditions)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Условия хранения</h2>
                        <p>{{ $product->storage_conditions }}</p>
                    </section>
                @endif

                @if ($product->certificates->isNotEmpty() || $product->instruction_file_path)
                    <section class="nik-product-section nik-product-glass">
                        <h2>Документация</h2>

                        <div class="nik-product-docs">
                            @foreach ($product->certificates as $certificate)
                                @php
                                    $expiresAt = $certificate->expires_at;
                                    $isExpired = $expiresAt && $expiresAt->isPast();
                                    $isSoon = $expiresAt && ! $isExpired && $expiresAt->lte(now()->addDays(30));
                                    $status = $isExpired ? 'просрочен' : ($isSoon ? 'скоро истекает' : 'действует');
                                    $statusClass = $isExpired ? 'nik-product-status-expired' : ($isSoon ? 'nik-product-status-soon' : 'nik-product-status-active');
                                @endphp

                                <article class="nik-product-doc">
                                    <div class="nik-product-doc-title">{{ $certificate->name }}</div>
                                    <div class="nik-product-doc-meta">
                                        @if ($certificate->certificate_type)
                                            {{ $certificate->certificate_type }}
                                        @endif

                                        @if ($certificate->number)
                                            № {{ $certificate->number }}
                                        @endif
                                    </div>
                                    <div class="nik-product-doc-meta">
                                        Статус: <span class="{{ $statusClass }}">{{ $status }}</span>

                                        @if ($certificate->is_permanent)
                                            · бессрочно
                                        @elseif ($expiresAt)
                                            · до {{ $expiresAt->format('d.m.Y') }}
                                        @endif
                                    </div>

                                    @if ($certificate->file_path)
                                        <a class="nik-product-doc-link" href="{{ Storage::disk('public')->url($certificate->file_path) }}" target="_blank" rel="noopener">
                                            Открыть PDF
                                        </a>
                                    @endif
                                </article>
                            @endforeach

                            @if ($product->instruction_file_path)
                                <article class="nik-product-doc">
                                    <div class="nik-product-doc-title">Инструкция по применению</div>
                                    <a class="nik-product-doc-link" href="{{ Storage::disk('public')->url($product->instruction_file_path) }}" target="_blank" rel="noopener">
                                        Открыть PDF
                                    </a>
                                </article>
                            @endif
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </main>
@endsection
