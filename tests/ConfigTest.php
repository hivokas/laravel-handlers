<?php

namespace Hivokas\LaravelHandlers\Tests;

use Hivokas\LaravelHandlers\Handler;

class ConfigTest extends AbstractTestCase
{
    public function test_default_config_values()
    {
        $this->assertEquals(config('handlers.base'), Handler::class);
    }
}
