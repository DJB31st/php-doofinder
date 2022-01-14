<?php

namespace Tests\Unit\Search\Resources;

use Doofinder\Search\Resources\Stat;
use Doofinder\Shared\Exceptions\ApiException;
use Doofinder\Shared\HttpResponse;
use Doofinder\Shared\HttpStatusCode;
use Doofinder\Shared\Interfaces\HttpClientInterface;
use Doofinder\Shared\Interfaces\HttpResponseInterface;
use Tests\Unit\Management\Resources\BaseResourceTest;

class StatTest extends BaseResourceTest
{
    private function createSut()
    {
        return Stat::create($this->httpClient, $this->config);
    }

    private function getUrl($hashId)
    {
        return self::BASE_URL . '/6/' . $hashId . '/stats';
    }

    public function testStatSuccess()
    {
        $body = ['result' => 'registered'];

        $response = HttpResponse::create(HttpStatusCode::OK, json_encode($body));

        $sessionId = 'rand_fake_session_id';
        $hashId = '3a0811e861d36f76cedca60723e03291';

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->getUrl($hashId) . '/init',
                HttpClientInterface::METHOD_PUT,
                ['session_id' => $sessionId],
                ['Authorization: Token ' . self::TOKEN]
            )
            ->willReturn($response);

        $this->setConfig();

        $response = $this->createSut()->initSession($hashId, $sessionId);

        $this->assertSame(HttpStatusCode::OK, $response->getStatusCode());
        $this->assertEquals($response->getBody(), $body);
    }

    public function testStatInvalidParams()
    {
        $response = HttpResponse::create(HttpStatusCode::BAD_REQUEST, '{"error" : {"code": "bad_params"}}');
        $hashId = '3a0811e861d36f76cedca60723e03291';
        $sessionId = 'rand_fake_session_id';

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->getUrl($hashId) . '/init',
                HttpClientInterface::METHOD_PUT,
                ['session_id' => $sessionId],
                ['Authorization: Token ' . self::TOKEN]
            )
            ->willReturn($response);

        $this->setConfig();

        $thrownException = false;

        try {
            $this->createSut()->initSession($hashId, $sessionId);
        } catch (ApiException $e) {
            $thrownException = true;
            $this->assertSame(HttpStatusCode::BAD_REQUEST, $e->getCode());
            /** @var HttpResponseInterface $response */
            $response = $e->getBody();
            $this->assertSame('bad_params', $response->getBody()['error']['code']);
        }

        $this->assertTrue($thrownException);
    }

    public function testStatHashIdNotFound()
    {
        $hashId = '3a0811e861d36f76cedca60723e03291';

        $response = HttpResponse::create(HttpStatusCode::NOT_FOUND, '{"error" : {"code": "not_found"}}');
        $sessionId = 'rand_fake_session_id';

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->getUrl($hashId) . '/init',
                HttpClientInterface::METHOD_PUT,
                ['session_id' => $sessionId],
                ['Authorization: Token ' . self::TOKEN]
            )
            ->willReturn($response);

        $this->setConfig();

        $thrownException = false;

        try {
            $this->createSut()->initSession($hashId, $sessionId);
        } catch (ApiException $e) {
            $thrownException = true;
            $this->assertSame(HttpStatusCode::NOT_FOUND, $e->getCode());
            /** @var HttpResponseInterface $response */
            $response = $e->getBody();
            $this->assertSame('not_found', $response->getBody()['error']['code']);
        }

        $this->assertTrue($thrownException);
    }
}