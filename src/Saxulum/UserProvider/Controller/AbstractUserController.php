<?php

namespace Saxulum\UserProvider\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Saxulum\UserProvider\Model\AbstractUser;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractUserController
{
    protected $entityClass;
    protected $formTypeClass;
    protected $listRoute;
    protected $editRoute;
    protected $showRoute;
    protected $deleteRoute;
    protected $listTemplate;
    protected $editTemplate;
    protected $showTemplate;
    protected $transPrefix;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var PasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @param ManagerRegistry          $doctrine
     * @param FormFactoryInterface     $formFactory
     * @param PasswordEncoderInterface $passwordEncoder
     * @param SecurityContextInterface $security
     * @param TranslatorInterface      $translator
     * @param \Twig_Environment        $twig
     * @param UrlGeneratorInterface    $urlGenerator
     */
    public function __construct(
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        PasswordEncoderInterface $passwordEncoder,
        SecurityContextInterface $security,
        TranslatorInterface $translator,
        \Twig_Environment $twig,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->doctrine = $doctrine;
        $this->formFactory = $formFactory;
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
        $this->translator = $translator;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param  Request  $request
     * @return Response
     */
    protected function listAction(Request $request)
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException("permission denied to show users");
        }

        $entities = $this
            ->doctrine
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass)
            ->findAll()
        ;

        return $this->render($this->listTemplate, array(
            'entities' => $entities,
            'listroute' => $this->listRoute,
            'editroute' => $this->editRoute,
            'showroute' => $this->showRoute,
            'deleteroute' => $this->deleteRoute,
            'transprefix' => $this->transPrefix,
        ));
    }

    /**
     * @param  Request    $request
     * @param  int|string $id
     * @return Response
     */
    protected function showAction(Request $request, $id)
    {
        $entity = $this
            ->doctrine
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass)
            ->find($id)
        ;

        if (is_null($entity)) {
            throw new NotFoundHttpException("entity with id {$id} not found!");
        }

        if(!$this->security->isGranted('ROLE_ADMIN') &&
            $entity->getId() !== $this->getUser()->getId()) {
            throw new AccessDeniedException("permission denied to show user with {$id}");
        }

        return $this->render($this->showTemplate, array(
            'entity' => $entity,
            'listroute' => $this->listRoute,
            'editroute' => $this->editRoute,
            'showroute' => $this->showRoute,
            'deleteroute' => $this->deleteRoute,
            'transprefix' => $this->transPrefix,
        ));
    }

    /**
     * @param  Request                   $request
     * @param  int|string                $id
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, $id)
    {
        $om = $this->doctrine->getManagerForClass($this->entityClass);

        if (!is_null($id)) {
            /** @var AbstractUser $entity */
            $entity = $om->getRepository($this->entityClass)->find($id);

            if (is_null($entity)) {
                throw new NotFoundHttpException("user with id {$id} not found!");
            }
            if(!$this->security->isGranted('ROLE_ADMIN') &&
                $entity->getId() !== $this->getUser()->getId()) {
                throw new AccessDeniedException("permission denied to edit user with {$id}");
            }
        } else {
            /** @var AbstractUser $entity */
            $entity = new $this->entityClass;
            $entity->setSalt(uniqid(mt_rand()));
        }

        $reflectionClass = new \ReflectionClass($this->formTypeClass);
        $formType = $reflectionClass->newInstance($this->entityClass);

        $form = $this->createForm($formType, $entity);

        if ('POST' == $request->getMethod()) {
            $form->submit($request);

            if ($form->isValid()) {
                if ($entity->updatePassword($this->passwordEncoder)) {
                    if ($entity->getId() == $this->getUser()->getId()) {
                        $entity->addRole(AbstractUser::ROLE_ADMIN);
                    }

                    $om->persist($entity);
                    $om->flush();

                    if ($request->request->get('saveandclose', false)) {
                        return new RedirectResponse($this->urlGenerator->generate($this->listRoute, array(), true), 302);
                    }

                    if ($request->request->get('saveandnew', false)) {
                        return new RedirectResponse($this->urlGenerator->generate($this->editRoute, array(), true), 302);
                    }

                    return new RedirectResponse($this->urlGenerator->generate($this->editRoute, array('id' => $entity->getId())), 302);
                } else {
                    $form->addError(new FormError($this->translator->trans("No password set", array(), "frontend")));
                }
            }
        }

        return $this->render($this->editTemplate, array(
            'entity' => $entity,
            'form' => $form->createView(),
            'editroute' => $this->editRoute,
            'showroute' => $this->showRoute,
            'listroute' => $this->listRoute,
            'transprefix' => $this->transPrefix,
        ));
    }

    /**
     * @param  Request          $request
     * @param  int|string       $id
     * @return RedirectResponse
     * @throws \ErrorException
     */
    public function deleteAction(Request $request, $id)
    {
        $om = $this->doctrine->getManagerForClass($this->entityClass);

        /** @var AbstractUser $entity */
        $entity = $om->getRepository($this->entityClass)->find($id);

        if (is_null($entity)) {
            throw new NotFoundHttpException("User with id {$id} not found!");
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException("permission denied to delete entity with {$id}");
        }

        if ($entity->getId() == $this->getUser()->getId()) {
            throw new \ErrorException("You can't delete yourself!");
        }

        $om->remove($entity);
        $om->flush();

        // redirect to the list
        return new RedirectResponse($this->urlGenerator->generate($this->listRoute), 302);
    }

    /**
     * @param  string               $type
     * @param  null                 $data
     * @param  array                $options
     * @param  FormBuilderInterface $parent
     * @return Form
     */
    protected function createForm($type = 'form', $data = null, array $options = array(), FormBuilderInterface $parent = null)
    {
        return $this->formFactory->createBuilder($type, $data, $options, $parent)->getForm();
    }

    /**
     * @param  string   $view
     * @param  array    $parameters
     * @return Response
     */
    protected function render($view, array $parameters = array())
    {
        return new Response($this->twig->render($view, $parameters));
    }

    /**
     * @return AbstractUser|null
     */
    protected function getUser()
    {
        if (is_null($this->security->getToken())) {
            return null;
        }

        $user = $this->security->getToken()->getUser();

        if ($user instanceof $this->entityClass) {
            /** @var AbstractUser $user */
            $user = $this
                ->doctrine
                ->getManagerForClass($this->entityClass)
                ->getRepository($this->entityClass)
                ->find($user->getId()
            );
        }

        return $user;
    }
}
