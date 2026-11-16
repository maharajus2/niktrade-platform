<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\Uploads\UploadStorage;
use App\View\Composers\BusinessLayoutComposer;
use App\View\Composers\ShopLayoutComposer;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        FileUpload::configureUsing(fn (FileUpload $component) => $component
            ->saveUploadedFileUsing(fn (FileUpload $component, TemporaryUploadedFile $file): string => app(UploadStorage::class)->storeFilament($component, $file)));

        Order::observe(OrderObserver::class);

        View::composer('layouts.shop', ShopLayoutComposer::class);
        View::composer('layouts.business', BusinessLayoutComposer::class);
    }
}
