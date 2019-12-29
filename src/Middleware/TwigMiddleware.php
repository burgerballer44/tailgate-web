<?php

namespace TailgateWeb\Middleware;

use Mailgun\Message\Exceptions\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Views\TwigMiddleware as SlimTwigMiddleware;
use Slim\Views\TwigRuntimeLoader;
use TailgateWeb\Extensions\FormBuilderExtension;

class TwigMiddleware extends SlimTwigMiddleware
{
    public static function createFromContainer(App $app, string $containerKey = 'view'): SlimTwigMiddleware
    {
        $container = $app->getContainer();
        if ($container === null) {
            throw new RuntimeException('The app does not have a container.');
        }
        if (!$container->has($containerKey)) {
            throw new RuntimeException(
                "The specified container key does not exist: $containerKey"
            );
        }

        $twig = $container->get($containerKey);
        if (!($twig instanceof Twig)) {
            throw new RuntimeException(
                "Twig instance could not be resolved via container key: $containerKey"
            );
        }

        return new self(
            $twig,
            $app->getRouteCollector()->getRouteParser(),
            $app->getBasePath()
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $runtimeLoader = new TwigRuntimeLoader($this->routeParser, $request->getUri(), $this->basePath);
        $this->twig->addRuntimeLoader($runtimeLoader);

        $this->twig->addExtension(new TwigExtension());
        $this->twig->addExtension(new FormBuilderExtension($request->getParsedBody()));

        if ($this->serverRequestAttributeName !== null) {
            $request = $request->withAttribute($this->serverRequestAttributeName, $this->twig);
        }

        return $handler->handle($request);
    }
}
