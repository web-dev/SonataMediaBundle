<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;

use Knp\Bundle\MenuBundle\Menu;
use Knp\Bundle\MenuBundle\MenuItem;

class MediaAdmin extends Admin
{
    protected $pool = null;

    protected $list = array(
        'image'  => array('template' => 'SonataMediaBundle:MediaAdmin:list_image.html.twig', 'type' => 'string'),
        'custom' => array('template' => 'SonataMediaBundle:MediaAdmin:list_custom.html.twig', 'type' => 'string'),
        'enabled',
        '_action' => array(
            'actions' => array(
                'view' => array(),
                'edit' => array()
            )
        )
    );

    protected $filter = array(
        'name',
        'providerReference',
        'enabled',
        'context',
    );

    public function __construct($code, $class, $baseControllerName, $pool)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->pool = $pool;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $media = $formMapper->getFormBuilder()->getData();

        if(!$media->getProviderName()) {
            return;
        }

        $provider = $this->pool->getProvider($media->getProviderName());

        if ($media->getId() > 0) {
            $provider->buildEditForm($formMapper);
        } else {
            $provider->buildCreateForm($formMapper);
        }
    }

    public function configureDatagridFilters(DatagridMapper $datagrid)
    {
        $providers = array();
        foreach($this->pool->getProviderNamesByContext('default') as $name) {
            $providers[$name] = $name;
        }

        $datagrid->add('providerName', array(
            'type' => 'choice',
            'filter_field_options'=> array(
                'choices' => $providers,
                'required' => false,
                'multiple' => true
            )
        ));
    }

    public function configureListFields(ListMapper $list)
    {

    }

    public function configureRoutes(RouteCollection $collection)
    {
        $collection->add('view', $this->getRouterIdParameter().'/view');
    }

    public function prePersist($media)
    {
        $parameters = $this->getPersistentParameters();
        $media->setContext($parameters['context']);

        $this->pool->prePersist($media);
    }

    public function postPersist($media)
    {
        $this->pool->postPersist($media);
    }

    public function preUpdate($media)
    {
      $this->pool->preUpdate($media);
    }

    public function postUpdate($media)
    {
      $this->pool->preUpdate($media);
    }

    public function getPersistentParameters()
    {
        if (!$this->hasRequest()) {
            return array();
        }

        return array(
            'provider' => $this->getRequest()->get('provider'),
            'context'  => $this->getRequest()->get('context', 'default'),
        );
    }

    public function getNewInstance()
    {
        $media = parent::getNewInstance();

        if ($this->hasRequest()) {
            $media->setProviderName($this->getRequest()->get('provider'));
            $media->setContext($this->getRequest()->get('context'));
        }

        return $media;
    }

    public function configureSideMenu(MenuItem $menu, $action, Admin $childAdmin = null)
    {
        if (!in_array($action, array('edit', 'view'))) {
            return;
        }

        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $this->getRequest()->get('id');

        $menu->addChild(
            $this->trans('edit_media'),
            $admin->generateUrl('edit', array('id' => $id))
        );

        $menu->addChild(
            $this->trans('view_media'),
            $admin->generateUrl('view', array('id' => $id))
        );
    }
}