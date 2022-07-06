<?php

/**
 * @file
 * Parse yaml configuration files.
 */

namespace App\EnvVarProcessor;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YmlEnvVarProcessor.
 */
class YmlEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEnv($prefix, $name, \Closure $getEnv): mixed
    {
        $env = $getEnv($name);

        return Yaml::parse($env);
    }

    /**
     * {@inheritdoc}
     */
    #[ArrayShape(['yml' => 'string'])]
    public static function getProvidedTypes(): array
    {
        return [
            'yml' => 'array',
        ];
    }
}
