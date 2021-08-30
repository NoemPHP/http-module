<?php

declare(strict_types=1);


use Noem\IntegrationTest\NoemFrameworkTestCase;
use Noem\IntegrationTest\NoProviderTrait;

class HttpReadyStateTest extends NoemFrameworkTestCase
{
    use NoProviderTrait;

    public function testReadyState()
    {
        $stateMachine = $this->getContainer()->get('state-machine');
        $this->assertTrue($stateMachine->isInState('http-ready'));
    }
}
