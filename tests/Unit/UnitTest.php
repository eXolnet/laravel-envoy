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
        return realpath(__DIR__ . '/../../Envoy.blade.php');
    }

    /**
     * @return string
     */
    protected function getEnvoyMockPath()
    {
        return realpath(__DIR__ . '/../Mock/Envoy.blade.php');
    }
}
