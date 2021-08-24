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
     * @var \Exolnet\Envoy\ConfigContext
     */
    protected $context;

    /**
     * @var float
     */
    protected $timeStart;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->context = new ConfigContext($data);
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
            $name = $this->context->get('env', $this->config['default']);
        }

        $config = $this->get('environments.'. $name);

        if (! is_array($config)) {
            throw new EnvoyException('No valid configuration found for environment '. $name);
        }

        return new ConfigEnvironment($name, $config, $this->context);
    }

    /**
     * @return float
     */
    public function getTimeTotal()
    {
        return microtime(true) - $this->timeStart;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function detectSlack()
    {
        if ($this->get('slack') !== null) {
            // phpcs:disable Generic.Files.LineLength.TooLong
            echo PHP_EOL;
            echo "\033[30;43m                                                                                           \033[39;49m". PHP_EOL;
            echo "\033[30;43m [WARNING] The 'slack' key is deprecated and should be removed from the 'deploy.php' file. \033[39;49m". PHP_EOL;
            echo "\033[30;43m                                                                                           \033[39;49m". PHP_EOL;
            echo PHP_EOL;
            // phpcs:enable
        }

        // Ensure that it is not possible to set those keys in the deploy.php file
        $this->forget(['slack_url', 'slack_channel']);

        $url = getenv('EXOLNET_ENVOY_SLACK_URL') ?: null;

        if ($url !== null) {
            $this->set('slack_url', $url);
        } else {
            // phpcs:disable Generic.Files.LineLength.TooLong
            echo PHP_EOL;
            echo "\033[30;43m                                                                                           \033[39;49m". PHP_EOL;
            echo "\033[30;43m [WARNING] No slack URL have been defined.                                                 \033[39;49m". PHP_EOL;
            echo "\033[30;43m                                                                                           \033[39;49m". PHP_EOL;
            echo "\033[30;43m The 'EXOLNET_ENVOY_SLACK_URL' environment variable need to be set and passed to envoy.    \033[39;49m". PHP_EOL;
            echo "\033[30;43m                                                                                           \033[39;49m". PHP_EOL;
            echo PHP_EOL;
            // phpcs:enable
        }

        $channel = getenv('EXOLNET_ENVOY_SLACK_CHANNEL') ?: null;

        if ($channel !== null) {
            $this->set('slack_channel', $channel);
        }
    }

    /**
     * @return string
     */
    protected function getBasePath()
    {
        return getcwd();
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
        if ($this->context->get('configFile')) {
            return $this->getBasePath() .'/'. $this->context->get('configFile');
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
