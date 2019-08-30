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
     * @param string $basePath
     * @param string $relativePath
     * @return string
     */
    public function getRelativePath($basePath, $relativePath = '')
    {
        return rtrim($basePath, '/') . ($relativePath ? '/'. ltrim($relativePath, '/') : '');
    }

    /**
     * @param string $path
     * @return string
     */
    public function getDeployPath($path = '')
    {
        return $this->getRelativePath($this->get('deploy_path'), $path);
    }

    /**
     * @param string $release
     * @param string $path
     * @return string
     */
    public function getReleasePath($release, $path = '')
    {
        return $this->getRelativePath($this->getRelativePath($this->getDeployPath('releases'), $release), $path);
    }

    /**
     * @return string
     */
    public function buildFingerprint()
    {
        return sha1($this->get('server') . $this->get('deploy_path'));
    }

    /**
     * @return string
     */
    public function buildServerString()
    {
        $options = '-qA'; // Same as '-q -A'

        if ($this->has('ssh_options')) {
            $options .= ' '. trim($this->get('ssh_options'));
        }

        return $options .' '. $this->get('ssh_user') .'@'. $this->get('ssh_host');
    }

    /**
     * @return array
     */
    public function extractVariables()
    {
        return [
            // Variables defined in the configuration file (or overwritten by context)
            'release'                 => $this->get('release'),
            'commit'                  => $this->get('commit'),
            'sshHost'                 => $this->get('ssh_host'),
            'sshUser'                 => $this->get('ssh_user'),
            'deployPath'              => $this->get('deploy_path'),

            // Variables defined in the configuration file
            'sshOptions'              => $this->get('ssh_options', ''),
            'repositoryUrl'           => $this->get('repository_url'),
            'linkedFiles'             => $this->get('linked_files', []),
            'linkedDirs'              => $this->get('linked_dirs', []),
            'copiedFiles'             => $this->get('copied_files', []),
            'copiedDirs'              => $this->get('copied_dirs', []),
            'cronJobs'                => $this->get('cron_jobs', null),
            'cronMailTo'              => $this->get('cron_mailto', ''),
            'keepReleases'            => $this->get('keep_releases', 5),
            'cmdGit'                  => $this->get('cmd_git', 'git'),
            'cmdNpm'                  => $this->get('cmd_npm', 'npm'),
            'cmdYarn'                 => $this->get('cmd_npm', 'yarn'),
            'cmdBower'                => $this->get('cmd_bower', 'bower'),
            'cmdGrunt'                => $this->get('cmd_grunt', 'grunt'),
            'cmdPhp'                  => $this->get('cmd_php', 'php'),
            'cmdComposer'             => $this->get('cmd_composer', 'composer'),
            'cmdComposerOptions'      => $this->get('cmd_composer_options', '--no-dev'),

            // Variables computed internally
            'fingerprint'             => $this->buildFingerprint(),
            'serverString'            => $this->buildServerString(),

            // Variables computed internally that defined paths
            'repoPath'                => $this->getDeployPath('repo'),
            'repositoryPath'          => $this->getDeployPath('repository'),
            'currentPath'             => $this->getDeployPath('current'),
            'releasesPath'            => $this->getDeployPath('releases'),
            'releasePath'             => $this->getReleasePath($this->get('release')),
            'assetsPath'              => $this->getReleasePath($this->get('release'), $this->get('assets_path', '')),
            'sharedPath'              => $this->getDeployPath('shared'),
            'backupsPath'             => $this->getDeployPath('backups'),
        ];
    }

    /**
     * @return void
     */
    protected function validateConfiguration()
    {
        if (! $this->get('ssh_host')) {
            throw new EnvoyException('SSH host is not defined.');
        }

        if (! $this->get('ssh_user')) {
            throw new EnvoyException('SSH user is not defined.');
        }

        if (! $this->get('deploy_path')) {
            throw new EnvoyException('Deploy path is not defined.');
        }

        if (! $this->get('repository_url')) {
            throw new EnvoyException('Repository URL is not defined.');
        }

        if (! $this->get('cron_mailto') && $this->get('cron_jobs')) {
            throw new EnvoyException('Cron MAILTO is not defined.');
        }
    }
}
