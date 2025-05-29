<?php

namespace AloneFrame\base;

use AloneFrame\base\expand\AloneRedis;
use AloneFrame\base\helper\RedisHelper;

class RedisClient extends AloneRedis{
    public function __construct(array $config = []) {
        $this->isRedis = true;
        $this->config = $config;
        $this->client = new RedisHelper($config);
    }
}