<?php

namespace App\Api\Elastic;

interface SearchServiceInterface
{
    public function search(string $type, array $identifiers): array;
}