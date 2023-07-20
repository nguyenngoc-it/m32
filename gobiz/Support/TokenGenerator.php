<?php

namespace Gobiz\Support;

use InvalidArgumentException;

class TokenGenerator
{
    /**
     * @var string|null
     */
    protected $secret;

    /**
     * @var string
     */
    public $delimiter = '-';

    /**
     * @var string
     */
    public $algorithm = 'sha256';

    /*
     * Errors
     */
    const ERROR_TOKEN_INVALID = 'TOKEN_INVALID';
    const ERROR_TOKEN_EXPIRED = 'TOKEN_EXPIRED';

    /**
     * TokenGenerator constructor
     *
     * @param string $secret
     */
    public function __construct($secret = null)
    {
        $this->secret = $secret.config('app.key');
    }

    /**
     * Make token
     *
     * @param string $data
     * @param int $ttl
     * @return string
     */
    public function make($data, $ttl = 60)
    {
        $expiredAt = time() + $ttl;
        $payload = $expiredAt.$this->delimiter.$data;

        return $this->makeSign($payload).$this->delimiter.$payload;
    }

    /**
     * Parse token
     *
     * @param string $token
     * @return string
     * @throws InvalidArgumentException
     */
    public function parse($token)
    {
        $args = explode($this->delimiter, $token, 3);

        if (count($args) !== 3) {
            throw new InvalidArgumentException(static::ERROR_TOKEN_INVALID);
        }

        list($sign, $expiredAt, $data) = $args;

        if ($this->makeSign($expiredAt . $this->delimiter . $data) !== $sign) {
            throw new InvalidArgumentException(static::ERROR_TOKEN_INVALID);
        }

        if (intval($expiredAt) < time()) {
            throw new InvalidArgumentException(static::ERROR_TOKEN_EXPIRED);
        }

        return $data;
    }

    /**
     * @param string $payload
     * @return string
     */
    protected function makeSign($payload)
    {
        return hash($this->algorithm, $this->secret.$payload);
    }
}
