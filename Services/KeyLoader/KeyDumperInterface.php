<?php

/**
 * @author zhaozhuobin
 */

namespace JubaopenTech\JWTAuthenticationBundle\Services\KeyLoader;


interface KeyDumperInterface
{
    /**
     * Dumps a key to be shared between parties.
     *
     * @return resource|string
     */
    public function dumpKey();
}
