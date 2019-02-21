<?php

/**
 * @file
 * Identifier Data Transfer Object (DTO) Interface.
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Dto;

interface IdentifierInterface
{
    public function getId(): string;

    public function setId(string $id): IdentifierInterface;

    public function getType(): string;

    public function setType(string $type): IdentifierInterface;

    public function getImageUrls(): array;

    public function addImageUrl(ImageUrl $imageUrl): IdentifierInterface;
}
