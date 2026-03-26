<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Console;

use PayReckoner\Application\Command\GenerateFixturesCommand;
use PayReckoner\Application\Command\RunPipelineCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('PayReckoner', '1.0.0');

        $this->add(new RunPipelineCommand());
        $this->add(new GenerateFixturesCommand());
    }
}
