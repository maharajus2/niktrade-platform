<?php

namespace Tests\Unit;

use App\Support\UploadLimits;
use Tests\TestCase;

class UploadLimitsTest extends TestCase
{
    public function test_image_upload_limit_is_fifteen_megabytes(): void
    {
        $this->assertSame(15 * 1024, UploadLimits::IMAGE_MAX_KILOBYTES);
    }

    public function test_livewire_preserves_existing_twenty_megabyte_file_uploads(): void
    {
        $this->assertSame(20 * 1024, UploadLimits::TEMPORARY_FILE_MAX_KILOBYTES);
        $this->assertContains(
            'max:'.UploadLimits::TEMPORARY_FILE_MAX_KILOBYTES,
            config('livewire.temporary_file_upload.rules'),
        );
    }
}
