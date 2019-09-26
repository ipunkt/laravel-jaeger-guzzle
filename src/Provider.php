<?php namespace Ipunkt\LaravelJaegerGuzzle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\ServiceProvider;
use Ipunkt\LaravelJaeger\Context\Context;
use Psr\Http\Message\RequestInterface;

/**
 * Class Provider
 * @package Ipunkt\LaravelJaeger
 */
class Provider extends ServiceProvider
{
    protected $middlewareContainerIdentifier = 'laravel-jaeger-guzzle';

    public function register()
    {
        dd('JaegerGuzzleProvider');
        $this->mergeConfigFrom(
            __DIR__ . '/../config/jaeger-guzzle.php', 'jaeger-guzzle'
        );

        $this->publishes([
            __DIR__ . '/../config/jaeger-guzzle.php' => config_path('jaeger-guzzle.php'),
        ]);

        $this->app->bind($this->middlewareContainerIdentifier, function() {
            return Middleware::mapRequest( function(RequestInterface $request) {
                /**
                 * @var Context $context
                 */
                $context = app('current-context');
                $data = [];
                $context->inject($data);

                return $request->withHeader('X-TRACE', json_encode($data));
            });
        });

        $this->registerHandlerStack();

        $this->registerClient();

        $this->app->resolving(HandlerStack::class, function(HandlerStack $handlerStack, $app) {
            $middleware = $app->make($this->middlewareContainerIdentifier);

            $handlerStack->push($middleware);
        });
    }

    public function boot()
    {
    }

    private function registerHandlerStack(): void
    {
        if( !config('jaeger-guzzle::register-handler') )
            return;

        $this->app->bind(HandlerStack::class, function () {
            return HandlerStack::create();
        });
    }

    private function registerClient(): void
    {
        if( !config('jaeger-guzzle::register-client') )
            return;

        $this->app->bind(Client::class, function (Client $client, $app) {
            return new Client([
                'handler' => $app->make(HandlerStack::class),
            ]);
        });
    }
}
