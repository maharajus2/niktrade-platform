<?php

namespace App\Filament\Resources\BusinessRequests\Pages;

use App\Filament\Resources\BusinessRequests\BusinessRequestResource;
use App\Models\BusinessRequest;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditBusinessRequest extends EditRecord
{
    protected static string $resource = BusinessRequestResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $locked = BusinessRequest::whereKey($record->id)->lockForUpdate()->firstOrFail();
            // Only manager fields may be changed, even if a client tampers with form state.
            $locked->fill(array_intersect_key($data, array_flip(['status', 'manager_id', 'manager_comment', 'invoice_number', 'invoice_path'])))->save();

            return $locked;
        });
    }
}
