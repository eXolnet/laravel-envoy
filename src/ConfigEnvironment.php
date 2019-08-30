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
     * @var string
     */
    protected $release;

    /**
     * @var \Exolnet\Envoy\ConfigContext
     */
    protected $context;

    /**
     * @param string                       $name
     * @param array                        $config
     * @param \Exolnet\Envoy\ConfigContext $context
     */
    public function __construct($name, array $config, $context)
    {
        $this->name = $name;
        $this->config = $config;
        $this->context = $context;

        $this->set('release', date('YmdHis'));

        $this->overrideConfiguration();

        $this->validateConfiguration();
    }

    /**
     * @return void
     */
    protected function overrideConfiguration()
    {
        $this->override('release', $this->context->get('release'));
        $this->override('commit', $this->context->get('commit'));
        $this->override('ssh_host', $this->context->get('ssh-host'));
        $this->override('ssh_user', $this->context->get('ssh-user'));
        $this->override('deploy_path', $this->context->get('deploy-path'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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

        if (! $this->get('cron_mailto') && $this->get('cron_jobs')) {
            throw new EnvoyException('Cron MAILTO is not defined for environment '. $this->name);
        }
    }
}
