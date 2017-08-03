<?php

/**
 * @author zhaozhuobin
 */

namespace JubaopenTech\JWTAuthenticationBundle\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * JWTNotFoundEvent event is dispatched when a JWT cannot be found in a request
 * covered by a firewall secured via jbp_jwt.
 */
class JWTNotFoundEvent extends AuthenticationFailureEvent implements JWTFailureEventInterface
{
    /**
     * @param AuthenticationException|null $exception
     * @param Response|null                $response
     */
    public function __construct(AuthenticationException $exception = null, Response $response = null)
    {
        $this->exception = $exception;
        $this->response  = $response;
    }
}
