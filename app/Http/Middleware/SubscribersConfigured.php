<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * This is the subscribers configured middleware class.
 *
 * @author James Brooks <james@alt-three.com>
 * @author Graham Campbell <graham@alt-three.com>
 */
class SubscribersConfigured
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Creates a subscribers configured middleware instance.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     *
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Determine if the given request has a valid signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $absolute
     * @return bool
     */
    public function hasValidSignatureCheck(Request $request, $absolute = true)
    {
        Log::debug("============================== DEV LOGGING =====================================");
        $url = $absolute ? $request->url() : '/'.$request->path();
        Log::debug("URL: ".$url);

        $original = rtrim($url.'?'.Arr::query(
                Arr::except($request->query(), 'signature')
            ), '?');

        Log::debug("ORIGINAL: ".$original);

        $expires = $request->query('expires');
        Log::debug("EXPIRES: ".$expires);

        Log::debug("APP_KEY: ".(string) $this->config->get("app")["key"]);

        $signature = hash_hmac('sha256', $original, $this->config->get("app")["key"]);
        Log::debug("GENERATED_SIGNATURE: ".$signature);

        Log::debug("ORIGINAL_SIGNATURE: ".(string) $request->query('signature', ''));

        Log::debug("TIME_CHECK: ".(string) ! ($expires && Carbon::now()->getTimestamp() > $expires));

        $status = hash_equals($signature, (string) $request->query('signature', '')) &&
            ! ($expires && Carbon::now()->getTimestamp() > $expires);

        Log::debug("STATUS: ".$status);
        Log::debug("============================== DEV LOGGING =====================================");
        return $status;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->config->get("app")["debug"]) {
            $this->hasValidSignatureCheck($request);
        }

        return $next($request);
    }
}
