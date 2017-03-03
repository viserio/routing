<?php
declare(strict_types=1);
namespace Viserio\Component\Routing\Tests\Providers;

use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Viserio\Component\Container\Container;
use Viserio\Component\Contracts\Routing\RouteCollection as RouteCollectionContract;
use Viserio\Component\Contracts\Routing\Router as RouterContract;
use Viserio\Component\Contracts\WebProfiler\WebProfiler as WebProfilerContract;
use Viserio\Component\HttpFactory\Providers\HttpFactoryServiceProvider;
use Viserio\Component\OptionsResolver\Providers\OptionsResolverServiceProvider;
use Viserio\Component\Routing\Providers\RoutingDataCollectorServiceProvider;
use Viserio\Component\WebProfiler\Providers\WebProfilerServiceProvider;

class RoutingDataCollectorServiceProviderTest extends MockeryTestCase
{
    public function testGetServices()
    {
        $routes = $this->mock(RouteCollectionContract::class);
        $router = $this->mock(RouterContract::class);
        $router->shouldReceive('getRoutes')
            ->once()
            ->andReturn($routes);
        $router->shouldReceive('group')
            ->once();

        $container = new Container();
        $container->instance(ServerRequestInterface::class, $this->getRequest());
        $container->instance(RouterContract::class, $router);
        $container->register(new OptionsResolverServiceProvider());
        $container->register(new HttpFactoryServiceProvider());
        $container->register(new WebProfilerServiceProvider());
        $container->register(new RoutingDataCollectorServiceProvider());

        $container->instance('config',
            [
                'viserio' => [
                    'webprofiler' => [
                        'enable'    => true,
                        'collector' => [
                            'routes'  => true,
                        ],
                    ],
                ],
            ]
        );

        $profiler = $container->get(WebProfilerContract::class);

        static::assertInstanceOf(WebProfilerContract::class, $profiler);

        static::assertTrue(array_key_exists('time-data-collector', $profiler->getCollectors()));
        static::assertTrue(array_key_exists('memory-data-collector', $profiler->getCollectors()));
        static::assertTrue(array_key_exists('routing-data-collector', $profiler->getCollectors()));
    }

    private function getRequest()
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')
            ->with('REQUEST_TIME_FLOAT')
            ->andReturn(false);
        $request->shouldReceive('getHeaderLine')
            ->with('REQUEST_TIME')
            ->andReturn(false);

        return $request;
    }
}