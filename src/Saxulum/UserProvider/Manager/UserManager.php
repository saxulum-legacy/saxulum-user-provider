<?php

namespace Saxulum\UserProvider\Manager;

use Saxulum\UserProvider\Model\AbstractUser;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserManager
{
    /**
     * @var PasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @param PasswordEncoderInterface $passwordEncoder
     */
    public function __construct(PasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param AbstractUser $user
     * @throws \Exception
     */
    public function update(AbstractUser $user)
    {
        $user->setSalt(uniqid(mt_rand()));
        if(!$user->updatePassword($this->passwordEncoder)) {
            throw new \Exception("Can't update password!");
        }
    }
}