<?php

namespace Saxulum\UserProvider;

use Saxulum\UserProvider\Model\AbstractUser;
use Saxulum\UserProvider\Provider\UserProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SaxulumUserProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['saxulum.userprovider.name'] = 'default';
        $app['saxulum.userprovider.pattern'] = '/';
        $app['saxulum.userprovider.loginpath'] = 'login';
        $app['saxulum.userprovider.checkpath'] = 'login_check';
        $app['saxulum.userprovider.logoutpath'] = 'logout';
        $app['saxulum.userprovider.objectmanagerkey'] = 'orm.em';
        $app['saxulum.userprovider.userclass'] = '';
        $app['saxulum.userprovider.anonymous'] = true;

        $app['security.firewalls'] = $app->share(function () use ($app) {
            return array(
                $app['saxulum.userprovider.name'] => array(
                    'pattern' => $app['saxulum.userprovider.pattern'],
                    'form' => array(
                        'login_path' => $app['saxulum.userprovider.loginpath'],
                        'check_path' => $app['saxulum.userprovider.checkpath'],
                    ),
                    'logout' => array(
                        'logout_path' => $app['saxulum.userprovider.logoutpath']
                    ),
                    'users' => $app->share(function () use ($app) {
                        return new UserProvider(
                            $app[$app['saxulum.userprovider.objectmanagerkey']],
                            $app['saxulum.userprovider.userclass']
                        );
                    }),
                    'anonymous' => $app['saxulum.userprovider.anonymous'],
                ),
            );
        });

        $app['security.access_rules'] = array(
            array('^/[^/]*/login', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        );

        $app['security.role_hierarchy'] = array(
            AbstractUser::ROLE_ADMIN => array(AbstractUser::ROLE_USER),
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app) {}
}
