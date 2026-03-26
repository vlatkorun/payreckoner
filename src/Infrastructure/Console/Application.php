<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Console;

use PayReckoner\Application\Command\GenerateFixturesCommand;
use PayReckoner\Application\Command\RunPipelineCommand;
use PayReckoner\Application\Service\FixtureGenerator;
use PayReckoner\Infrastructure\Config\ConfigurationLoader;
use PayReckoner\Infrastructure\Config\Definition\RedisConfiguration;
use PayReckoner\Infrastructure\Storage\RedisConnectionFactory;
use PayReckoner\Infrastructure\Storage\RedisRecordStorage;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('PayReckoner', '1.0.0');

        $config = new ConfigurationLoader(
            configPath: dirname(__DIR__, 3) . '/config',
            definitions: [
                'redis' => new RedisConfiguration(),
            ],
        );

        $storage = new RedisRecordStorage(new RedisConnectionFactory($config));
        $generator = new FixtureGenerator();

        $this->add(new RunPipelineCommand());
        $this->add(new GenerateFixturesCommand($generator, $storage));
    }
}
