<?php

namespace Exolnet\Envoy\Tests\Unit;

use Laravel\Envoy\Compiler;

class CompileTest extends UnitTest
{
    public function testEnvoyConfigurationCanBeCompiled()
    {
        $content = file_get_contents($this->getEnvoyPath());

        $compiler = new Compiler();
        $result = $compiler->compile($content);
        $this->assertEquals(1, preg_match('/\$__container->startMacro\(\'deploy\'\);/s', $result, $matches));
    }
}
