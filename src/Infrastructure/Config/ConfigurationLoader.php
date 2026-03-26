<?php

declare(strict_types=1);

namespace PayReckoner\Infrastructure\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

class ConfigurationLoader
{
    /** @var array<string, array<string, mixed>> */
    private array $resolved = [];

    /**
     * @param array<string, ConfigurationInterface> $definitions
     */
    public function __construct(
        private readonly string $configPath,
        private readonly array $definitions,
    ) {}

    public function get(string $key): mixed
    {
        $parts = explode('.', $key, 2);
        $domain = $parts[0];
        $subKey = $parts[1] ?? null;

        if (!isset($this->resolved[$domain])) {
            $this->resolved[$domain] = $this->loadAndProcess($domain);
        }

        if ($subKey === null) {
            return $this->resolved[$domain];
        }

        return $this->resolved[$domain][$subKey]
            ?? throw new \InvalidArgumentException("Unknown config key: {$key}");
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAndProcess(string $domain): array
    {
        $fileLocator = new FileLocator([$this->configPath]);
        /** @var string $filePath */
        $filePath = $fileLocator->locate("{$domain}.php");

        /** @var array<string, mixed> $rawConfig */
        $rawConfig = require $filePath;

        if (!isset($this->definitions[$domain])) {
            return $rawConfig;
        }

        $processor = new Processor();

        return $processor->processConfiguration(
            $this->definitions[$domain],
            [$rawConfig],
        );
    }
}
