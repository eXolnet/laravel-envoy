<?php

namespace Exolnet\Envoy\Tests\Unit;

use Exolnet\Envoy\ConfigDeploy;
use Exolnet\Envoy\ConfigEnvironment;
use Exolnet\Envoy\Slack;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SlackTest extends TestCase
{
    /** @var \Exolnet\Envoy\Slack */
    protected $slack;

    public function setUp(): void
    {
        $this->slack = new Slack(
            'https://localhost/',
            'TheChannel',
            'TheTask',
            'TheProject',
            'TheEnv',
            'TheCommit',
            'TheRelease',
            'TheAppUrl',
            0
        );
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSend(): void
    {
        $this->slack->send();

        // Needed to assert something to have the coverage on the test
        $this->assertTrue(true);
    }
}
