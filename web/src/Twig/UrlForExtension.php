<?php

namespace AgenDAV\Twig;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteParserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes Slim's RouteParser as a Twig {{ url_for('name') }} function.
 *
 * Resolves the parser lazily so the Twig environment can be built before the
 * Slim App has registered routes.
 */
class UrlForExtension extends AbstractExtension
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('url_for', [$this, 'urlFor']),
        ];
    }

    public function urlFor(string $name, array $data = [], array $queryParams = []): string
    {
        /** @var RouteParserInterface $parser */
        $parser = $this->container->get(RouteParserInterface::class);
        return $parser->urlFor($name, $data, $queryParams);
    }
}
