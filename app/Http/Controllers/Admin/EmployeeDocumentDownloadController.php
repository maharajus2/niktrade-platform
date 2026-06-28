<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentDownloadController extends Controller
{
    public function __invoke(User $employee, EmployeeDocument $document): StreamedResponse|Response
    {
        abort_unless((int) $document->employee_id === (int) $employee->getKey(), 404);
        abort_unless(UserResource::canViewEmployeeDocuments($employee), 403);

        $disk = Storage::disk('employee_documents');

        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path),
        );
    }
}
