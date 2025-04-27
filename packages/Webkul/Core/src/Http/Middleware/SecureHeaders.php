<?php

namespace Webkul\Core\Http\Middleware;

use Closure;

class SecureHeaders
{
    
    private $unwantedHeaderList = [
        'X-Powered-By',
        'Server',
    ];

    
    public function handle($request, Closure $next)
    {
        $this->removeUnwantedHeaders();

        $resp = $next($request);

        $this->setHeaders($resp);

        return $resp;
    }

    
    private function setHeaders($resp)
    {
        $resp->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $resp->headers->set('X-Content-Type-Options', 'nosniff');
        $resp->headers->set('X-XSS-Protection', '1; mode=block');
        $resp->headers->set('X-Frame-Options', 'DENY');
        $resp->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    
    private function removeUnwantedHeaders()
    {
        if (headers_sent()) {
            return;
        }

        foreach ($this->unwantedHeaderList as $header) {
            header_remove($header);
        }
    }
}
