<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Console;

use PayReckoner\Application\Command\GenerateFixturesCommand;
use PayReckoner\Application\Command\RunPipelineCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('PayReckoner', '1.0.0');

        $container = new ContainerBuilder();
        $container->setParameter('config_path', dirname(__DIR__, 3) . '/config');

        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('services.yaml');

        $container->compile();

        $runPipeline = $container->get(RunPipelineCommand::class);
  
        $this->addCommand($runPipeline);

        $generateFixtures = $container->get(GenerateFixturesCommand::class);

        $this->addCommand($generateFixtures);
    }
}
