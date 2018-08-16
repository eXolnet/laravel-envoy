<?php

namespace Exolnet\Envoy\Tests\Unit;

use PHPUnit\Framework\TestCase;

abstract class UnitTest extends TestCase
{
    /**
     * @return string
     */
    protected function getEnvoyPath()
    {
        return realpath(__DIR__ .'/../../Envoy.blade.php');
    }
}
