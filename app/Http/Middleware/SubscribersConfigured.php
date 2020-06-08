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
    public function hasValidSignature(Request $request, $absolute = true)
    {
        $url = $absolute ? $request->url() : '/'.$request->path();
        Log::info("URL: ".$url);

        $original = rtrim($url.'?'.Arr::query(
                Arr::except($request->query(), 'signature')
            ), '?');

        Log::info("ORIGINAL: ".$original);

        $expires = $request->query('expires');
        Log::info("EXPIRES: ".$expires);

        Log::info("APP_KEY: ".(string) $this->config->get("app")["key"]);

        $signature = hash_hmac('sha256', $original, $this->config->get("app")["key"]);
        Log::info("GENERATED_SIGNATURE: ".$signature);

        Log::info("ORIGINAL_SIGNATURE: ".(string) $request->query('signature', ''));

        Log::info("TIME_CHECK: ".(string) ! ($expires && Carbon::now()->getTimestamp() > $expires));

        return  hash_equals($signature, (string) $request->query('signature', '')) &&
            ! ($expires && Carbon::now()->getTimestamp() > $expires);
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
        Log::info("============================== DEV LOGGING =====================================");
        $status = $this->hasValidSignature($request);
        Log::info("STATUS: ".$status);
        Log::info("============================== DEV LOGGING =====================================");
        return $next($request);
    }
}
