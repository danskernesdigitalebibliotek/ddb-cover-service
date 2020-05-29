<?php

/**
 * @file
 * Cover Data Transfer Object (DTO). This DTO defines the /api/cover/{type}/{id} endpoint.
 *
 * However, because api-platform doesn't play nice with multiple {parameters} in path
 * individual DTOs are defined for all valid {type} values. These are necessary to enable
 * api-platform to generate IRIs for the cover resources.
 *
 * @see https://api-platform.com/docs/core/dto/
 */

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "method"="GET",
 *              "path": "/covers",
 *              "security"="is_granted('ROLE_COVER_READ')",
 *              "openapi_context" = {
 *                  "summary" = "Search multiple covers",
 *                  "description" = "Get covers by identifier in specific image format(s), specific image size(s) and with or without generic covers.",
 *                  "responses" = {
 *                      "200" = {
 *                          "description" = "A list of covers is returned. Notice that - unknown covers will not be present in the list. - if the requested size is larger than the original 'null' will be returned for 'url' and 'format for that size. - 'worst case' you will receive a 200 OK with an empty list.",
 *                          "content": {
 *                              "application/json": {
 *                                  "schema": {
 *                                      "type": "array",
 *                                      "items": {
 *                                          "$ref": "#/components/schemas/Cover"
 *                                      }
 *                                  }
 *                              }
 *                          }
 *                      },
 *                      "400" = {
 *                          "description" = "Bad request, e.g. required parameters missing."
 *                      }
 *                  },
 *                  "parameters" = {
 *                      {
 *                          "name" = "type",
 *                          "in" = "query",
 *                          "description" = "The type of the identifier, i.e. 'isbn', 'faust', 'pid' or 'issn'",
 *                          "required" = true,
 *                          "schema" : {
 *                              "type": "string",
 *                              "enum": {"faust", "isbn", "issn", "pid"},
 *                              "example"="pid"
 *                          }
 *                      },
 *                      {
 *                          "name" = "identifiers",
 *                          "in" = "query",
 *                          "description" = "A list of identifiers of {type}. Maximum number os identifiers per reqeust is %d",
 *                          "required" = true,
 *                          "schema" : {
 *                              "type": "array",
 *                              "maxLength": "DECORATED_ENV_VALUE",
 *                              "minLength": 1,
 *                              "items" : {
 *                                  "type" : "string",
 *                                  "example" : {
 *                                      "870970-basis:29862885",
 *                                      "870970-basis:27992625"
 *                                  },
 *                              },
 *                          },
 *                          "style" : "form",
 *                          "explode" : false,
 *                          "example" : {
 *                              "870970-basis:29862885",
 *                              "870970-basis:27992625"
 *                          },
 *                      },
 *                      {
 *                          "name" = "sizes",
 *                          "in" = "query",
 *                          "description" = "A list of image sizes (Cloudinary transformations) for the cover(s) you want to receive.",
 *                          "required" = false,
 *                          "schema" : {
 *                              "type": "array",
 *                              "items" : {
 *                                  "type" : "string",
 *                                  "enum": {"default", "original", "small", "medium", "large"},
 *                                  "example" : {
 *                                      "original",
 *                                      "small",
 *                                      "medium",
 *                                      "large"
 *                                  },
 *                              },
 *                          },
 *                          "style" : "form",
 *                          "explode" : false,
 *                          "example" : {
 *                              "original",
 *                              "small",
 *                              "medium",
 *                              "large"
 *                          },
 *                      }
 *                  }
 *               }
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted('ROLE_COVER_READ')"
 *          }
 *     }
 * )
 */
class Cover
{
    /**
     * @ApiProperty(
     *     identifier=true,
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="736830-basis:70773147"
     *         }
     *     }
     * )
     */
    private $id;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"pid", "isbn"},
     *             "example"="pid"
     *         }
     *     }
     * )
     */
    private $type;

    private $imageUrls;

    /**
     * Get the isIdentifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the isIdentifier.
     *
     * @param string $isIdentifier
     *
     * @return $this
     */
    public function setId(string $isIdentifier): self
    {
        $this->id = $isIdentifier;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get array of image urls.
     *
     * @return array
     */
    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }

    /**
     * Add an image url.
     *
     * @param ImageUrl $imageUrl
     *
     * @return $this
     */
    public function addImageUrl(ImageUrl $imageUrl): self
    {
        $this->imageUrls[$imageUrl->getSize()] = $imageUrl;

        return $this;
    }
}
