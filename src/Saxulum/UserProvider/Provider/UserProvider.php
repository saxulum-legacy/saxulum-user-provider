<?php

namespace Saxulum\UserProvider\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Saxulum\UserProvider\Model\AbstractUser;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserProvider implements UserProviderInterface
{
    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var string
     */
    protected $userClass;

    /**
     * @param ObjectManager $om
     * @param string        $userClass
     */
    public function __construct(ObjectManager $om, $userClass)
    {
        $this->om = $om;
        $this->userClass = $userClass;
    }

    /**
     * @param  string                    $username
     * @return AbstractUser
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
        /** @var AbstractUser $objUser */
        $objUser = $this->om->getRepository($this->userClass)->findOneBy(array('username' => $username));

        if (is_null($objUser)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $objUser;
    }

    /**
     * @param  UserInterface            $user
     * @return AbstractUser
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof AbstractUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param  string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === $this->userClass;
    }
}
