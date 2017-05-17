<?php
declare(strict_types=1);

namespace Pac\CorsMiddleware\DependencyInjection;

use Pac\CorsMiddleware\Middleware\CorsMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

class CorsMiddlewareExtension implements ExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = call_user_func_array('array_merge', $configs);

        $definition = (new Definition(CorsMiddleware::class))
            ->setArguments([$config, new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ;
        $container->setDefinition('cors_middleware', $definition);
    }

    public function getNamespace()
    {
        // TODO: Implement getNamespace() method.
    }

    public function getXsdValidationBasePath()
    {
        // TODO: Implement getXsdValidationBasePath() method.
    }

    public function getAlias()
    {
        return 'cors_middleware';
    }
}
