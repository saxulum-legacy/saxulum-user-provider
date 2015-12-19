<?php

namespace Saxulum\UserProvider\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Saxulum\UserProvider\Manager\UserManager;
use Saxulum\UserProvider\Model\AbstractUser;

class SaxulumUserProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['saxulum.userprovider.name'] = 'default';
        $container['saxulum.userprovider.pattern'] = '/';
        $container['saxulum.userprovider.loginpath'] = 'login';
        $container['saxulum.userprovider.checkpath'] = 'login_check';
        $container['saxulum.userprovider.logoutpath'] = 'logout';
        $container['saxulum.userprovider.objectmanagerkey'] = 'orm.em';
        $container['saxulum.userprovider.userclass'] = '';
        $container['saxulum.userprovider.anonymous'] = true;

        $container['security.firewalls'] = function () use ($container) {
            return array(
                $container['saxulum.userprovider.name'] => array(
                    'pattern' => $container['saxulum.userprovider.pattern'],
                    'form' => array(
                        'login_path' => $container['saxulum.userprovider.loginpath'],
                        'check_path' => $container['saxulum.userprovider.checkpath'],
                    ),
                    'logout' => array(
                        'logout_path' => $container['saxulum.userprovider.logoutpath']
                    ),
                    'users' => function () use ($container) {
                        return new UserProvider(
                            $container[$container['saxulum.userprovider.objectmanagerkey']],
                            $container['saxulum.userprovider.userclass']
                        );
                    },
                    'anonymous' => $container['saxulum.userprovider.anonymous'],
                ),
            );
        };

        $container['saxulum.userprovider.manager'] = function () use ($container) {
            return new UserManager($container['security.encoder.digest']);
        };

        $container['security.access_rules'] = function () {
            return array(
                array('^/[^/]*/login', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            );
        };

        $container['security.role_hierarchy'] = function () {
            return array(
                AbstractUser::ROLE_ADMIN => array(AbstractUser::ROLE_USER),
            );
        };
    }
}
