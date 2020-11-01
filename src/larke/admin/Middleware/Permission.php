<?php

namespace Larke\Admin\Middleware;

use Closure;

use Larke\Admin\Http\ResponseCode;
use Larke\Admin\Traits\Json as JsonTrait;

/*
 * 权限检测
 *
 * @create 2020-10-28
 * @author deatil
 */
class Permission
{
    use JsonTrait;
    
    public function handle($request, Closure $next)
    {
        if (!$this->shouldPassThrough($request)) {
            $this->permissionCheck();
        }
        
        return $next($request);
    }
    
    /*
     * 权限检测
     */
    public function permissionCheck()
    {
        if (app('larke.admin')->isAdministrator()) {
            return;
        }
        
        $adminId = app('larke.admin')->getId();
        $requestUrl = \Route::currentRouteName();
        $requestMethod = request()->getMethod();
        
        if (!\Enforcer::enforce($adminId, $requestUrl, $requestMethod)) {
            $this->errorJson(__('你没有访问权限'), ResponseCode::AUTH_ERROR);
        }
    }

    /**
     * Determine if the request has a URI that should pass through verification.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        $excepts = array_merge(config('larke.auth.excepts', []), [
            'larke-admin-passport-captcha',
            'larke-admin-passport-login',
            'larke-admin-passport-refresh-token',
            'larke-admin-attachment-download',
        ]);

        return collect($excepts)
            ->contains(function ($except) {
                $requestUrl = \Route::currentRouteName();
                return ($except == $requestUrl);
            });
    }

}
