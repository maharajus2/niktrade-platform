<?php

use App\Support\UploadLimits;

return [
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:'.UploadLimits::TEMPORARY_FILE_MAX_KILOBYTES],
    ],
];
