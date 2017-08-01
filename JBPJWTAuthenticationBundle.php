<?php

/**
 * @author zhaozhuobin
 */

namespace JWTAuthenticationBundle;

use JWTAuthenticationBundle\DependencyInjection\Security\Factory\JWTFactory;
use JWTAuthenticationBundle\DependencyInjection\Security\Factory\JWTUserFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * JBPJWtAuthenticationBundle.
 */
class JBPJWTAuthenticationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        $extension->addUserProviderFactory(new JWTUserFactory());
        $extension->addSecurityListenerFactory(new JWTFactory()); // BC 1.x, to be removed in 3.0
    }
}
