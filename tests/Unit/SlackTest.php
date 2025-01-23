<?php

namespace Exolnet\Envoy\Tests\Unit;

use Exolnet\Envoy\ConfigDeploy;
use Exolnet\Envoy\ConfigEnvironment;
use Exolnet\Envoy\Slack;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Mockery as m;

class SlackTest extends TestCase
{
    /** @var \Exolnet\Envoy\Slack */
    protected $slack;

    public function setUp(): void
    {
        $this->slack = m::mock(Slack::class . '[makeClient]', [
            'TheHook',
            'TheChannel',
            'TheTask',
            'TheProject',
            'TheEnv',
            'TheCommit',
            'TheRelease',
            'TheAppUrl',
            0
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(Slack::class, $this->slack);
    }

    /**
     * @test
     * @return void
     */
    public function testConstructHookIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Slack(null, '', '', '', '', '', '', '', 0);
    }

    /**
     * @test
     * @return void
     */
    public function testMake(): void
    {
        $environment = m::mock(ConfigEnvironment::class);
        $deploy = m::mock(ConfigDeploy::class);
        $task = '';

        $deploy->shouldReceive('get')->with('slack_url')->once()->andReturn('');
        $deploy->shouldReceive('get')->with('slack_channel')->once()->andReturn('');
        $deploy->shouldReceive('getName')->once()->andReturn('');
        $environment->shouldReceive('getName')->once()->andReturn('');
        $environment->shouldReceive('get')->with('commit')->once()->andReturn('');
        $environment->shouldReceive('get')->with('release')->once()->andReturn('');
        $environment->shouldReceive('get')->with('app_url')->once()->andReturn('');
        $deploy->shouldReceive('getTimeTotal')->once()->andReturn(1);

        $this->assertInstanceOf(Slack::class, Slack::make($environment, $deploy, $task));
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function testBuildPayload(): void
    {
        $getSystemUser = new \ReflectionMethod(Slack::class, 'getSystemUser');
        $getSystemUser->setAccessible(true);
        $systemUser = $getSystemUser->invoke($this->slack);

        $fields = [
            [
                'title' => 'Commit',
                'value' => 'TheCommit',
                'short' => true,
            ],
            [
                'title' => 'Release',
                'value' => 'TheRelease',
                'short' => true,
            ],
            [
                'title' => 'URL',
                'value' => 'TheAppUrl',
                'short' => false,
            ]
        ];

        $arrayFilter = [
            'channel' => 'TheChannel',
            'attachments' => [
                [
                    'color' => 'good',
                    'text' => '*[TheProject]* _' . $systemUser . '_ ran `TheTask` on _TheEnv_ in 0 seconds.',
                    'mrkdwn_in' => ['text'],
                    'fields' => $fields,
                ],
            ],
        ] ;

        $buildPayload = new \ReflectionMethod(Slack::class, 'buildPayload');
        $buildPayload->setAccessible(true);

        $this->assertEquals($arrayFilter, $buildPayload->invoke($this->slack));
    }

    /**
     * @test
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function testSend(): void
    {
        $mock = new MockHandler([
            new Response(200),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->slack->shouldAllowMockingProtectedMethods()->shouldReceive('makeClient')->once()->andReturn($client);
        $this->slack->send();

        $this->expectOutputString('Slack notification sent.' . PHP_EOL);
    }
}
