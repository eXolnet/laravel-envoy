<?php

namespace Exolnet\Envoy\Tests\Unit;

use Exolnet\Envoy\ConfigDeploy;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var array */
    protected $config;

    protected function setUp(): void
    {
        $this->config = new ConfigDeploy();
    }

    public function testConfig()
    {
        $this->config->override('keyNotInConfig', 'test');
        $this->assertTrue($this->config->has('keyNotInConfig'));
        $this->assertEquals('test', $this->config->get('keyNotInConfig'));

        $this->config->forget('keyNotInConfig');
        $this->assertFalse($this->config->has('keyNotInConfig'));

        $this->config->offsetSet('keyNotInConfig', 'test');
        $this->assertTrue($this->config->has('keyNotInConfig'));
        $this->assertTrue($this->config->offsetExists('keyNotInConfig'));
        $this->assertEquals('test', $this->config->get('keyNotInConfig'));
        $this->assertEquals('test', $this->config->offsetGet('keyNotInConfig'));

        $this->config->offsetUnset('keyNotInConfig');
        $this->assertFalse($this->config->has('keyNotInConfig'));
        $this->assertFalse($this->config->offsetExists('keyNotInConfig'));
    }
}
