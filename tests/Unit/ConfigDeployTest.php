<?php

namespace Exolnet\Envoy\Tests\Unit;

use Exolnet\Envoy\ConfigDeploy;

class ConfigDeployTest extends TestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function testDetectSlack(): void
    {
        $config = new ConfigDeploy();
        $config->set('slack', '');
        $config->detectSlack();
        $this->assertEquals('#general', $config->get('slack_channel'));
        $this->assertEquals('http://localhost/', $config->get('slack_url'));

        // The formatting seems broken, but it fits the message echoed
        $this->expectOutputString(
            '
[30;43m                                                                                           [39;49m
[30;43m [WARNING] The \'slack\' key is deprecated and should be removed from the \'deploy.php\' file. [39;49m
[30;43m                                                                                           [39;49m

'
        );
    }

    /**
     * @test
     * @return void
     */
    public function testGetName(): void
    {
        $config = new ConfigDeploy();

        $config->set('name', 'test');
        $this->assertEquals('test', $config->getName());
    }
}
