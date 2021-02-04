<?php
/**
 * @file
 * Append date stamp to env variable. E.g 'stats' to 'stats_04-02-2021'
 */

namespace App\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Class AppendDateStampEnvVarProcessor.
 */
class AppendDateStampEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);
        $date = new \DateTimeImmutable();

        return $env.'_'.$date->format('d-m-Y');
    }

    /**
     * {@inheritdoc}
     */
    public static function getProvidedTypes()
    {
        return [
            'append_date' => 'string',
        ];
    }
}
