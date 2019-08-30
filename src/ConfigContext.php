<?php

namespace Exolnet\Envoy;

class ConfigContext extends Config
{
    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
