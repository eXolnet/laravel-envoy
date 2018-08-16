<?php

namespace Exolnet\Envoy;

use Exolnet\Envoy\Exceptions\EnvoyException;

class ConfigDeploy extends Config
{
    /**
     * @var array
     */
    const DEPLOY_CONFIGURATION_POTENTIAL_FILES = [
        'config/deploy.php',
        'app/config/deploy.php',
    ];

    /**
     * @var array
     */
    protected $data;

    /**
     * @var float
     */
    protected $timeStart;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->timeStart = microtime(true);

        $this->loadDeployConfiguration();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * @param string|null $name
     * @return mixed
     */
    public function getEnvironment($name = null)
    {
        if (! $name) {
            $name = array_get($this->data, 'env', $this->config['default']);
        }

        $config = $this->get('environments.'. $name);

        if (! is_array($config)) {
            throw new EnvoyException('No valid configuration found for environment '. $name);
        }

        return new ConfigEnvironment($name, $config);
    }

    /**
     * @return float
     */
    public function getTimeTotal()
    {
        return microtime(true) - $this->timeStart;
    }

    /**
     * @return string
     */
    protected function getBasePath()
    {
        return array_get($this->data, '__current_cwd', getcwd());
    }

    /**
     * @return void
     */
    protected function loadDeployConfiguration()
    {
        $configurationFile = $this->getDeployConfigurationFile();

        if (! file_exists($configurationFile)) {
            throw new EnvoyException('Unable to find deploy configuration file '. $configurationFile);
        }

        $this->config = include($configurationFile);
    }

    /**
     * @return string
     */
    protected function getDeployConfigurationFile()
    {
        if (isset($this->data['configFile'])) {
            return $this->data['configFile'];
        }

        return $this->guessDeployConfigurationFile();
    }

    /**
     * @return string
     */
    protected function guessDeployConfigurationFile()
    {
        foreach (static::DEPLOY_CONFIGURATION_POTENTIAL_FILES as $potentialFile) {
            $potentialFile = $this->getBasePath() .'/'. $potentialFile;

            if (! file_exists($potentialFile)) {
                continue;
            }

            return $potentialFile;
        }

        throw new EnvoyException(
            'Could not guess deploy configuration file. Please define it with the variable $configFile.'
        );
    }
}
