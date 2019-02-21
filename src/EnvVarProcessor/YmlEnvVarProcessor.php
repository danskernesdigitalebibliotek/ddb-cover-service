<?php

/**
 * @file
 * Parse yaml configuration files.
 */

namespace App\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Yaml\Yaml;

class YmlEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEnv($prefix, $name, \Closure $getEnv)
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
