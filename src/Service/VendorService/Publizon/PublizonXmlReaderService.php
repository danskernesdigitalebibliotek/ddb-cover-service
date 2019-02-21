<?php

/**
 * @file
 * 'Publizon' vendor xml reader service
 */

namespace App\Service\VendorService\Publizon;

use App\Exception\XmlreaderException;

/**
 * Class PublizonXmlReaderService.
 */
class PublizonXmlReaderService
{
    private const AUTH_HEADER_NAME = 'x-service-key';

    private $reader;

    /**
     * Open an XMLReader to the given endpoint.
     *
     * @param string $apiServiceKey
     * @param string $apiEndpoint
     *
     * @return bool
     *
     * @throws XmlreaderException
     */
    public function open(string $apiServiceKey, string $apiEndpoint): bool
    {
        $param = ['http' => [
            'method' => 'GET',
            'header' => $this::AUTH_HEADER_NAME.': '.$apiServiceKey."\r\n",
        ]];
        libxml_set_streams_context(stream_context_create($param));
        $this->reader = \XMLReader::open($apiEndpoint);

        if ($this->reader) {
            return true;
        }

        throw new XmlreaderException('Unknown error when opening '.$apiEndpoint);
    }

    /**
     * Move to next node in document.
     *
     * @return bool
     *
     * @throws XmlreaderException
     */
    public function read(): bool
    {
        if ($this->reader) {
            return $this->reader->read();
        }

        throw new XmlreaderException();
    }

    /**
     * Get the type of the current element.
     *
     * @return int
     */
    public function getNodeType(): int
    {
        return $this->reader->nodeType;
    }

    /**
     * Get the name of the current element.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->reader->name;
    }

    /**
     * Check if next element is end element of the given name. Advances the pointer one position.
     *
     * @param string $element
     *
     * @return bool
     *
     * @throws XmlreaderException
     */
    public function readUntilElementEnd(string $element): bool
    {
        if ($this->reader) {
            $this->reader->read();

            return $this->notAtElementEnd($element);
        }

        throw new XmlreaderException();
    }

    /**
     * Get the value of the next element. Advances the pointer one position.
     *
     * @return string
     *
     * @throws XmlreaderException
     */
    public function getNextElementValue(): string
    {
        if ($this->reader) {
            $this->reader->read();

            return $this->reader->value;
        }

        throw new XmlreaderException();
    }

    /**
     * Check if pointer is at an start element of the given name.
     *
     * @param string $elementName
     *
     * @return bool
     *
     * @throws XmlreaderException
     */
    public function isAtElementStart(string $elementName): bool
    {
        if ($this->reader) {
            return \XMLReader::ELEMENT === $this->reader->nodeType && $this->reader->name === $elementName;
        }

        throw new XmlreaderException();
    }

    /**
     * Check if pointer is at an end element of the given name.
     *
     * @param string $elementName
     *
     * @return bool
     *
     * @throws XmlreaderException
     */
    public function notAtElementEnd(string $elementName): bool
    {
        if ($this->reader) {
            return !(\XMLReader::END_ELEMENT === $this->reader->nodeType && $this->reader->name === $elementName);
        }

        throw new XmlreaderException();
    }
}
