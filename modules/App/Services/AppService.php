<?php

namespace Modules\App\Services;

use Closure;
use Exception;
use Gobiz\Log\LogService;
use Gobiz\Support\TokenGenerator;
use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Sentry\Severity;
use Sentry\State\Scope;

class AppService implements AppServiceInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $response;

    /**
     * @var WebhookInterface
     */
    protected $webhook;

    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var UtilsApiInterface
     */
    protected $utilsApi;

    /**
     * Get response handler
     *
     * @return ResponseFactoryInterface
     */
    public function response()
    {
        return $this->response ?? $this->response = new ResponseFactory(TransformerService::transformers());
    }

    /**
     * Make asset's url
     *
     * @param string $path
     * @param array $query
     * @return string
     */
    public function assetUrl($path = null, array $query = [])
    {
        if ($version = config('app.asset_version')) {
            $query['v'] = $version;
        }

        return $this->makeUrl(config('app.asset_url'), $path, $query);
    }

    /**
     * Make url to frontend
     *
     * @param string $path
     * @param array $query
     * @return string
     */
    public function frontendUrl($path = null, array $query = [])
    {
        return $this->makeUrl(config('app.frontend_url'), $path, $query);
    }

    /**
     * @param string $root
     * @param string $path
     * @param array $query
     * @return string
     */
    protected function makeUrl($root, $path = null, array $query = [])
    {
        return $root . ($path ? '/' . $path : '') . (empty($query) ? '' : '?' . http_build_query($query));
    }

    /**
     * Log time execute
     *
     * @param Closure $handler
     * @param LoggerInterface $logger
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function logTimeExecute(Closure $handler, LoggerInterface $logger, $message = '', array $context = [])
    {
        $start = microtime(true);
        $logger->info("{$message} Start", $context);
        $result = $handler();
        $time = microtime(true) - $start;
        $logger->info("{$message} End: {$time}", $context);

        return $result;
    }
    /**
     * Log exception
     *
     * @param Exception $exception
     * @param array $context
     */
    public function logException(Exception $exception, array $context = [])
    {
        if (!env('SENTRY_ENABLE')) {
            LogService::logger('exception')->error($exception->getMessage(), array_merge($context, ['trace' => $exception->getTraceAsString()]));
            return;
        }

        app('sentry')->withScope(function (Scope $scope) use ($exception, $context) {
            $scope->setExtras($context);
            app('sentry')->captureException($exception);
        });
    }

    /**
     * Log message
     *
     * @param string $message
     * @param array $context
     */
    public function logMessage($message, array $context = [])
    {
        $level = Arr::pull($context, 'level', 'info');

        if (!env('SENTRY_ENABLE')) {
            LogService::logger('message')->log($level, $message, $context);
            return;
        }

        app('sentry')->withScope(function (Scope $scope) use ($message, $level, $context) {
            $scope->setExtras($context);
            app('sentry')->captureMessage($message, new Severity($level));
        });
    }

    /**
     * Get webhook instance
     *
     * @return WebhookInterface
     */
    public function webhook()
    {
        return $this->webhook ?? $this->webhook = new Webhook(config('gobiz.webhook'));
    }

    /**
     * Get token generator
     *
     * @return TokenGenerator
     */
    public function tokenGenerator()
    {
        return $this->tokenGenerator ?? $this->tokenGenerator = new TokenGenerator();
    }

    /**
     * Get utils api
     *
     * @return UtilsApiInterface
     */
    public function utilsApi()
    {
        return $this->utilsApi ?? $this->utilsApi = new UtilsApi(config('gobiz.utils.url'), config('gobiz.utils.key'), config('gobiz.utils.ttl'));
    }
}
