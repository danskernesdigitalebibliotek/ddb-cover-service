<?php

/**
 * @file
 * Interface for handling Cover storing.
 */

namespace App\Service\CoverStore;

use App\Exception\CoverStoreCredentialException;
use App\Exception\CoverStoreException;
use App\Exception\CoverStoreNotFoundException;
use App\Exception\CoverStoreTooLargeFileException;
use App\Exception\CoverStoreUnexpectedException;
use App\Utils\CoverStore\CoverStoreItem;
use App\Utils\OpenPlatform\Material;

/**
 * Interface CoverStoreInterface.
 */
interface CoverStoreInterface
{
    /**
     * Upload image the store.
     *
     * @param string $url
     *   The URL to fetch the image from
     * @param string $folder
     *   The vendor that supplied the image used to organize the images
     * @param string $identifier
     *   The name that the file should be saved under
     * @param array $tags
     *   Tags to enrich the image in the store
     *
     * @return coverStoreItem
     *   CoverStoreItem object contain information about the image
     *
     * @throws CoverStoreCredentialException
     * @throws CoverStoreException
     * @throws CoverStoreNotFoundException
     * @throws CoverStoreTooLargeFileException
     * @throws CoverStoreUnexpectedException
     */
    public function upload(string $url, string $folder, string $identifier, array $tags = []): CoverStoreItem;

    /**
     * Generate cover based on material object.
     *
     * @param material $material
     *   Material to generate cover for
     * @param string $folder
     *   The folder to place the cover in
     * @param string $identifier
     *   Filename for the cover in the store
     * @param array $tags
     *   Tags to enrich the image in the store
     *
     * @return coverStoreItem
     *   CoverStoreItem object contain information about the image
     *
     * @throws CoverStoreCredentialException
     * @throws CoverStoreException
     */
    public function generate(Material $material, string $folder, string $identifier, array $tags = []): CoverStoreItem;

    /**
     * Remove cover from the store.
     *
     * On error exception is thrown.
     *
     * @param string $folder
     *   The folder to place the cover in
     * @param string $identifier
     *   Filename for the cover in the store
     *
     * @throws CoverStoreCredentialException
     * @throws CoverStoreException
     */
    public function remove(string $folder, string $identifier): void;
}
