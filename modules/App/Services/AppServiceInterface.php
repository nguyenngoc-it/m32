<?php

namespace Modules\App\Services;

use Closure;
use Exception;
use Gobiz\Support\TokenGenerator;
use Psr\Log\LoggerInterface;

interface AppServiceInterface
{
    /**
     * Get response handler
     *
     * @return ResponseFactoryInterface
     */
    public function response();

    /**
     * Make asset's url
     *
     * @param string $path
     * @param array $query
     * @return string
     */
    public function assetUrl($path = null, array $query = []);

    /**
     * Make url to frontend
     *
     * @param string $path
     * @param array $query
     * @return string
     */
    public function frontendUrl($path = null, array $query = []);

    /**
     * Log time execute
     *
     * @param Closure $handler
     * @param LoggerInterface $logger
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function logTimeExecute(Closure $handler, LoggerInterface $logger, $message = '', array $context = []);

    /**
     * Log exception
     *
     * @param Exception $exception
     * @param array $context
     */
    public function logException(Exception $exception, array $context = []);

    /**
     * Log message
     *
     * @param string $message
     * @param array $context
     */
    public function logMessage($message, array $context = []);

    /**
     * Get webhook instance
     *
     * @return WebhookInterface
     */
    public function webhook();

    /**
     * Get token generator
     *
     * @return TokenGenerator
     */
    public function tokenGenerator();

    /**
     * Get utils api
     *
     * @return UtilsApiInterface
     */
    public function utilsApi();
}
