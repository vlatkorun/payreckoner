<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Config\Definition;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class RedisConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('redis');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('host')
                    ->defaultValue('localhost')
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('port')
                    ->defaultValue(6379)
                    ->min(1)->max(65535)
                ->end()
                ->integerNode('database')
                    ->defaultValue(0)
                    ->min(0)->max(15)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
