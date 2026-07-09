<?php

namespace App\Filament\Resources\SiteHomepageBanners\Pages;

use App\Filament\Resources\SiteHomepageBanners\SiteHomepageBannerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteHomepageBanner extends EditRecord
{
    protected static string $resource = SiteHomepageBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => SiteHomepageBannerResource::canDelete($this->record)),
        ];
    }
}
