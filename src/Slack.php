<?php

namespace Exolnet\Envoy;

use GuzzleHttp\Client;
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
     * @param float  $time
     */
    public function __construct($hook, $channel, $task, $project, $environment, $commit, $release, $time)
    {
        $this->hook = $hook;
        $this->channel = $channel;
        $this->task = $task;
        $this->project = $project;
        $this->environment = $environment;
        $this->commit = $commit;
        $this->release = $release;
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
        $slack = $environment->get('slack') ?: $deploy->get('slack');
        $hook = $slack['url'];
        $channel = $slack['channel'] ?? '#deployments';
        $project = $deploy->getName();
        $env = $environment->getName();
        $commit = $environment->get('commit');
        $release = $environment->get('release');
        $time = round($deploy->getTimeTotal(), 1);

        return new static($hook, $channel, $task, $project, $env, $commit, $release, $time);
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

        return [
            'channel' => $this->channel,
            'attachments' => [
                [
                    'color' => 'good',
                    'text' => $message,
                    'mrkdwn_in' => ['text'],
                    'fields' => [
                        [
                            'title' => 'Commit',
                            'value' => $this->commit,
                            'short' => true,
                        ],
                        [
                            'title' => 'Release',
                            'value' => $this->release,
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];
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

        (new Client())->post($this->hook, [
            'json' => $payload,
        ]);
    }
}
