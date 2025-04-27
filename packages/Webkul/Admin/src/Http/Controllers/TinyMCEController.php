<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class TinyMCEController extends Controller
{
    
    private $storagePath = 'tinymce';

    
    public function upload()
    {
        $media = $this->storeMedia();

        if (! empty($media)) {
            return response()->json([
                'location' => $media['file_url'],
            ]);
        }

        return response()->json([]);
    }

    
    public function storeMedia()
    {
        if (! request()->hasFile('file')) {
            return [];
        }

        return [
            'file'      => $path = request()->file('file')->store($this->storagePath),
            'file_name' => request()->file('file')->getClientOriginalName(),
            'file_url'  => Storage::url($path),
        ];
    }
}
