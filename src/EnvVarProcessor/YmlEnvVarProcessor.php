<?php

/**
 * @file
 * Parse yaml configuration files.
 */

namespace App\EnvVarProcessor;

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
    public function getEnv($prefix, $name, \Closure $getEnv): string
    {
        $env = $getEnv($name);

        return Yaml::parse($env);
    }

    /**
     * {@inheritdoc}
     */
    public static function getProvidedTypes(): array
    {
        return [
            'yml' => 'array',
        ];
    }
}
