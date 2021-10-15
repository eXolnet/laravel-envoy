<?php

namespace Exolnet\Envoy;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Laravel\Envoy\ConfigurationParser;

/**
 * @see \Laravel\Envoy\Slack
 */
class Slack
{
    use ConfigurationParser;

    /**
     * @var string
     */
    protected $hook;

    /**
     * @var string
     */
    protected $channel;

    /**
     * @var string
     */
    protected $task;

    /**
     * @var string
     */
    protected $project;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $commit;

    /**
     * @var string
     */
    protected $release;

    /**
     * @var string
     */
    protected $appUrl;

    /**
     * @var float
     */
    protected $time;

    /**
     * Create a new Slack instance.
     *
     * @param string $hook
     * @param string $channel
     * @param string $task
     * @param string $project
     * @param string $environment
     * @param string $commit
     * @param string $release
     * @param string $appUrl
     * @param float  $time
     */
    public function __construct($hook, $channel, $task, $project, $environment, $commit, $release, $appUrl, $time)
    {
        if ($hook === null) {
            throw new InvalidArgumentException(
                'Slack URL is not defined (hint: you need to export the EXOLNET_ENVOY_SLACK_URL environment variable).'
            );
        }

        $this->hook = $hook;
        $this->channel = $channel;
        $this->task = $task;
        $this->project = $project;
        $this->environment = $environment;
        $this->commit = $commit;
        $this->release = $release;
        $this->appUrl = $appUrl;
        $this->time = $time;
    }

    /**
     * Create a new Slack message instance.
     *
     * @param \Exolnet\Envoy\ConfigEnvironment $environment
     * @param \Exolnet\Envoy\ConfigDeploy      $deploy
     * @return \Exolnet\Envoy\Slack
     */
    public static function make(ConfigEnvironment $environment, ConfigDeploy $deploy, $task)
    {
        $hook = $deploy->get('slack_url');
        $channel = $deploy->get('slack_channel');
        $project = $deploy->getName();
        $env = $environment->getName();
        $commit = $environment->get('commit');
        $release = $environment->get('release');
        $appUrl = $environment->get('app_url');
        $time = round($deploy->getTimeTotal(), 1);

        return new static($hook, $channel, $task, $project, $env, $commit, $release, $appUrl, $time);
    }

    /**
     * Build the Slack message payload.
     *
     * @return \array[][]
     */
    protected function buildPayload()
    {
        $message = sprintf(
            '*[%s]* _%s_ ran `%s` on _%s_ in %s seconds.',
            $this->project,
            $this->getSystemUser(),
            $this->task,
            $this->environment,
            $this->time
        );

        $fields = [];

        if ($this->commit) {
            $fields[] = [
                'title' => 'Commit',
                'value' => $this->commit,
                'short' => true,
            ];
        }

        if ($this->release) {
            $fields[] = [
                'title' => 'Release',
                'value' => $this->release,
                'short' => true,
            ];
        }

        if ($this->appUrl) {
            $fields[] = [
                'title' => 'URL',
                'value' => $this->appUrl,
                'short' => false,
            ];
        }

        return array_filter([
            'channel' => $this->channel,
            'attachments' => [
                [
                    'color' => 'good',
                    'text' => $message,
                    'mrkdwn_in' => ['text'],
                    'fields' => $fields,
                ],
            ],
        ]);
    }

    /**
     * Send the Slack message.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send()
    {
        $payload = $this->buildPayload();

        $this->makeClient()->post($this->hook, [
            'json' => $payload,
        ]);

        echo 'Slack notification sent.'. PHP_EOL;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function makeClient(): Client
    {
        return new Client();
    }
}
