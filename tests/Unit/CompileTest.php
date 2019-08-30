<?php

namespace Exolnet\Envoy\Tests\Unit;

use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;

class CompileTest extends UnitTest
{
    public function testEnvoyConfigurationCanBeCompiled()
    {
        $content = file_get_contents($this->getEnvoyPath());

        $compiler = new Compiler();
        $result = $compiler->compile($content);
        $this->assertEquals(1, preg_match('/\$__container->startMacro\(\'deploy\'\);/s', $result, $matches));
    }

    public function testEnvoyConfigurationCanBeLoaded()
    {
        chdir(dirname($this->getEnvoyMockPath()));

        $container = new TaskContainer();

        $container->load($this->getEnvoyMockPath(), new Compiler);

        $this->assertArrayHasKey('setup', $container->getMacros());
        $this->assertArrayHasKey('deploy:check', $container->getTasks());
        $this->assertArrayHasKey('deploy', $container->getMacros());
    }
}
