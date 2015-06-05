<?php

namespace Saxulum\UserProvider\Form;

use Saxulum\UserProvider\Model\AbstractUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractUserType extends AbstractType
{
    /**
     * @var string
     */
    protected $userClass;

    /**
     * @param string $userClass
     */
    public function __construct($userClass)
    {
        $this->userClass = $userClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('plainpassword', 'repeated', array('type' => 'password', 'required' => false))
            ->add('email', 'email')
            ->add('roles', 'choice', array(
                'choices' => AbstractUser::getPredefinedRoles(),
                'multiple' => true,
                'required' => true
            ))
            ->add('enabled', 'checkbox', array('required' => false))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => $this->userClass
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }
}
