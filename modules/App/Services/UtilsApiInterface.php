<?php

namespace Modules\App\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface UtilsApiInterface
{
    /**
     * Merge files
     *
     * @param array $urls
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function mergeFiles(array $urls);

    /**
     * Make url merge file
     *
     * @param string $filename
     * @param int $ttl
     * @return string
     */
    public function urlMergeFile($filename, $ttl = null);
}
