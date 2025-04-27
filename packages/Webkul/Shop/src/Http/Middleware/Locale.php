<?php

namespace Webkul\Shop\Http\Middleware;

use Closure;
use Webkul\Core\Repositories\LocaleRepository;

class Locale
{
    
    public function __construct(protected LocaleRepository $localeRepository) {}

    
    public function handle($request, Closure $next)
    {
        $locales = core()->getCurrentChannel()->locales->pluck('code')->toArray();
        $localeCode = core()->getRequestedLocaleCode('locale', false);

        if (! $localeCode || ! in_array($localeCode, $locales)) {
            $localeCode = session()->get('locale');
        }

        if (! $localeCode || ! in_array($localeCode, $locales)) {
            $localeCode = core()->getCurrentChannel()->default_locale->code;
        }

        app()->setLocale($localeCode);
        session()->put('locale', $localeCode);
        unset($request['locale']);

        return $next($request);
    }
}
