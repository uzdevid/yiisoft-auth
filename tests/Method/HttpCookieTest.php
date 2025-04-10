<?php

declare(strict_types=1);

namespace Yiisoft\Auth\Tests\Method;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\Method\HttpCookie;
use Yiisoft\Auth\Tests\Stub\FakeIdentity;
use Yiisoft\Auth\Tests\Stub\FakeIdentityRepository;
use Yiisoft\Http\Method;

final class HttpCookieTest extends TestCase
{
    public function testSuccessfulAuthentication(): void
    {
        $identityRepository = new FakeIdentityRepository($this->createIdentity());
        $result = (new HttpCookie($identityRepository))->authenticate(
            $this->createRequest(['access-token' => 'access-token-value'])
        );

        $this->assertNotNull($result);
        $this->assertEquals('test-id', $result->getId());
        $this->assertEquals(
            [
                'findIdentityByToken' => [
                    'token' => 'access-token-value',
                    'type' => null,
                ],
            ],
            $identityRepository->getCallParams()
        );
    }

    public function testIdentityNotFoundByToken(): void
    {
        $identityRepository = new FakeIdentityRepository(null);
        $authenticationMethod = new HttpCookie($identityRepository);

        $this->assertNull(
            $authenticationMethod->authenticate(
                $this->createRequest(['access-token' => 'access-token-value'])
            )
        );
    }

    public function testChallengeIsCorrect(): void
    {
        $response = new Response(400);
        $identityRepository = new FakeIdentityRepository($this->createIdentity());
        $authenticationMethod = new HttpCookie($identityRepository);

        $this->assertEquals(400, $authenticationMethod
            ->challenge($response)
            ->getStatusCode());
    }

    public function testCustomTokenParam(): void
    {
        $identityRepository = new FakeIdentityRepository($this->createIdentity());
        $authenticationMethod = (new HttpCookie($identityRepository))
            ->withCookieName('AuthToken');

        $result = $authenticationMethod->authenticate(
            $this->createRequest(['AuthToken' => 'AccessTokenValue'])
        );

        $this->assertNotNull($result);
        $this->assertEquals('test-id', $result->getId());
    }

    public function testImmutability(): void
    {
        $identityRepository = new FakeIdentityRepository($this->createIdentity());
        $original = (new HttpCookie($identityRepository));
        $this->assertNotSame($original, $original->withCookieName('cookieName'));
        $this->assertNotSame($original, $original->withTokenType('another-token-type'));
    }

    public function testWithTokenType(): void
    {
        $identityRepository = new FakeIdentityRepository($this->createIdentity());
        (new HttpCookie($identityRepository))
            ->withTokenType('another-token-type')
            ->authenticate(
                $this->createRequest(['access-token' => 'access-token-value'])
            );

        $this->assertEquals(
            [
                'findIdentityByToken' => [
                    'token' => 'access-token-value',
                    'type' => 'another-token-type',
                ],
            ],
            $identityRepository->getCallParams()
        );
    }

    private function createIdentity(): IdentityInterface
    {
        return new FakeIdentity('test-id');
    }

    private function createRequest(array $cookieParams = []): ServerRequestInterface
    {
        return (new ServerRequest(Method::GET, '/'))
            ->withCookieParams($cookieParams);
    }
}
