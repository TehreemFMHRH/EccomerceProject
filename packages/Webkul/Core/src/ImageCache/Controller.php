<?php

namespace Webkul\Core\ImageCache;

use Closure;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Config;
use Intervention\Image\ImageCacheController;

class Controller extends ImageCacheController
{
    
    protected $template;

    
    const BAGISTO_LOGO = 'https://updates.bagisto.com/bagisto.png';

    
    public function getResponse($template, $filename)
    {
        switch (strtolower($template)) {
            case 'original':
                return $this->getOriginal($filename);

            case 'download':
                return $this->getDownload($filename);

            default:
                return $this->getImage($template, $filename);
        }
    }

    
    public function getImage($template, $filename)
    {
        $this->template = $template;

        $cacheTime = $template == 'logo' ? 10080 : config('imagecache.lifetime');

        if ($template == 'logo') {
            $path = self::BAGISTO_LOGO;
        } else {
            $template = $this->getTemplate($template);

            $path = $this->getImagePath($filename);
        }

        
        $manager = new ImageManager(Config::get('image'));

        try {
            $content = $manager->cache(function ($image) use ($template, $path) {
                if ($template instanceof Closure) {
                    
                    $template($image->make($path));
                } elseif (is_object($template)) {
                    
                    $image->make($path)->filter($template);
                } else {
                    $image->make($path);
                }
            }, $cacheTime);
        } catch (\Exception $e) {
            if ($template != 'logo') {
                abort(404);
            }

            $content = '';
        }

        return $this->buildResponse($content);
    }

    
    protected function buildResponse($content)
    {
        
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);

        
        $eTag = md5($content);

        $notModified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag;

        $content = $notModified ? null : $content;

        $statusCode = $notModified ? 304 : 200;

        $maxAge = ($this->template == 'logo' ? 10080 : config('imagecache.lifetime')) * 60;

        
        return new IlluminateResponse($content, $statusCode, [
            'Content-Type'   => $mime,
            'Cache-Control'  => 'max-age='.$maxAge.', public',
            'Content-Length' => strlen($content),
            'Etag'           => $eTag,
        ]);
    }
}
