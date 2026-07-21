@php
    use App\Filament\Pages\Tasks;
    use App\Filament\Pages\Workplace;
    use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
    use App\Models\EmployeeScheduleRequest;
    use App\Models\User;
    use App\Support\WorkNavigation;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $routeName = request()->route()?->getName() ?? '';

    $customRoutes = [
        'filament.admin.pages.workplace',
        'filament.admin.pages.my-calendar',
        'filament.admin.pages.messenger',
        'filament.admin.pages.tasks',
        'filament.admin.resources.orders.index',
        'filament.admin.resources.orders.kanban',
        'filament.admin.resources.orders.view',
        'filament.admin.resources.employee-schedule-requests.create',
        'filament.admin.resources.employee-schedule-requests.view',
        'filament.admin.resources.admin-users.users.index',
        'filament.admin.resources.admin-users.users.view',
        'filament.admin.resources.departments.index',
    ];

    $shouldRender = $user instanceof User
        && Str::startsWith($routeName, 'filament.admin.')
        && ! Str::contains($routeName, '.auth.')
        && ! in_array($routeName, $customRoutes, true);

    $active = match (true) {
        Str::contains($routeName, '.resources.products.') => 'products',
        Str::contains($routeName, '.resources.categories.') => 'categories',
        Str::contains($routeName, '.resources.brands.') => 'brands',
        Str::contains($routeName, '.resources.product-lines.') => 'product_lines',
        Str::contains($routeName, '.resources.product-types.') => 'product_types',
        Str::contains($routeName, '.resources.certificates.') => 'certificates',
        Str::contains($routeName, '.resources.customers.') => 'customers',
        Str::contains($routeName, '.resources.orders.') => 'orders',
        Str::contains($routeName, '.resources.warehouses.') => 'warehouses',
        Str::contains($routeName, '.resources.admin-users.') => 'employees',
        Str::contains($routeName, '.resources.departments.') => 'departments',
        Str::contains($routeName, '.resources.employee-schedule-requests.') => 'requests',
        Str::contains($routeName, '.resources.site-homepage-banners.') => 'site_homepage_banners',
        default => 'workplace',
    };

    $requestCounts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'returned' => 0,
    ];
    $recentRequests = collect();

    if ($shouldRender) {
        $baseRequests = EmployeeScheduleRequest::query()
            ->visibleTo($user)
            ->whereNull('deleted_at');

        $requestCounts = [
            'pending' => (clone $baseRequests)->whereIn('status', [
                EmployeeScheduleRequest::STATUS_PENDING,
                EmployeeScheduleRequest::STATUS_IN_REVIEW,
                EmployeeScheduleRequest::STATUS_FORWARDED,
            ])->count(),
            'approved' => (clone $baseRequests)->where('status', EmployeeScheduleRequest::STATUS_APPROVED)->count(),
            'rejected' => (clone $baseRequests)->where('status', EmployeeScheduleRequest::STATUS_REJECTED)->count(),
            'returned' => (clone $baseRequests)->where('status', EmployeeScheduleRequest::STATUS_RETURNED)->count(),
        ];

        $recentRequests = (clone $baseRequests)
            ->latest()
            ->limit(3)
            ->get();
    }
@endphp

@if ($shouldRender)
    <div
        class="nik-work-legacy-nav"
        x-data="{
            activeSheet: null,
            openSheet(sheet) {
                this.activeSheet = sheet
                document.documentElement.classList.add('nik-work-mobile-sheet-open')
            },
            closeSheet() {
                this.activeSheet = null
                document.documentElement.classList.remove('nik-work-mobile-sheet-open')
            },
        }"
        x-on:keydown.escape.window="closeSheet()"
    >
        <x-work.sidebar :active="$active" :user="$user" />

        <div class="nik-work-legacy-actions" aria-label="Панель действий">
            <label class="nik-work-legacy-search">
                <x-work.icon name="search" />
                <input type="search" placeholder="Поиск..." />
            </label>
            <button type="button" class="nik-work-legacy-icon-button has-badge" aria-label="Уведомления">
                <x-work.icon name="bell" />
                <span>3</span>
            </button>
            <x-work.user-menu :user="$user" button-class="nik-work-legacy-avatar" />
        </div>

        <header class="nik-work-legacy-mobile-head">
            <a href="{{ Workplace::getUrl() }}" class="nik-work-legacy-mobile-logo" aria-label="Никтрейд">
                <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
            </a>
            <div>
                <button type="button" class="nik-work-legacy-icon-button" aria-label="Поиск">
                    <x-work.icon name="search" />
                </button>
                <button type="button" class="nik-work-legacy-icon-button has-badge" aria-label="Уведомления">
                    <x-work.icon name="bell" />
                    <span>3</span>
                </button>
                <x-work.user-menu :user="$user" button-class="nik-work-legacy-avatar" :show-chevron="false" />
            </div>
        </header>

        <x-work.mobile-bottom-sheets
            :menu-groups="WorkNavigation::groups($active, $user)"
            :request-counts="$requestCounts"
            :recent-requests="$recentRequests"
            :create-request-url="EmployeeScheduleRequestResource::getUrl('create')"
            :requests-url="EmployeeScheduleRequestResource::getUrl('index')"
        />

        <x-work.mobile-bottom-nav :active="$active" />
    </div>

    <script>
        (() => {
            const initLegacyWorkNavigation = () => {
                const nav = document.querySelector('.nik-work-legacy-nav')
                const toggle = nav?.querySelector('[data-sidebar-toggle]')

                if (!nav || !toggle) {
                    return
                }

                const storageKey = 'nik-work-sidebar-collapsed'
                const applyState = (isCollapsed) => {
                    nav.classList.toggle('is-sidebar-collapsed', isCollapsed)
                    toggle.setAttribute('aria-expanded', String(!isCollapsed))
                    toggle.setAttribute(
                        'aria-label',
                        isCollapsed ? 'Развернуть боковую панель' : 'Свернуть боковую панель',
                    )
                }

                applyState(localStorage.getItem(storageKey) === 'true')

                toggle.addEventListener('click', () => {
                    const isCollapsed = !nav.classList.contains('is-sidebar-collapsed')

                    localStorage.setItem(storageKey, String(isCollapsed))
                    applyState(isCollapsed)
                })
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLegacyWorkNavigation)
            } else {
                initLegacyWorkNavigation()
            }
        })()
    </script>
@endif
