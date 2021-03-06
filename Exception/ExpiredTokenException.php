<?php

/**
 * @author zhaozhuobin
 */

namespace JubaopenTech\JWTAuthenticationBundle\Exception;

use JubaopenTech\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Exception that should be thrown from a {@link JWTTokenAuthenticator} implementation during
 * an authentication process.
 */
class ExpiredTokenException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Expired JWT Token';
    }
}
