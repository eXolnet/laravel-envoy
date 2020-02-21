<?php

namespace Exolnet\Envoy\Tests\Unit;

use Exolnet\Envoy\ConfigContext;
use Exolnet\Envoy\ConfigEnvironment;
use Generator;
use Mockery as m;

class ConfigEnvironmentTest extends UnitTest
{
    /**
     * @var array
     */
    const BASE_CONFIG = [
        'ssh_host' => 'hostname',
        'ssh_user' => 'user',
        'deploy_path' => '/deployment/path',
        'repository_url' => 'ssh://git@hostname/repository.git',
    ];

    /**
     * @var \Exolnet\Envoy\ConfigContext|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $context;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->context = m::mock(ConfigContext::class);

        $this->context->shouldReceive('get')->andReturn(null);
    }


    /**
     * @return void
     */
    public function testEnvoyConfigurationCanBeCompiled(): void
    {
        $config = new ConfigEnvironment('foo', static::BASE_CONFIG, $this->context);

        $this->assertInstanceOf(ConfigEnvironment::class, $config);
    }

    /**
     * @return void
     * @dataProvider provideTestBuildServerString
     */
    public function testBuildServerString($host, $user, $options, $expected): void
    {
        $overwrites = [
            'ssh_host' => $host,
            'ssh_user' => $user,
            'ssh_options' => $options,
        ];

        $config = new ConfigEnvironment('foo', $overwrites + static::BASE_CONFIG, $this->context);

        $this->assertEquals($expected, $config->buildServerString());
    }

    /**
     * @return \Generator
     */
    public function provideTestBuildServerString(): Generator
    {
        yield ['127.0.0.1', '', '', '127.0.0.1'];
        yield ['hostname', '', '', '-qA hostname'];
        yield ['hostname', 'user', '', '-qA user@hostname'];
        yield ['hostname', '', '-p', '-qA -p hostname'];
        yield ['hostname', 'user', '-p', '-qA -p user@hostname'];
    }
}
