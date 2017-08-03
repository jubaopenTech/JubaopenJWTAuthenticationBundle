<?php

namespace JWTAuthenticationBundle\Tests\Services\JWSProvider;

use JWTAuthenticationBundle\Services\JWSProvider\DefaultJWSProvider;
use JWTAuthenticationBundle\Services\KeyLoader\KeyLoaderInterface;

/**
 * Tests the DefaultJWSProvider.
 */
final class DefaultJWSProviderTest extends AbstractJWSProviderTest
{
    public function __construct()
    {
        self::$providerClass  = DefaultJWSProvider::class;
        self::$keyLoaderClass = KeyLoaderInterface::class;
    }
}
