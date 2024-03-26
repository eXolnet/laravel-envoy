<?php

namespace Exolnet\Envoy\Tests\Unit;

use Exolnet\Envoy\ConfigContext;
use Exolnet\Envoy\ConfigEnvironment;
use Exolnet\Envoy\Exceptions\EnvoyException;
use Generator;
use Mockery as m;

class ConfigEnvironmentTest extends UnitTest
{
    /**
     * @var array
     */
    protected const BASE_CONFIG = [
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
     * @dataProvider provideTestBuildValidServerString
     */
    public function testBuildValidServerString($host, $user, $options, $expected): void
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
    public static function provideTestBuildValidServerString(): Generator
    {
        yield ['local', '', '', 'local'];
        yield ['localhost', '', '', 'localhost'];
        yield ['127.0.0.1', '', '', '127.0.0.1'];
        yield ['localhost', 'user', '', '-A -o LogLevel=error user@localhost'];
        yield ['127.0.0.1', 'user', '-p', '-A -o LogLevel=error -p user@127.0.0.1'];
        yield ['hostname', 'user', '', '-A -o LogLevel=error user@hostname'];
        yield ['hostname', 'user', '-p', '-A -o LogLevel=error -p user@hostname'];
    }

    /**
     * @return void
     * @dataProvider provideTestBuildInvalidServerString
     */
    public function testBuildInvalidServerString($host, $user, $options): void
    {
        $this->expectException(EnvoyException::class);

        $overwrites = [
            'ssh_host' => $host,
            'ssh_user' => $user,
            'ssh_options' => $options,
        ];

        $config = new ConfigEnvironment('foo', $overwrites + static::BASE_CONFIG, $this->context);

        $config->buildServerString();
    }

    /**
     * @return \Generator
     */
    public static function provideTestBuildInvalidServerString(): Generator
    {
        yield ['hostname', '', ''];
        yield ['hostname', '', '-p'];
        yield ['', 'user', ''];
        yield ['', 'user', '-p'];
    }
}
