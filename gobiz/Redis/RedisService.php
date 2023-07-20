<?php

namespace Gobiz\Redis;

use Predis\ClientInterface;

class RedisService
{
    /**
     * @return ClientInterface
     */
    public static function redis()
    {
        return app('redis');
    }
}