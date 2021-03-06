<?php

namespace JubaopenTech\JWTAuthenticationBundle\Tests\Response;

use JubaopenTech\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;

/**
 * Tests the JWTAuthenticationFailureResponse.
 */
final class JWTAuthenticationFailureResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testResponse()
    {
        $expected = [
            'code'    => 401,
            'msg' => 'message',
        ];

        $response = new JWTAuthenticationFailureResponse($expected['msg']);

        $this->assertSame($expected['msg'], $response->getMessage());
        $this->assertSame($expected['code'], $response->getStatusCode());
        $this->assertSame('Bearer', $response->headers->get('WWW-Authenticate'));
        $this->assertSame(json_encode($expected), $response->getContent());

        return $response;
    }

    /**
     * @depends testResponse
     */
    public function testSetMessage(JWTAuthenticationFailureResponse $response)
    {
        $newMessage = 'new message';
        $response->setMessage($newMessage);

        $responseBody = json_decode($response->getContent());

        $this->assertSame($response->getStatusCode(), $responseBody->code);
        $this->assertSame($newMessage, $response->getMessage());
        $this->assertSame($newMessage, $responseBody->msg);
    }
}
