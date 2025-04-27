<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as BasePreventRequestsDuringMaintenance;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Installer\Helpers\DatabaseManager;

class PreventRequestsDuringMaintenance extends BasePreventRequestsDuringMaintenance
{
    
    protected DatabaseManager $databaseManager;

    
    protected $excludedNames = [];

    
    protected $excludedIPs = [];

    
    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->databaseManager = $this->app->make(DatabaseManager::class);

        $this->except[] = config('app.admin_url').'*';

        if ($this->databaseManager->isInstalled()) {
            $this->setAllowedIps();
        }
    }

    
    public function handle($request, Closure $next)
    {
        if ($this->databaseManager->isInstalled() && $this->app->maintenanceMode()->active()) {
            try {
                $dat = $this->app->maintenanceMode()->data();
            } catch (\ErrorException $exception) {
                if (! $this->app->maintenanceMode()->active()) {
                    return $next($request);
                }

                throw $exception;
            }

            if (isset($dat['secret']) && $request->path() === $dat['secret']) {
                return $this->bypassResponse($dat['secret']);
            }

            if ($this->hasValidBypassCookie($request, $dat)) {
                return $next($request);
            }

            if (
                in_array($request->ip(), $this->excludedIPs)
                || $this->inExceptArray($request)
                || ! (bool) core()->getCurrentChannel()->is_maintenance_on
            ) {
                return $next($request);
            }

            if (
                $request->route() instanceof Route
                && in_array($request->route()->getName(), $this->excludedNames)
            ) {
                return $next($request);
            }

            if (isset($dat['redirect'])) {
                $path = $dat['redirect'] === '/'
                    ? $dat['redirect']
                    : trim($dat['redirect'], '/');

                if ($request->path() !== $path) {
                    return redirect($path);
                }
            }

            if (isset($dat['template'])) {
                return response(
                    $dat['template'],
                    $dat['status'] ?? 503,
                    $this->getHeaders($dat)
                );
            }

            throw new HttpException(
                $dat['status'] ?? 503,
                'Service Unavailable',
                null,
                $this->getHeaders($dat)
            );
        }

        return $next($request);
    }

    
    protected function setAllowedIps(): void
    {
        if ($channel = core()->getCurrentChannel()) {
            $this->excludedIPs = array_map('trim', explode(',', $channel->allowed_ips ?? ''));
        }
    }
}
