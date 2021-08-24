<?php

namespace Exolnet\Envoy;

use Exception;
use Exolnet\Envoy\Exceptions\EnvoyException;
use Laravel\Envoy\SSH;
use Symfony\Component\Process\Process;

class ConfigEnvironment extends Config
{
    /**
     * @var array
     */
    public const LOCAL_HOSTS = ['local', 'localhost', '127.0.0.1'];

    /**
     * @var string
     */
    public const INVALID_RELEASE = '__INVALID__';

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
        $this->override('current', $this->context->get('current'));
        $this->override('release', $this->context->get('release'));
        $this->override('commit', $this->context->get('commit'));
        $this->override('ssh_host', $this->context->get('ssh_host'));
        $this->override('ssh_user', $this->context->get('ssh_user'));
        $this->override('deploy_path', $this->context->get('deploy_path'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function detectCurrentRelease()
    {
        $task = $this->context->get('__container')->getTask('releases:current');
        $release = self::INVALID_RELEASE;
        $errors = [];

        (new SSH())->run($task, function ($type, $host, $line) use (&$release, &$errors) {
            if ($type === Process::OUT) {
                $release = trim($line);
            } else {
                $errors[] = rtrim($line);
            }
        });

        if ($errors) {
            throw new Exception('Unable to detect the current version. Reason:'. PHP_EOL . implode(PHP_EOL, $errors));
        }

        if (! $release || $release === self::INVALID_RELEASE) {
            throw new Exception('Unable to detect the current version. No releases were found.');
        }

        $this->set('release', $release);
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
        // This method should have computed an hash combining the server
        // string and the deploy path (see the line bellow), but I forgot to
        // fix this method when splitting 'server' into 'ssh_user' and
        // 'ssh_host'. Since fixing this would cause issue with all existing
        // cronjobs and not fixing this does not, I'm leaving this comment
        // as an explanation why I did not fix it and left it as-is.
        //
        // return sha1($this->get('ssh_user') . '@' . $this->get('ssh_host') . ':' . $this->get('deploy_path'));

        return sha1($this->get('deploy_path'));
    }

    /**
     * @return string
     */
    public function buildServerString()
    {
        $host = $this->get('ssh_host');

        if ($this->get('ssh_user')) {
            $host = $this->get('ssh_user') .'@'. $host;
        }

        if ($this->isLocalHost($host)) {
            return $host;
        }

        // Default options:
        // [-A] Forward ssh agent
        // [-o LogLevel=error] Suppress ssh banner
        $options = '-A -o LogLevel=error';

        if ($this->get('ssh_options')) {
            $options .= ' '. trim($this->get('ssh_options'));
        }

        return $options .' '. $host;
    }

    /**
     * @return array
     */
    public function extractVariables()
    {
        return [
            // Variables defined in the configuration file (or overwritten by context)
            'current'                 => $this->get('current'),
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

        if (! $this->get('ssh_user') && ! $this->isLocalHost()) {
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

    /**
     * @param null $host
     * @return bool
     */
    protected function isLocalHost($host = null)
    {
        return in_array($host ?? $this->get('ssh_host'), static::LOCAL_HOSTS);
    }
}
