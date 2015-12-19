<?php

namespace Saxulum\UserProvider\Model;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

abstract class AbstractUser implements UserInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $plainPassword;

    /**
     * @var string
     */
    protected $repeatedPassword;

    /**
     * @var string
     */
    protected $salt;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var boolean
     */
    protected $enabled = false;

    /**
     * @var array
     */
    protected $roles;

    /**
     * roles
     */
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_USER = 'ROLE_USER';

    public function __construct()
    {
        $this->roles = array();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param  PasswordEncoderInterface $passwordencoder
     */
    public function updatePassword(PasswordEncoderInterface $passwordencoder)
    {
        $this->password = $passwordencoder->encodePassword($this->plainPassword, $this->getSalt());
    }

    public function eraseCredentials()
    {
        $this->plainPassword = '';
        $this->repeatedPassword = '';
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param  string $plainPassword
     * @return $this
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param  string $repeatedPassword
     * @return $this
     */
    public function setRepeatedPassword($repeatedPassword)
    {
        $this->repeatedPassword = $repeatedPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getRepeatedPassword()
    {
        return $this->repeatedPassword;
    }

    /**
     * @param  string $salt
     * @return $this
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param  string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param $role
     * @return $this
     */
    public function addRole($role)
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * @param $role
     * @return $this
     */
    public function removeRole($role)
    {
        $mixKey = array_search($role, $this->roles);
        if (is_numeric($mixKey)) {
            unset($this->roles[$mixKey]);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(new Assert\Callback(function (AbstractUser $user, ExecutionContextInterface $context) {
            if ($user->getPlainPassword() && ($user->getPlainPassword() !== $user->getRepeatedPassword())) {
                $context
                    ->buildViolation('passwords doesn\'t match')
                    ->atPath('plainPassword')
                    ->addViolation()
                ;
            }
        }
        ));
    }

    /**
     * @return array
     */
    public static function getPredefinedRoles()
    {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        $roles = array();
        foreach ($reflectionClass->getConstants() as $constant) {
            if (strpos($constant, 'ROLE_') === 0) {
                $roles[$constant] = $constant;
            }
        }

        return $roles;
    }
}
