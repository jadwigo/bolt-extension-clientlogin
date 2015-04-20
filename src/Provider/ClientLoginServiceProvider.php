<?php

namespace Bolt\Extension\Bolt\ClientLogin\Provider;

use Bolt\Extension\Bolt\ClientLogin\Database;
use Bolt\Extension\Bolt\ClientLogin\Session;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session as SessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

class ClientLoginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['clientlogin.session'] = $app->share(
            function ($app) {
                $session = new Session($app);

                return $session;
            }
        );

        $app['clientlogin.session.handler'] = $app->share(
            function ($app) {
                $options = ['cookie_lifetime' => 3600, 'cookie_httponly' => false];
                $storage = new NativeSessionStorage($options, new NativeFileSessionHandler());
                $handler = new SessionHandler($storage);

                return $handler;
            }
        );

        $app['clientlogin.db'] = $app->share(
            function ($app) {
                $records = new Database($app);

                return $records;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
