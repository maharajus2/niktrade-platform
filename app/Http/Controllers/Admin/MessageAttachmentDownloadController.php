<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\Messenger\MessengerAccessService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentDownloadController extends Controller
{
    public function __invoke(MessageAttachment $attachment, MessengerAccessService $access): StreamedResponse|Response
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);
        abort_unless($access->canDownloadAttachment($attachment, $user), 403);

        $disk = Storage::disk('messenger_attachments');

        abort_unless(filled($attachment->file_path) && $disk->exists($attachment->file_path), 404);

        return $disk->download(
            $attachment->file_path,
            $attachment->original_filename ?: basename((string) $attachment->file_path),
        );
    }
}
