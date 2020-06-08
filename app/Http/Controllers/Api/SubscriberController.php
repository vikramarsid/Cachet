<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Http\Controllers\Api;

use CachetHQ\Cachet\Bus\Commands\Subscriber\GetSubscriberByIdCommand;
use CachetHQ\Cachet\Bus\Commands\Subscriber\SubscribeSubscriberCommand;
use CachetHQ\Cachet\Bus\Commands\Subscriber\UnsubscribeSubscriberCommand;
use CachetHQ\Cachet\Bus\Commands\Subscriber\UpdateSubscriberSubscriptionCommand;
use CachetHQ\Cachet\Models\Subscriber;
use GrahamCampbell\Binput\Facades\Binput;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * This is the subscriber controller class.
 *
 * @author James Brooks <james@alt-three.com>
 * @author Graham Campbell <graham@alt-three.com>
 */
class SubscriberController extends AbstractApiController
{
    /**
     * Get all subscribers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $subscribers = Subscriber::paginate(Binput::get('per_page', 20));

        return $this->paginator($subscribers, Request::instance());
    }

    /**
     * Create a new subscriber.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $verified = Binput::get('verify', app(Repository::class)->get('setting.skip_subscriber_verification'));

        try {
            $subscriber = execute(new SubscribeSubscriberCommand(Binput::get('email'), $verified, Binput::get('components', null)));
        } catch (QueryException $e) {
            throw new BadRequestHttpException();
        }

        return $this->item($subscriber);
    }

    /**
     * Update existing subscriber.
     *
     * @param \CachetHQ\Cachet\Models\Subscriber $subscriber
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Subscriber $subscriber)
    {

        try {
            execute(new UpdateSubscriberSubscriptionCommand($subscriber, Binput::get('components')));
            $updatedSubscriber = $subscriber->load('subscriptions');;
        } catch (QueryException $e) {
            throw new BadRequestHttpException();
        }

        return $this->item($updatedSubscriber);
    }

    /**
     * Delete a subscriber.
     *
     * @param \CachetHQ\Cachet\Models\Subscriber $subscriber
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Subscriber $subscriber)
    {
        execute(new UnsubscribeSubscriberCommand($subscriber));

        return $this->noContent();
    }
}
