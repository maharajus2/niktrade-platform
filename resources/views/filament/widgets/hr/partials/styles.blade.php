@once
    <style>
        .nt-hr-dashboard,
        .nt-hr-dashboard * {
            box-sizing: border-box;
        }

        .nt-hr-dashboard.grid,
        .nt-hr-dashboard .grid {
            display: grid;
        }

        .nt-hr-dashboard .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .nt-hr-dashboard.flex,
        .nt-hr-dashboard .flex {
            display: flex;
        }

        .nt-hr-dashboard.block,
        .nt-hr-dashboard .block {
            display: block;
        }

        .nt-hr-dashboard.inline-flex,
        .nt-hr-dashboard .inline-flex {
            display: inline-flex;
        }

        .nt-hr-dashboard .flex-col {
            flex-direction: column;
        }

        .nt-hr-dashboard .flex-wrap {
            flex-wrap: wrap;
        }

        .nt-hr-dashboard .items-center {
            align-items: center;
        }

        .nt-hr-dashboard .items-start {
            align-items: flex-start;
        }

        .nt-hr-dashboard .justify-between {
            justify-content: space-between;
        }

        .nt-hr-dashboard .justify-center {
            justify-content: center;
        }

        .nt-hr-dashboard.gap-4,
        .nt-hr-dashboard .gap-4 {
            gap: 1rem;
        }

        .nt-hr-dashboard .gap-3 {
            gap: .75rem;
        }

        .nt-hr-dashboard .gap-2 {
            gap: .5rem;
        }

        .nt-hr-dashboard .gap-1\.5 {
            gap: .375rem;
        }

        .nt-hr-dashboard.space-y-5 > * + *,
        .nt-hr-dashboard .space-y-5 > * + * {
            margin-top: 1.25rem;
        }

        .nt-hr-dashboard.space-y-3 > * + *,
        .nt-hr-dashboard .space-y-3 > * + * {
            margin-top: .75rem;
        }

        .nt-hr-dashboard.space-y-2 > * + *,
        .nt-hr-dashboard .space-y-2 > * + * {
            margin-top: .5rem;
        }

        .nt-hr-dashboard.rounded-xl,
        .nt-hr-dashboard .rounded-xl {
            border-radius: .75rem;
        }

        .nt-hr-dashboard .rounded-lg {
            border-radius: .5rem;
        }

        .nt-hr-dashboard .rounded-full {
            border-radius: 9999px;
        }

        .nt-hr-dashboard.border,
        .nt-hr-dashboard .border {
            border: 1px solid #e5e7eb;
        }

        .nt-hr-dashboard .border-dashed {
            border-style: dashed;
        }

        .nt-hr-dashboard .border-gray-100 {
            border-color: #f3f4f6;
        }

        .nt-hr-dashboard .border-gray-200 {
            border-color: #e5e7eb;
        }

        .nt-hr-dashboard .border-yellow-200 {
            border-color: #fde68a;
        }

        .nt-hr-dashboard.bg-white,
        .nt-hr-dashboard .bg-white {
            background: #ffffff;
        }

        .nt-hr-dashboard .bg-gray-50 {
            background: #f9fafb;
        }

        .nt-hr-dashboard .bg-gray-100 {
            background: #f3f4f6;
        }

        .nt-hr-dashboard .bg-red-50 {
            background: #fef2f2;
        }

        .nt-hr-dashboard .bg-yellow-50 {
            background: #fefce8;
        }

        .nt-hr-dashboard .bg-emerald-50 {
            background: #ecfdf5;
        }

        .nt-hr-dashboard .bg-sky-50 {
            background: #f0f9ff;
        }

        .nt-hr-dashboard .bg-purple-100 {
            background: #f3e8ff;
        }

        .nt-hr-dashboard .bg-red-100 {
            background: #fee2e2;
        }

        .nt-hr-dashboard .bg-yellow-100 {
            background: #fef3c7;
        }

        .nt-hr-dashboard .bg-emerald-100 {
            background: #d1fae5;
        }

        .nt-hr-dashboard .bg-sky-100 {
            background: #e0f2fe;
        }

        .nt-hr-dashboard .bg-primary-600 {
            background: #0ea5e9;
        }

        .nt-hr-dashboard.shadow-sm,
        .nt-hr-dashboard .shadow-sm {
            box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
        }

        .nt-hr-dashboard.hover\:shadow-md:hover,
        .nt-hr-dashboard .hover\:shadow-md:hover {
            box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
        }

        .nt-hr-dashboard.p-5,
        .nt-hr-dashboard .p-5 {
            padding: 1.25rem;
        }

        .nt-hr-dashboard .p-4 {
            padding: 1rem;
        }

        .nt-hr-dashboard .p-3 {
            padding: .75rem;
        }

        .nt-hr-dashboard .p-2\.5 {
            padding: .625rem;
        }

        .nt-hr-dashboard .px-3 {
            padding-left: .75rem;
            padding-right: .75rem;
        }

        .nt-hr-dashboard .px-2\.5 {
            padding-left: .625rem;
            padding-right: .625rem;
        }

        .nt-hr-dashboard .px-2 {
            padding-left: .5rem;
            padding-right: .5rem;
        }

        .nt-hr-dashboard .py-2 {
            padding-top: .5rem;
            padding-bottom: .5rem;
        }

        .nt-hr-dashboard .py-1 {
            padding-top: .25rem;
            padding-bottom: .25rem;
        }

        .nt-hr-dashboard .mt-5 {
            margin-top: 1.25rem;
        }

        .nt-hr-dashboard .mt-4 {
            margin-top: 1rem;
        }

        .nt-hr-dashboard .mt-2 {
            margin-top: .5rem;
        }

        .nt-hr-dashboard .mt-1 {
            margin-top: .25rem;
        }

        .nt-hr-dashboard .mb-2 {
            margin-bottom: .5rem;
        }

        .nt-hr-dashboard .min-w-0 {
            min-width: 0;
        }

        .nt-hr-dashboard .shrink-0 {
            flex-shrink: 0;
        }

        .nt-hr-dashboard .h-2\.5 {
            height: .625rem;
        }

        .nt-hr-dashboard .w-2\.5 {
            width: .625rem;
        }

        .nt-hr-dashboard .h-10 {
            height: 2.5rem;
        }

        .nt-hr-dashboard .w-10 {
            width: 2.5rem;
        }

        .nt-hr-dashboard .text-xs {
            font-size: .75rem;
            line-height: 1rem;
        }

        .nt-hr-dashboard .text-sm {
            font-size: .875rem;
            line-height: 1.25rem;
        }

        .nt-hr-dashboard .text-base {
            font-size: 1rem;
            line-height: 1.5rem;
        }

        .nt-hr-dashboard .text-2xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }

        .nt-hr-dashboard .text-3xl {
            font-size: 1.875rem;
            line-height: 2.25rem;
        }

        .nt-hr-dashboard .font-medium {
            font-weight: 500;
        }

        .nt-hr-dashboard .font-semibold {
            font-weight: 600;
        }

        .nt-hr-dashboard .uppercase {
            text-transform: uppercase;
        }

        .nt-hr-dashboard .tracking-wide {
            letter-spacing: .025em;
        }

        .nt-hr-dashboard .tracking-tight {
            letter-spacing: -.025em;
        }

        .nt-hr-dashboard .text-white {
            color: #ffffff;
        }

        .nt-hr-dashboard .text-gray-950 {
            color: #030712;
        }

        .nt-hr-dashboard .text-gray-700 {
            color: #374151;
        }

        .nt-hr-dashboard .text-gray-600 {
            color: #4b5563;
        }

        .nt-hr-dashboard .text-gray-500 {
            color: #6b7280;
        }

        .nt-hr-dashboard .text-red-700 {
            color: #b91c1c;
        }

        .nt-hr-dashboard .text-yellow-700 {
            color: #a16207;
        }

        .nt-hr-dashboard .text-emerald-700 {
            color: #047857;
        }

        .nt-hr-dashboard .text-sky-700 {
            color: #0369a1;
        }

        .nt-hr-dashboard .text-purple-700 {
            color: #7e22ce;
        }

        .nt-hr-dashboard .text-primary-600 {
            color: #0284c7;
        }

        .nt-hr-dashboard .text-primary-500,
        .nt-hr-dashboard .hover\:text-primary-500:hover {
            color: #0ea5e9;
        }

        .nt-hr-dashboard a {
            text-decoration: none;
        }

        .nt-hr-dashboard .transition {
            transition: all .15s ease;
        }

        .nt-hr-dashboard .hover\:bg-gray-50:hover {
            background: #f9fafb;
        }

        .nt-hr-dashboard .hover\:bg-white:hover {
            background: #ffffff;
        }

        .nt-hr-dashboard .hover\:bg-primary-500:hover {
            background: #38bdf8;
        }

        .nt-hr-dashboard .hover\:-translate-y-0\.5:hover {
            transform: translateY(-2px);
        }

        .nt-hr-dashboard .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nt-hr-dashboard .object-cover {
            object-fit: cover;
        }

        @media (min-width: 768px) {
            .nt-hr-dashboard.md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .nt-hr-dashboard .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .nt-hr-dashboard .sm\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .nt-hr-dashboard .sm\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .nt-hr-dashboard .sm\:flex-row {
                flex-direction: row;
            }

            .nt-hr-dashboard .sm\:items-start {
                align-items: flex-start;
            }

            .nt-hr-dashboard .sm\:justify-between {
                justify-content: space-between;
            }
        }

        @media (min-width: 1024px) {
            .nt-hr-dashboard .lg\:flex-row {
                flex-direction: row;
            }

            .nt-hr-dashboard .lg\:items-center {
                align-items: center;
            }

            .nt-hr-dashboard .lg\:justify-between {
                justify-content: space-between;
            }
        }

        @media (min-width: 1280px) {
            .nt-hr-dashboard.xl\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>
@endonce
