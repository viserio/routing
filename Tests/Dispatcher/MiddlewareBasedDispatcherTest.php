<?php
declare(strict_types=1);
namespace Viserio\Component\Routing\Tests\Dispatchers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Viserio\Component\Contract\Container\Container as ContainerContract;
use Viserio\Component\HttpFactory\ResponseFactory;
use Viserio\Component\HttpFactory\ServerRequestFactory;
use Viserio\Component\HttpFactory\StreamFactory;
use Viserio\Component\Routing\Dispatcher\MiddlewareBasedDispatcher;
use Viserio\Component\Routing\Route;
use Viserio\Component\Routing\Route\Collection as RouteCollection;
use Viserio\Component\Routing\Tests\Fixture\FakeMiddleware;
use Viserio\Component\Routing\Tests\Fixture\FooMiddleware;
use Viserio\Component\Support\Traits\NormalizePathAndDirectorySeparatorTrait;

/**
 * @internal
 */
final class MiddlewareBasedDispatcherTest extends AbstractDispatcherTest
{
    use NormalizePathAndDirectorySeparatorTrait;

    /**
     * @var \Viserio\Component\Routing\Dispatcher\MiddlewareBasedDispatcher
     */
    protected $dispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = new MiddlewareBasedDispatcher();
        $dispatcher->setCachePath(self::normalizeDirectorySeparator($this->patch . '/MiddlewareBasedDispatcherTest.cache'));
        $dispatcher->refreshCache(true);

        $this->dispatcher = $dispatcher;
    }

    public function testMiddlewareFunc(): void
    {
        $dispatcher = $this->dispatcher;

        $dispatcher->withMiddleware(FooMiddleware::class);

        $this->assertSame([FooMiddleware::class => FooMiddleware::class], $dispatcher->getMiddleware());

        $dispatcher->setMiddlewarePriorities([999 => FooMiddleware::class]);

        $this->assertSame([999 => FooMiddleware::class], $dispatcher->getMiddlewarePriorities());
    }

    public function testHandleFound(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(
            'GET',
            '/test',
            [
                'uses' => function () {
                    return (new ResponseFactory())
                        ->createResponse()
                        ->withBody((new StreamFactory())->createStream('hello'));
                },
                'middleware' => 'api',
            ]
        ));

        $this->dispatcher->setMiddlewareGroup('api', [new FakeMiddleware()]);

        $response = $this->dispatcher->handle(
            $collection,
            (new ServerRequestFactory())->createServerRequest('GET', '/test')
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('caught', (string) $response->getBody());
    }

    public function testHandleFoundThrowExceptionClassNotManaged(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Class [Viserio\\Component\\Routing\\Tests\\Fixture\\FakeMiddleware] is not being managed by the container.');

        $collection = new RouteCollection();
        $collection->add(new Route(
            'GET',
            '/test',
            [
                'uses' => function () {
                    return (new ResponseFactory())
                        ->createResponse()
                        ->withBody((new StreamFactory())->createStream('hello'));
                },
                'middleware' => FakeMiddleware::class,
            ]
        ));

        $container = $this->mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->once()
            ->andReturn(false);

        $this->dispatcher->setContainer($container);

        $this->dispatcher->handle(
            $collection,
            (new ServerRequestFactory())->createServerRequest('GET', '/test')
        );
    }

    public function testHandleFoundWithResolve(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(
            'GET',
            '/test',
            [
                'uses' => function () {
                    return (new ResponseFactory())
                        ->createResponse()
                        ->withBody((new StreamFactory())->createStream('hello'));
                },
                'middleware' => FakeMiddleware::class,
            ]
        ));

        $container = $this->mock(ContainerContract::class);
        $container->shouldReceive('has')
            ->once()
            ->andReturn(false);
        $container->shouldReceive('resolve')
            ->once()
            ->with(FakeMiddleware::class)
            ->andReturn(new FakeMiddleware());

        $this->dispatcher->setContainer($container);

        $this->dispatcher->handle(
            $collection,
            (new ServerRequestFactory())->createServerRequest('GET', '/test')
        );
    }
}
