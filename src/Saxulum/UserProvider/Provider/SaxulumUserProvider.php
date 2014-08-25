<?php

namespace Saxulum\UserProvider\Provider;

use Saxulum\UserProvider\Manager\UserManager;
use Saxulum\UserProvider\Model\AbstractUser;

class SaxulumUserProvider
{
    /**
     * @param \Pimple $container
     */
    public function register(\Pimple $container)
    {
        $container['saxulum.userprovider.name'] = 'default';
        $container['saxulum.userprovider.pattern'] = '/';
        $container['saxulum.userprovider.loginpath'] = 'login';
        $container['saxulum.userprovider.checkpath'] = 'login_check';
        $container['saxulum.userprovider.logoutpath'] = 'logout';
        $container['saxulum.userprovider.objectmanagerkey'] = 'orm.em';
        $container['saxulum.userprovider.userclass'] = '';
        $container['saxulum.userprovider.anonymous'] = true;

        $container['security.firewalls'] = $container->share(function () use ($container) {
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
                    'users' => $container->share(function () use ($container) {
                        return new UserProvider(
                            $container[$container['saxulum.userprovider.objectmanagerkey']],
                            $container['saxulum.userprovider.userclass']
                        );
                    }),
                    'anonymous' => $container['saxulum.userprovider.anonymous'],
                ),
            );
        });

        $container['saxulum.userprovider.manager'] = $container->share(function () use ($container) {
            return new UserManager($container['security.encoder.digest']);
        });

        $container['security.access_rules'] = $container->share(function () {
            return array(
                array('^/[^/]*/login', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            );
        });

        $container['security.role_hierarchy'] = $container->share(function () {
            return array(
                AbstractUser::ROLE_ADMIN => array(AbstractUser::ROLE_USER),
            );
        });
    }
}
