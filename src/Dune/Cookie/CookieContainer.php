<?php

declare(strict_types=1);

namespace Dune\Cookie;

use Dune\Cookie\CookieHandler;

trait CookieContainer
{
    public static function init(): void
    {
        if (is_null(self::$handler)) {
            $container = new \Dune\Container\Container();
            self::$handler = $container->get(CookieHandler::class);
        }
    }
}