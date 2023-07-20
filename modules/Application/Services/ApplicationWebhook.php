<?php

namespace Modules\Application\Services;

use Gobiz\Event\EventService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use InvalidArgumentException;
use Modules\Application\Model\Application;
use Modules\Service;

class ApplicationWebhook implements ApplicationWebhookInterface
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * ApplicationWebhook constructor
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Sync application to webhook service
     *
     * @return array
     * @throws RestApiException
     */
    public function syncApplication()
    {
        if ($app = $this->findApplication()) {
            return $app;
        }

        return Service::app()->webhook()
            ->createApplication($this->application->only(['code', 'name', 'description']))
            ->getData('application');
    }

    /**
     * @return array|null
     */
    protected function findApplication()
    {
        try {
            return Service::app()->webhook()->getApplication($this->application->code)->getData('application');
        } catch (RestApiException $exception) {
            return null;
        }
    }

    /**
     * Update webhook url
     *
     * @param string $url
     * @return array
     * @throws RestApiException
     */
    public function updateWebhookUrl($url)
    {
        if (!$this->application->webhook_id) {
            $this->syncApplication();
            $res = Service::app()->webhook()->createWebhook($this->application->code, ['url' => $url]);
        } else {
            $res = Service::app()->webhook()->updateWebhook($this->application->webhook_id, ['url' => $url]);
        }

        $this->saveWebhook($webhook = $res->getData('webhook'));

        return $webhook;
    }

    /**
     * Reset the webhook secret
     *
     * @return array
     * @throws RestApiException
     */
    public function resetWebhookSecret()
    {
        if (!$this->application->webhook_id) {
            throw new InvalidArgumentException("Webhook not found");
        }

        $res = Service::app()->webhook()->resetWebhookSecret($this->application->webhook_id);

        $this->saveWebhook($webhook = $res->getData('webhook'));

        return $webhook;
    }

    /**
     * @param array $webhook
     */
    protected function saveWebhook(array $webhook)
    {
        $this->application->update([
            'webhook_id' => $webhook['id'],
            'webhook_url' => $webhook['url'],
            'webhook_secret' => $webhook['secret'],
        ]);
    }

    /**
     * Delete webhook
     *
     * @throws RestApiException
     */
    public function deleteWebhook()
    {
        if ($webhookId = $this->application->webhook_id) {
            Service::app()->webhook()->deleteWebhook($webhookId);
        }

        $this->application->update([
            'webhook_id' => null,
            'webhook_url' => null,
            'webhook_secret' => null,
        ]);
    }

    /**
     * Publish event
     *
     * @param string $event
     * @param array $payload
     * @param string|null $object
     * @return RestApiResponse|null
     * @throws RestApiException
     */
    public function publishEvent($event, array $payload = [], $object = null)
    {
        if (!$this->application->webhook_id) {
            return null;
        }

        return Service::app()->webhook()->publishEvent($this->application->code, array_filter([
            'name' => $event,
            'payload' => EventService::publicEventTransformer()->transform($payload),
            'object' => $object,
        ]));
    }
}
