<?php

namespace Exolnet\Envoy;

use Exolnet\Envoy\Exceptions\EnvoyException;

class ConfigEnvironment extends Config
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     * @param array $config
     */
    public function __construct($name, array $config)
    {
        $this->name = $name;
        $this->config = $config;

        $this->validateConfiguration();
    }

    /**
     * @param string $path
     * @return string
     */
    public function getDeployPath($path = '')
    {
        return rtrim($this->get('deploy_to'), '/') . ($path ? '/'. $path : '');
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function getDeployReleasePath($path = null)
    {
        if (! $path) {
            $path = date('YmdHis');
        }

        return $this->getDeployPath('releases/'. $path);
    }

    /**
     * @return void
     */
    protected function validateConfiguration()
    {
        if (! $this->get('server')) {
            throw new EnvoyException('Server URL is not defined for environment '. $this->name);
        }

        if (! $this->get('repo_url')) {
            throw new EnvoyException('Repository URL is not defined for environment '. $this->name);
        }
    }
}
