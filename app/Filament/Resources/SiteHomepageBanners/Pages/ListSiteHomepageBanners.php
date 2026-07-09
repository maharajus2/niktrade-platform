<?php

namespace App\Filament\Resources\SiteHomepageBanners\Pages;

use App\Filament\Resources\SiteHomepageBanners\SiteHomepageBannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteHomepageBanners extends ListRecords
{
    protected static string $resource = SiteHomepageBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
