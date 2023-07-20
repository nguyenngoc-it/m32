<?php

namespace Modules\App\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;

class UtilsApi implements UtilsApiInterface
{
    use RestApiRequestTrait;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $ttl = 3600;

    /**
     * @var Client
     */
    protected $http;

    /**
     * UtilsApi constructor
     *
     * @param string $url
     * @param string $key
     * @param int $ttl
     */
    public function __construct($url, $key, $ttl = null)
    {
        $this->url = $url;
        $this->key = $key;
        $this->ttl = $ttl ?: $this->ttl;

        $this->http = new Client([
            'base_uri' => $this->url,
        ]);

        $this->logger = LogService::logger('utils');
    }

    /**
     * Merge files
     *
     * @param array $urls
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function mergeFiles(array $urls)
    {
        return $this->sendRequest(function () use ($urls) {
            return $this->http->post('/api/files/merge', $this->makeRequest([
                'json' => ['urls' => $urls],
            ]));
        });
    }

    /**
     * Make url merge file
     *
     * @param string $filename
     * @param int $ttl
     * @return string
     */
    public function urlMergeFile($filename, $ttl = null)
    {
        return "{$this->url}/api/files/{$filename}/merge?".http_build_query(['token' => $this->makeToken($ttl ?: $this->ttl)]);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function makeRequest(array $options)
    {
        return array_merge($options, [
            'headers' => ['Authorization' => $this->makeToken($this->ttl)],
        ]);
    }

    /**
     * @param int $ttl
     * @return string
     */
    protected function makeToken($ttl)
    {
        $expiredAt = time() + $ttl;

        return $expiredAt . '-' . hash('sha256', $expiredAt . '-' . $this->key);
    }
}
