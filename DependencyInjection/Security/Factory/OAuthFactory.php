<?php

/*
 * This file is part of the current project.
 * 
 * (c) ForeverGlory <http://foreverglory.me/>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glory\Bundle\OAuthBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * OAuthFactory
 * 
 * @author ForeverGlory <foreverglory@qq.com>
 */
class OAuthFactory extends AbstractFactory
{

    public function __construct()
    {
        $this->addOption('check_path', '/connect/{service}/callback');
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        parent::addConfiguration($node);
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return 'oauth';
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        return 'http';
    }

    /**
     * {@inheritDoc}
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'glory_oauth.authentication.provider.oauth.' . $id;

        $container
                ->setDefinition($providerId, new DefinitionDecorator('glory_oauth.authentication.provider.oauth'))
                ->addArgument(new Reference('glory_oauth.user_checker'))
                ->addMethodCall('setOwnerMap', array(new Reference('glory_oauth.ownermap')))
        ;

        return $providerId;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory::createEntryPoint()
     */
    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        //进入权限页面时的状态，调用form_login的操作，自动转到登录页
        $entryPointId = 'glory_oauth.authentication.entry_point.oauth.' . $id;
        $container
                ->setDefinition($entryPointId, new DefinitionDecorator('glory_oauth.authentication.entry_point.oauth'))
                ->addArgument(new Reference('security.http_utils'))
                ->addArgument($config['login_path'])
                ->addArgument($config['use_forward'])
        ;

        return $entryPointId;
    }

    /**
     * {@inheritDoc}
     */
    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);
        
        $container
                ->getDefinition($listenerId)
                ->addMethodCall('setOwnerMap', array(new Reference('glory_oauth.ownermap')));

        return $listenerId;
    }

    /**
     * {@inheritDoc}
     */
    protected function getListenerId()
    {
        return 'glory_oauth.authentication.listener.oauth';
    }

}
