<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\HttpRequestException;
use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const PHP_OS_FAMILY;

final class HttpRequestCurlTest extends TestCase
{
    private const HOST = '127.0.0.1:8101';
    private const URL = 'http://127.0.0.1:8101/';

    private static PhpServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/Server/http_request_curl.php');
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        try {
            self::$server->stop();
        } catch (RuntimeException $e) {
            if (PHP_OS_FAMILY !== 'Windows') {
                throw $e;
            }
        }
    }

    public function testRequestFailureThrowsException(): void
    {
        $request = new HttpRequestCurl(new HttpRequestHeaders());

        $this->expectException(HttpRequestException::class);

        $request->request('GET', 'http://127.0.0.1:1/', []);
    }

    public function testJsonResponse(): void
    {
        $response = $this->request('valid');

        $this->assertSame(200, $response['code']);
        $this->assertSame(['ok' => true], $response['body']);
        $this->assertSame('{"ok":true}', $response['view']);
    }

    public function testEmptyJsonResponse(): void
    {
        $response = $this->request('empty');

        $this->assertSame(200, $response['code']);
        $this->assertSame([], $response['body']);
        $this->assertSame('', $response['view']);
    }

    public function testInvalidJsonResponseThrowsException(): void
    {
        $this->expectException(HttpRequestException::class);

        $this->request('invalid');
    }

    /**
     * @return array{
     *     body: array<mixed>,
     *     code: int,
     *     headers: array<string, string>,
     *     view: string
     * }
     */
    private function request(string $case): array
    {
        $request = new HttpRequestCurl(new HttpRequestHeaders());

        return $request->request('GET', self::URL . '?case=' . $case, []);
    }
}
