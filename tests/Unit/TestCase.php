<?php

namespace Exolnet\Envoy\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
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
