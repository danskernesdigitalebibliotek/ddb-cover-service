<?php

/**
 * @file
 * EnvProcessor to generate trace id.
 */

namespace App\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

class TraceIdEnvVarProcessor implements EnvVarProcessorInterface
{
    private static $id;

    /**
     * {@inheritdoc}
     */
    public function getEnv($prefix, $name, $getEnv)
    {
        try {
            $this::$id = $getEnv($name);
        } catch (EnvNotFoundException $exception) {
            // Do not do anything here as the id will fallback to be generated.
        }

        if (empty($this::$id)) {
            $this->generate();
        }

        return $this::$id;
    }

    /**
     * {@inheritdoc}
     */
    public static function getProvidedTypes()
    {
        return [
            'traceId' => 'string',
        ];
    }

    /**
     * Generate new unique id.
     *
     * @throws \Exception
     */
    private function generate() {
        $this::$id = bin2hex(random_bytes(16));
    }
}