<?php
namespace Pluf\Tests\Mocks;

use InvalidArgumentException;

class MockAction
{

    public function __call($name, array $arguments)
    {
        if (count($arguments) !== 3) {
            throw new InvalidArgumentException("Not a Pluf call");
        }

        $arguments[1]->write(json_encode(compact('name') + [
            'arguments' => $arguments[2]
        ]));

        return $arguments[1];
    }
}
