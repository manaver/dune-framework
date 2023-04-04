<?php

declare(strict_types=1);

namespace Dune\Routing;

use Dune\Http\Request;
use Dune\Exception\NotFound;
use Dune\Exception\MethodNotSupported;
use Dune\Csrf\Csrf;
use Dune\Session\Session;
use Dune\Container\Container;

class Action extends Router
{
    /**
     * Check the route exist and pass to other method to run,
     *
     * @param  string  $uri
     * @param  string  $requestMethod
     *
     * @throw \MethodNotSupported
     * @throw \NotFound
     *
     * @return string|null
     */
    protected static function tryRun($uri, $requestMethod): mixed
    {
        $url = parse_url($uri);

        foreach (self::$routes as $route) {
            if (
                $route['route'] == $url['path'] &&
                $route['method'] != $requestMethod
            ) {
                throw new MethodNotSupported("Exception : {$requestMethod} Method Not Supported For This Route, Supported Method {$route['method']}");
            }
            if (
                $route['route'] == $url['path'] &&
                $route['method'] == $requestMethod
            ) {
                if ($requestMethod == 'POST' || $requestMethod == 'PUT' || $requestMethod == 'PATCH' || $requestMethod == 'DELETE') {
                    $request = new Request();
                    if (!Csrf::validate(Session::get('_token'), $request->get('_token'))) {
                        abort(419, 'Page Expired');
                        exit;
                    }
                }
                $action = $route['action'];
                if ($route['middleware']) {
                    $middleware = \App\Middleware\Middleware::MAP[$route['middleware']] ?? false;
                    if (!$middleware) {
                        throw new NotFound("Exception : Middleware {$route['middleware']} Not Found");
                    }
                    self::callMiddleware($middleware);
                }
                if (is_callable($action)) {
                    return self::runCallable($action);
                }
                if (is_array($action)) {
                    return self::runMethod($action);
                }
                if (is_string($action)) {
                    return self::renderView($action);
                }
            }
        }
        throw new NotFound("Exception : Route Not Found By This URI {$url['path']}");
    }
    /**
     * will run method in route
     *
     * @param  array  $action
     *
     * @throw \NotFound
     *
     * @return string|null
     */
    protected static function runMethod(array $action): mixed
    {
        [$class, $method] = $action;
        if (class_exists($class)) {
            $container = new Container();
            $class = $container->get($class);
        } else {
            throw new NotFound("Exception : Class {$class} Not Found");
        }
        if (method_exists($class, $method)) {
            return call_user_func_array([$class, $method], [new Request()]);
        }
        throw new NotFound("Exception : Method {$method} Not Found");
    }
}
