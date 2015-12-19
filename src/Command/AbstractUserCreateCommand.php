<?php

namespace Saxulum\UserProvider\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Saxulum\Console\Command\AbstractPimpleCommand;
use Saxulum\UserProvider\Model\AbstractUser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * based on https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Command/CreateUserCommand.php
 */
class AbstractUserCreateCommand extends AbstractPimpleCommand
{
    /**
     * @var string
     */
    protected $userClass;

    public function __construct($name = null, $userClass)
    {
        $this->userClass = $userClass;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('saxulum:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                new InputOption('admin', null, InputOption::VALUE_NONE, 'Set the user as admin'),
            ))
            ->setHelp(<<<EOT
The <info>saxulum:user:create</info> command creates a user:

  <info>php app/console saxulum:user:create dominik</info>

This interactive shell will ask you for an email and then a password.

You can alternatively specify the email and password as the second and third arguments:

  <info>php app/console saxulum:user:create dominik dominik@example.com mypassword</info>

You can create a super admin via the admin flag:

  <info>php app/console saxulum:user:create admin --admin</info>

EOT
            );
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username   = $input->getArgument('username');
        $email      = $input->getArgument('email');
        $password   = $input->getArgument('password');
        $admin      = $input->getOption('admin');

        $om = $this->getDoctrine()->getManagerForClass($this->userClass);

        $existingUser = $om->getRepository($this->userClass)->findOneBy(array(
            'username' => $username
        ));

        if (!is_null($existingUser)) {
            $output->writeln("<error>User with this username allready exists</error>");
            die();
        }

        /** @var AbstractUser $user */
        $user = new $this->userClass;
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setSalt(uniqid(mt_rand()));
        $user->setEnabled(true);
        $user->addRole('ROLE_USER');

        if ($admin) {
            $user->addRole('ROLE_ADMIN');
        }

        $user->updatePassword($this->container['security.encoder.digest']);

        $om->persist($user);
        $om->flush();

        $output->writeln("<success>New user with username '{$username}' created</success>");
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @throws \Exception
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('username')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a username:',
                function ($username) {
                    if (empty($username)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }

        if (!$input->getArgument('email')) {
            $email = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an email:',
                function ($email) {
                    if (empty($email)) {
                        throw new \Exception('Email can not be empty');
                    }

                    return $email;
                }
            );
            $input->setArgument('email', $email);
        }

        if (!$input->getArgument('password')) {
            $password = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a password:',
                function ($password) {
                    if (empty($password)) {
                        throw new \Exception('Password can not be empty');
                    }

                    return $password;
                }
            );
            $input->setArgument('password', $password);
        }
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->container['doctrine'];
    }
}
