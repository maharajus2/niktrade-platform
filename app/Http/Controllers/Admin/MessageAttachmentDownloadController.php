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
        return $this->respond($attachment, $access, true);
    }

    public function preview(MessageAttachment $attachment, MessengerAccessService $access): StreamedResponse|Response
    {
        abort_unless($attachment->isPreviewable(), 404);

        return $this->respond($attachment, $access, false);
    }

    private function respond(MessageAttachment $attachment, MessengerAccessService $access, bool $download): StreamedResponse|Response
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);
        abort_unless($access->canDownloadAttachment($attachment, $user), 403);

        $disk = Storage::disk('messenger_attachments');

        abort_unless(filled($attachment->file_path) && $disk->exists($attachment->file_path), 404);

        $filename = $attachment->original_filename ?: basename((string) $attachment->file_path);

        if ($download) {
            return $disk->download($attachment->file_path, $filename);
        }

        $stream = $disk->readStream($attachment->file_path);

        abort_unless(is_resource($stream), 404);

        return response()->stream(static function () use ($stream): void {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }
}
