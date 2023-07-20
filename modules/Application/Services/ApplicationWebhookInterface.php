<?php

namespace Modules\Application\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface ApplicationWebhookInterface
{
    /**
     * Sync application to webhook service
     *
     * @return int
     * @throws RestApiException
     */
    public function syncApplication();

    /**
     * Update webhook url
     *
     * @param string $url
     * @return array
     * @throws RestApiException
     */
    public function updateWebhookUrl($url);

    /**
     * Reset the webhook secret
     *
     * @return array
     * @throws RestApiException
     */
    public function resetWebhookSecret();

    /**
     * Delete webhook
     *
     * @throws RestApiException
     */
    public function deleteWebhook();

    /**
     * Publish event
     *
     * @param string $event
     * @param array $payload
     * @param string|null $object
     * @return RestApiResponse|null
     * @throws RestApiException
     */
    public function publishEvent($event, array $payload = [], $object = null);
}