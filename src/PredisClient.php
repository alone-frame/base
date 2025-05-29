<?php

namespace AloneFrame\base;

use AloneFrame\base\expand\AloneRedis;
use AloneFrame\base\helper\RedisHelper;

class PredisClient extends AloneRedis{
    public function __construct(array $config = []) {
        $this->isRedis = false;
        $this->config = $config;
        $this->client = new RedisHelper($config);
    }
}