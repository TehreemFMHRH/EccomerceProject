<?php

namespace Webkul\Core\Traits;

use enshrined\svgSanitize\Sanitizer as MainSanitizer;
use Illuminate\Support\Facades\Storage;

trait Sanitizer
{
    
    public $mimeTypes = [
        'image/svg',
        'image/svg+xml',
    ];

    
    public function sanitizeSVG($path, $mimeType)
    {
        if ($this->checkMimeType($mimeType)) {
            /* sanitizer instance */
            $sanitizer = new MainSanitizer;

            /* grab svg file */
            $dirtySVG = Storage::get($path);

            /* save sanitized svg */
            Storage::put($path, $sanitizer->sanitize($dirtySVG));
        }
    }

    
    public function checkMimeType($mimeType)
    {
        return in_array($mimeType, $this->mimeTypes);
    }
}
