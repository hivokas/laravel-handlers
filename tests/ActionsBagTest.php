<?php

namespace Hivokas\LaravelHandlers\Tests;

use Hivokas\LaravelHandlers\Support\ActionsBag;

class ActionsBagTest extends AbstractTestCase
{
    public function test_add_if_not_exists_method()
    {
        $bag = new ActionsBag;

        $this->assertEquals(count($bag->get()), 0);

        $bag->addIfNotExists('index');

        $this->assertEquals(count($bag->get()), 1);

        $bag->addIfNotExists('index');

        $this->assertEquals(count($bag->get()), 1);
    }

    public function test_delete_if_exists_method()
    {
        $bag = new ActionsBag;

        $bag->addIfNotExists('index');

        $this->assertEquals(count($bag->get()), 1);

        $bag->deleteIfExists('show');

        $this->assertEquals(count($bag->get()), 1);

        $bag->deleteIfExists('index');

        $this->assertEquals(count($bag->get()), 0);
    }

    public function test_get_method()
    {
        $bag = new ActionsBag;

        $bag->addIfNotExists('index');

        $bag->addIfNotExists('show');

        $this->assertEquals(array_sort_recursive(['index', 'show']), array_sort_recursive($bag->get()));
    }

    public function test_is_empty_method()
    {
        $bag = new ActionsBag;

        $this->assertTrue($bag->isEmpty());

        $bag->addIfNotExists('show');

        $this->assertFalse($bag->isEmpty());
    }
}
