<?php

namespace App\Filament\Resources\SiteHomepageBanners;

use App\Filament\Resources\Concerns\UsesResourcePermissions;
use App\Filament\Resources\SiteHomepageBanners\Pages\CreateSiteHomepageBanner;
use App\Filament\Resources\SiteHomepageBanners\Pages\EditSiteHomepageBanner;
use App\Filament\Resources\SiteHomepageBanners\Pages\ListSiteHomepageBanners;
use App\Filament\Resources\SiteHomepageBanners\Schemas\SiteHomepageBannerForm;
use App\Filament\Resources\SiteHomepageBanners\Tables\SiteHomepageBannersTable;
use App\Models\SiteHomepageBanner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SiteHomepageBannerResource extends Resource
{
    use UsesResourcePermissions;

    protected static string $permissionPrefix = 'site_homepage_banners';

    protected static ?string $model = SiteHomepageBanner::class;

    protected static ?string $navigationLabel = 'Баннеры главной';

    protected static ?string $modelLabel = 'баннер главной';

    protected static ?string $pluralModelLabel = 'баннеры главной';

    protected static string|UnitEnum|null $navigationGroup = 'Управление сайтом';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return SiteHomepageBannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteHomepageBannersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteHomepageBanners::route('/'),
            'create' => CreateSiteHomepageBanner::route('/create'),
            'edit' => EditSiteHomepageBanner::route('/{record}/edit'),
        ];
    }
}
