<?php

namespace Saxulum\UserProvider\Silex\Provider;

use Saxulum\UserProvider\Provider\SaxulumUserProvider as PimpleSaxulumUserProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SaxulumUserProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $saxulumUserProvider = new PimpleSaxulumUserProvider();
        $saxulumUserProvider->register($app);
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app) {}
}