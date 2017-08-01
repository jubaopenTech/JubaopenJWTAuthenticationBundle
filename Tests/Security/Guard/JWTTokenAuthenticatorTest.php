<?php

namespace JWTAuthenticationBundle\Tests\Security\Guard;

use JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use JWTAuthenticationBundle\Event\JWTInvalidEvent;
use JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use JWTAuthenticationBundle\Events;
use JWTAuthenticationBundle\Exception\ExpiredTokenException;
use JWTAuthenticationBundle\Exception\InvalidPayloadException;
use JWTAuthenticationBundle\Exception\InvalidTokenException;
use JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use JWTAuthenticationBundle\Exception\MissingTokenException;
use JWTAuthenticationBundle\Exception\UserNotFoundException;
use JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use JWTAuthenticationBundle\Tests\Stubs\User as AdvancedUserStub;
use JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JWTTokenAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCredentials()
    {
        $jwtManager = $this->getJWTManagerMock();
        $jwtManager
            ->expects($this->once())
            ->method('decode')
            ->willReturn(['username' => 'lexik']);

        $authenticator = new JWTTokenAuthenticator(
            $jwtManager,
            $this->getEventDispatcherMock(),
            $this->getTokenExtractorMock('token')
        );

        $this->assertInstanceOf(PreAuthenticationJWTUserToken::class, $authenticator->getCredentials($this->getRequestMock()));
    }

    public function testGetCredentialsWithInvalidTokenThrowsException()
    {
        try {
            (new JWTTokenAuthenticator(
                $this->getJWTManagerMock(),
                $this->getEventDispatcherMock(),
                $this->getTokenExtractorMock('token')
            ))->getCredentials($this->getRequestMock());

            $this->fail(sprintf('Expected exception of type "%s" to be thrown.', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            $this->assertSame('Invalid JWT Token', $e->getMessageKey());
        }
    }

    public function testGetCredentialsWithExpiredTokenThrowsException()
    {
        $jwtManager = $this->getJWTManagerMock();
        $jwtManager
            ->expects($this->once())
            ->method('decode')
            ->with(new PreAuthenticationJWTUserToken('token'))
            ->will($this->throwException(new JWTDecodeFailureException(JWTDecodeFailureException::EXPIRED_TOKEN, 'Expired JWT Token')));

        try {
            (new JWTTokenAuthenticator(
                $jwtManager,
                $this->getEventDispatcherMock(),
                $this->getTokenExtractorMock('token')
            ))->getCredentials($this->getRequestMock());

            $this->fail(sprintf('Expected exception of type "%s" to be thrown.', ExpiredTokenException::class));
        } catch (ExpiredTokenException $e) {
            $this->assertSame('Expired JWT Token', $e->getMessageKey());
        }
    }

    public function testGetCredentialsReturnsNullWithoutToken()
    {
        $authenticator = new JWTTokenAuthenticator(
            $this->getJWTManagerMock(),
            $this->getEventDispatcherMock(),
            $this->getTokenExtractorMock(false)
        );

        $this->assertNull($authenticator->getCredentials($this->getRequestMock()));
    }

    public function testGetUser()
    {
        $userIdentityField = 'username';
        $payload           = [$userIdentityField => 'lexik'];
        $rawToken          = 'token';
        $userRoles         = ['ROLE_USER'];

        $userStub = new AdvancedUserStub('lexik', 'password', 'user@gmail.com', $userRoles);

        $decodedToken = new PreAuthenticationJWTUserToken($rawToken);
        $decodedToken->setPayload($payload);

        $userProvider = $this->getUserProviderMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($payload[$userIdentityField])
            ->willReturn($userStub);

        $authenticator = new JWTTokenAuthenticator(
            $this->getJWTManagerMock('username'),
            $this->getEventDispatcherMock(),
            $this->getTokenExtractorMock()
        );

        $this->assertSame($userStub, $authenticator->getUser($decodedToken, $userProvider));
    }

    public function testGetUserWithInvalidPayloadThrowsException()
    {
        $decodedToken = new PreAuthenticationJWTUserToken('rawToken');
        $decodedToken->setPayload([]); // Empty payload

        try {
            (new JWTTokenAuthenticator(
                $this->getJWTManagerMock('username'),
                $this->getEventDispatcherMock(),
                $this->getTokenExtractorMock()
            ))->getUser($decodedToken, $this->getUserProviderMock());

            $this->fail(sprintf('Expected exception of type "%s" to be thrown.', InvalidPayloadException::class));
        } catch (InvalidPayloadException $e) {
            $this->assertSame('Unable to find key "username" in the token payload.', $e->getMessageKey());
        }
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage must be an instance of "JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken".
     */
    public function testGetUserWithInvalidFirstArg()
    {
        (new JWTTokenAuthenticator(
            $this->getJWTManagerMock(),
            $this->getEventDispatcherMock(),
            $this->getTokenExtractorMock()
        ))->getUser(new \stdClass(), $this->getUserProviderMock());
    }

    public function testGetUserWithInvalidUserThrowsException()
    {
        $userIdentityField = 'username';
        $payload           = [$userIdentityField => 'lexik'];

        $decodedToken = new PreAuthenticationJWTUserToken('rawToken');
        $decodedToken->setPayload($payload);

        $userProvider = $this->getUserProviderMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($payload[$userIdentityField])
            ->will($this->throwException(new UsernameNotFoundException()));

        try {
            (new JWTTokenAuthenticator(
                $this->getJWTManagerMock('username'),
                $this->getEventDispatcherMock(),
                $this->getTokenExtractorMock()
            ))->getUser($decodedToken, $userProvider);

            $this->fail(sprintf('Expected exception of type "%s" to be thrown.', UserNotFoundException::class));
        } catch (UserNotFoundException $e) {
            $this->assertSame('Unable to load an user with property "username" = "lexik". If the user identity has changed, you must renew the token. Otherwise, verify that the "jbp_jwt_authentication.user_identity_field" config option is correctly set.', $e->getMessageKey());
        }
    }

    public function testCreateAuthenticatedToken()
    {
        $rawToken  = 'token';
        $userRoles = ['ROLE_USER'];
        $payload   = ['username' => 'lexik'];
        $userStub  = new AdvancedUserStub('lexik', 'password', 'user@gmail.com', $userRoles);

        $decodedToken = new PreAuthenticationJWTUserToken($rawToken);
        $decodedToken->setPayload($payload);

        $jwtUserToken = new JWTUserToken($userRoles, $userStub, $rawToken, 'lexik');

        $dispatcher = $this->getEventDispatcherMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(Events::JWT_AUTHENTICATED, new JWTAuthenticatedEvent($payload, $jwtUserToken));

        $authenticator = new JWTTokenAuthenticator(
            $this->getJWTManagerMock('username'),
            $dispatcher,
            $this->getTokenExtractorMock()
        );

        $userProvider = $this->getUserProviderMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($payload['username'])
            ->willReturn($userStub);

        $authenticator->getUser($decodedToken, $userProvider);

        $this->assertEquals($jwtUserToken, $authenticator->createAuthenticatedToken($userStub, 'lexik'));
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Unable to return an authenticated token
     */
    public function testCreateAuthenticatedTokenThrowsExceptionIfNotPreAuthenticatedToken()
    {
        $userStub  = new AdvancedUserStub('lexik', 'test');

        (new JWTTokenAuthenticator(
           $this->getJWTManagerMock(),
           $this->getEventDispatcherMock(),
           $this->getTokenExtractorMock()
       ))->createAuthenticatedToken($userStub, 'lexik');
    }

    public function testOnAuthenticationFailureWithInvalidToken()
    {
        $authException    = new InvalidTokenException();
        $expectedResponse = new JWTAuthenticationFailureResponse('Invalid JWT Token');

        $dispatcher = $this->getEventDispatcherMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::JWT_INVALID,
                new JWTInvalidEvent($authException, $expectedResponse)
            );

        $authenticator = new JWTTokenAuthenticator(
            $this->getJWTManagerMock(),
            $dispatcher,
            $this->getTokenExtractorMock()
        );

        $response = $authenticator->onAuthenticationFailure($this->getRequestMock(), $authException);

        $this->assertEquals($expectedResponse, $response);
        $this->assertSame($expectedResponse->getMessage(), $response->getMessage());
    }

    public function testStart()
    {
        $authException   = new MissingTokenException('JWT Token not found');
        $failureResponse = new JWTAuthenticationFailureResponse($authException->getMessageKey());

        $dispatcher = $this->getEventDispatcherMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::JWT_NOT_FOUND,
                new JWTNotFoundEvent($authException, $failureResponse)
            );

        $authenticator = new JWTTokenAuthenticator(
            $this->getJWTManagerMock(),
            $dispatcher,
            $this->getTokenExtractorMock()
        );

        $response = $authenticator->start($this->getRequestMock());

        $this->assertEquals($failureResponse, $response);
        $this->assertSame($failureResponse->getMessage(), $response->getMessage());
    }

    public function testCheckCredentials()
    {
        $user = new AdvancedUserStub('test', 'test');

        $this->assertTrue(
            (new JWTTokenAuthenticator(
                $this->getJWTManagerMock(),
                $this->getEventDispatcherMock(),
                $this->getTokenExtractorMock()
            ))->checkCredentials(null, $user)
        );
    }

    public function testSupportsRememberMe()
    {
        $this->assertFalse(
            (new JWTTokenAuthenticator(
                $this->getJWTManagerMock(),
                $this->getEventDispatcherMock(),
                $this->getTokenExtractorMock()
            ))->supportsRememberMe()
        );
    }

    private function getJWTManagerMock($userIdentityField = null)
    {
        $jwtManager = $this->getMockBuilder(JWTTokenManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null !== $userIdentityField) {
            $jwtManager
                ->expects($this->once())
                ->method('getUserIdentityField')
                ->willReturn($userIdentityField);
        }

        return $jwtManager;
    }

    private function getEventDispatcherMock()
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getTokenExtractorMock($returnValue = null)
    {
        $extractor = $this->getMockBuilder(TokenExtractorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null !== $returnValue) {
            $extractor
                ->expects($this->once())
                ->method('extract')
                ->willReturn($returnValue);
        }

        return $extractor;
    }

    private function getRequestMock()
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUserProviderMock()
    {
        return $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
