<?php

namespace Hivokas\LaravelHandlers;

use BadMethodCallException;
use Illuminate\Routing\Controller as BaseController;

abstract class Handler extends BaseController
{
    /**
     * Execute an action on the handler.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        if ($method === '__invoke') {
            return call_user_func_array([$this, $method], $parameters);
        }

        throw new BadMethodCallException('Only __invoke method can be called on handler.');
    }
}
