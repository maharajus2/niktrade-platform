<?php

namespace App\Support;

use App\Filament\Pages\MyCalendar;
use App\Filament\Pages\Messenger;
use App\Filament\Pages\Tasks;
use App\Filament\Pages\Workplace;
use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\ProductLines\ProductLineResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ProductTypes\ProductTypeResource;
use App\Filament\Resources\SiteHomepageBanners\SiteHomepageBannerResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Models\User;
use Throwable;

class WorkNavigation
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function groups(?string $active = null, ?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return self::filterGroups([
            'Главное' => [
                self::pageItem('workplace', 'Рабочее пространство', 'grid', Workplace::class, $active),
                self::pageItem('calendar', 'Календарь', 'calendar', MyCalendar::class, $active),
                self::pageItem('messenger', 'Мессенджер', 'message', Messenger::class, $active),
                self::pageItem('tasks', 'Задачи', 'check-square', Tasks::class, $active),
                self::resourceItem('requests', 'Заявки', 'link', EmployeeScheduleRequestResource::class, $active),
            ],
            'Продажи' => [
                self::resourceItem('orders', 'Заказы', 'package', OrderResource::class, $active),
                self::resourceItem('customers', 'Покупатели', 'users', CustomerResource::class, $active),
            ],
            'Каталог' => [
                self::resourceItem('products', 'Товары', 'package', ProductResource::class, $active),
                self::resourceItem('categories', 'Категории', 'grid', CategoryResource::class, $active),
                self::resourceItem('brands', 'Бренды', 'check-circle', BrandResource::class, $active),
                self::resourceItem('product_lines', 'Линейки', 'list', ProductLineResource::class, $active),
                self::resourceItem('product_types', 'Типы товаров', 'book', ProductTypeResource::class, $active),
                self::resourceItem('certificates', 'Сертификаты', 'file', CertificateResource::class, $active),
            ],
            'Справочники' => [
                self::resourceItem('warehouses', 'Склады', 'building', WarehouseResource::class, $active),
            ],
            'Компания' => [
                self::resourceItem('employees', 'Сотрудники', 'users', UserResource::class, $active),
                self::resourceItem('departments', 'Отделы', 'building', DepartmentResource::class, $active),
            ],
            'Управление сайтом' => [
                self::resourceItem('site_homepage_banners', 'Баннеры главной', 'grid', SiteHomepageBannerResource::class, $active),
            ],
        ]);
    }

    /**
     * @param array<string, list<array<string, mixed>>> $groups
     * @return array<string, list<array<string, mixed>>>
     */
    public static function mergeMenuGroups(array $groups, ?User $user = null, ?string $active = null): array
    {
        $knownItems = collect($groups)
            ->flatten(1)
            ->filter()
            ->values()
            ->all();

        foreach (self::groups($active, $user) as $group => $items) {
            $groups[$group] ??= [];

            foreach ($items as $item) {
                if (self::containsItem($knownItems, $item)) {
                    continue;
                }

                $groups[$group][] = $item;
                $knownItems[] = $item;
            }
        }

        return self::filterGroups($groups);
    }

    /**
     * @param class-string $page
     * @return array<string, mixed>|null
     */
    private static function pageItem(string $key, string $label, string $icon, string $page, ?string $active): ?array
    {
        try {
            if (method_exists($page, 'canAccess') && ! $page::canAccess()) {
                return null;
            }

            return [
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'url' => $page::getUrl(),
                'active' => $active === $key,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param class-string $resource
     * @return array<string, mixed>|null
     */
    private static function resourceItem(string $key, string $label, string $icon, string $resource, ?string $active): ?array
    {
        try {
            if (method_exists($resource, 'canAccess') && ! $resource::canAccess()) {
                return null;
            }

            return [
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'url' => $resource::getUrl('index'),
                'active' => $active === $key,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, list<array<string, mixed>|null>> $groups
     * @return array<string, list<array<string, mixed>>>
     */
    private static function filterGroups(array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group => $items) {
            $items = array_values(array_filter($items));

            if ($items !== []) {
                $filtered[$group] = $items;
            }
        }

        return $filtered;
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private static function containsItem(array $items, array $needle): bool
    {
        foreach ($items as $item) {
            if (($item['url'] ?? null) === ($needle['url'] ?? null)) {
                return true;
            }

            if (($item['label'] ?? null) === ($needle['label'] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
