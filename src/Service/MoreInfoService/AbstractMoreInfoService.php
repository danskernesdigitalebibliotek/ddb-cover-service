<?php

/**
 * @file
 * Abstract SOAP Service that mimics the original 'moreInfo' service.
 *
 * This class was created using wsdl2php. Modified for 'moreInfo' service.
 *
 * @wsdl2php  Wed, 21 Nov 2018 13:11:08 +0100 - Last modified
 *
 * @WSDL      moreinfo.wsdl
 *
 * @Processed Tue, 20 Nov 2018 20:44:22 +0100
 *
 * @Hash      c0e22cf73947f4676ad67c6b82085672
 */

namespace App\Service\MoreInfoService;

use App\Exception\CoverStoreTransformationException;
use App\Service\CoverStore\CoverStoreTransformationInterface;
use App\Service\MetricsService;
use App\Service\MoreInfoService\Exception\MoreInfoException;
use App\Service\MoreInfoService\Types\AuthenticationType;
use App\Service\MoreInfoService\Types\FormatType;
use App\Service\MoreInfoService\Types\IdentifierInformationType;
use App\Service\MoreInfoService\Types\IdentifierType;
use App\Service\MoreInfoService\Types\ImageType;
use App\Service\MoreInfoService\Types\MoreInfoRequest;
use App\Service\MoreInfoService\Types\MoreInfoResponse;
use App\Service\MoreInfoService\Types\RequestStatusType;
use App\Service\MoreInfoService\Utils\NoHitItem;
use App\Service\NoHitService;
use App\Service\StatsLoggingService;
use Elastica\JSON;
use Elastica\Query;
use Elastica\Request;
use Elastica\Type;
use Psr\Log\LoggerInterface;
use ReflectionException;
use SoapClient;
use SoapHeader;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * moreInfoService class.
 *
 * @author wsdl2php
 */
abstract class AbstractMoreInfoService extends SoapClient
{
    private const FALLBACK_CODE = 'fallback';
    private const FALLBACK_IMAGE_URL = 'https://res.cloudinary.com/dandigbib/image/upload/v1576082092/default/forside-mangler-c.jpg';
    // Note: If the fallback image is changed the width and height should be updated to match the dimensions of the new image.
    private const FALLBACK_IMAGE_WIDTH = 2500;
    private const FALLBACK_IMAGE_HEIGHT = 3256;

    /**
     * Default class mapping for this service.
     *
     * @var array
     */
    private static $classMap = [
        'AuthenticationType' => AuthenticationType::class,
        'FormatType' => FormatType::class,
        'IdentifierInformationType' => IdentifierInformationType::class,
        'IdentifierType' => IdentifierType::class,
        'ImageType' => ImageType::class,
        'RequestStatusType' => RequestStatusType::class,
        'MoreInfoRequest' => MoreInfoRequest::class,
        'MoreInfoResponse' => MoreInfoResponse::class,
    ];

    private $index;
    private $statsLoggingService;
    private $metricsService;
    private $requestStack;
    private $dispatcher;
    private $transformer;
    private $noHitService;
    protected $logger;

    /**
     * MoreInfoService constructor.
     *
     * @param Type $index
     *   Elastica index
     * @param StatsLoggingService $statsLoggingService
     *   Statistics logging service
     * @param MetricsService $metricsService
     *   Metrics service to log stats
     * @param RequestStack $requestStack
     *   HTTP RequestStack
     * @param eventDispatcherInterface $dispatcher
     *   Dispatch events
     * @param coverStoreTransformationInterface $transformer
     *   URL transformation service
     * @param \App\Service\NoHitService $noHitService
     *   Service for registering no hits
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger
     * @param array $options
     *   Any additional parameters to add to the service
     *
     * @throws \SoapFault
     */
    public function __construct(Type $index, StatsLoggingService $statsLoggingService, MetricsService $metricsService, RequestStack $requestStack, EventDispatcherInterface $dispatcher, CoverStoreTransformationInterface $transformer, NoHitService $noHitService, LoggerInterface $logger, array $options = [])
    {
        $this->index = $index;
        $this->statsLoggingService = $statsLoggingService;
        $this->metricsService = $metricsService;
        $this->requestStack = $requestStack;
        $this->dispatcher = $dispatcher;
        $this->transformer = $transformer;
        $this->noHitService = $noHitService;
        $this->logger = $logger;

        // Add the classmap to the options.
        foreach (self::$classMap as $serviceClassName => $mappedClassName) {
            if (!isset($options['classmap'][$serviceClassName])) {
                $options['classmap'][$serviceClassName] = $mappedClassName;
            }
        }

        parent::__construct($this->getWsdl(), $options);
    }

    abstract protected function getNameSpace(): string;

    abstract protected function getWsdl(): string;

    abstract protected function provideDefaultCover(): bool;

    /**
     * Service call proxy.
     *
     * @param string $serviceName
     *   The name of the service being called
     * @param array $parameters
     *   The parameters being supplied to the service
     * @param SoapHeader[] $requestHeaders
     *   An array of SOAPHeaders
     *
     * @return mixed the service response
     */
    protected function callProxy(string $serviceName, array $parameters = null, array $requestHeaders = null)
    {
        $result = $this->__soapCall(
            $serviceName,
            $parameters,
            [
                'uri' => '',
                'soapaction' => '',
            ],
            !empty($requestHeaders) ? array_filter($requestHeaders) : null,
            $responseHeaders
        );

        if (!empty($responseHeaders)) {
            foreach ($responseHeaders as $headerName => $headerData) {
                $this->$headerName = $headerData;
            }
        }

        return $result;
    }

    /**
     * Build and populate a SOAP header.
     *
     * @param string $headerName
     *   The name of the services SOAP Header
     * @param array|object $rawHeaderData
     *   Any data that can be mapped to the SOAP Header. Public properties of objects will be used if an object is supplied.
     * @param string $namespace
     *   The namespace which will default to this service's namespace
     *
     * @throws ReflectionException
     */
    public function assignSoapHeader(string $headerName, $rawHeaderData = null, string $namespace): void
    {
        if (!$namespace) {
            $namespace = $this->getNameSpace();
        }

        // Is there a corresponding property of this service for the requested SOAP Header?
        // Is there a mapped class for this SOAP Header?
        // Do we have any data to populate the SOAP Header with?
        if (property_exists($this, $headerName) && isset(self::$classMap[$headerName]) && !empty($rawHeaderData)) {
            // Start with no data for the SOAP Header.
            $dataForSoapHeader = [];
            $mappedData = [];

            // Get the mapped class and get the properties defined for the SOAP Header.
            $reflectedHeader = new \ReflectionClass(self::$classMap[$headerName]);
            $reflectedHeaderProperties = $reflectedHeader->getProperties();

            // Produce an array of public data from an object.
            if (\is_object($rawHeaderData)) {
                $reflectedData = new \ReflectionClass($rawHeaderData);
                $reflectedDataProperties = $reflectedData->getProperties(\ReflectionProperty::IS_PUBLIC);
                $mappedData = [];
                foreach ($reflectedDataProperties as $property) {
                    $propertyName = $property->name;
                    $mappedData[$propertyName] = $rawHeaderData->$propertyName;
                }
            } elseif (\is_array($rawHeaderData)) {
                $mappedData = $rawHeaderData;
            }

            // Process the data as an array.
            if (!empty($mappedData)) {
                foreach ($reflectedHeaderProperties as $property) {
                    $propertyName = $property->name;
                    if (isset($mappedData[$propertyName])) {
                        $dataForSoapHeader[$propertyName] = $mappedData[$propertyName];
                    }
                }
            }

            // Build the SOAP Header and assign it the corresponding property.
            $this->$headerName = new SoapHeader($namespace, $headerName, $dataForSoapHeader);
        }
    }

    /**
     * Validate and respond to a 'moreInfo' SOAP request.
     *
     * @param $body
     *
     * @return MoreInfoResponse
     *
     * @throws MoreInfoException
     * @throws CoverStoreTransformationException
     */
    public function moreInfo($body): MoreInfoResponse
    {
        $startTime = microtime(true);
        $labels = ['type' => 'soapRequest'];

        $this->validateRequestAuthentication($body);

        $searchParameters = $this->getSearchParameters($body);

        // Build Elastic Query Results
        $query = $this->buildElasticQuery($searchParameters);

        // Note that we here don't uses the elastica request function to post the request to elasticsearch because we have
        // had strange performance issues with it. We get information about the index and create the curl call by hand.
        // We can do this as we know the complete setup and what should be taken into consideration.
        $index = $this->index->getIndex();
        $connection = $index->getClient()->getConnection();
        $path = $index->getName().'/search/_search';
        $url = $connection->hasConfig('url') ? $connection->getConfig('url') : '';
        $jsonQuery = JSON::stringify($query->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $startQueryTime = microtime(true);
        $ch = curl_init($url.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($jsonQuery),
        ]);
        $response = curl_exec($ch);
        $queryTime = microtime(true) - $startQueryTime;

        $results = [];
        if (false === $response) {
            $this->logger->error('Curl ES query error: '.curl_error($ch));
        } else {
            $results = JSON::parse($response);
            $results = $this->filterResults($results);
        }
        curl_close($ch);

        $this->metricsService->histogram('elastica_query_duration_seconds', 'Time used to run elasticsearch query', $queryTime, $labels);

        $time = microtime(true);
        $imageUrls = is_array($results) ? $this->getImageUrls($results) : [];
        $this->statsLoggingService->info('Cover request/response', [
            'service' => 'MoreInfoService',
            'clientID' => $body->authentication->authenticationGroup,
            'remoteIP' => $this->requestStack->getCurrentRequest()->getClientIp(),
            'searchParameters' => $searchParameters,
            'fileNames' => array_values($imageUrls),
            'matches' => $this->getMatches($imageUrls, $searchParameters),
            'elasticQueryTime' => $queryTime,
        ]);
        $this->metricsService->histogram('stats_logging_duration_seconds', 'Time used to log stats', microtime(true) - $time, $labels);

        $response = $this->buildSoapResponse($searchParameters, $results);

        $time = microtime(true);
        $this->registerSearchNoHits($response->identifierInformation);
        $this->metricsService->histogram('no_hit_event_duration_seconds', 'Time used to register no-hit event', microtime(true) - $time, $labels);

        $this->metricsService->histogram('request_duration_total_seconds', 'Total time used to handel soap request', microtime(true) - $startTime, $labels);

        return $response;
    }

    /**
     * Create array of matches between searches and found image urls.
     *
     * @param array $imageUrls
     *   Array of found image urls
     * @param array $searchParameters
     *   Array requested identifiers
     *
     * @return array
     *   Array of matches between found imageUrls and requested identifiers
     */
    private function getMatches(array $imageUrls, array $searchParameters)
    {
        $matches = [];

        foreach ($searchParameters as $searchKey => $searchParameter) {
            foreach ($searchParameter as $search) {
                $match = [
                    'match' => $imageUrls[$search] ?? null,
                    'identifier' => $search,
                    'type' => $searchKey,
                ];

                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * Filter raw search result from ES request.
     *
     * @param array $results
     *   Raw search result array
     *
     * @return array
     *   The filtered results
     */
    private function filterResults(array $results): array
    {
        $hits = [];
        if (is_array($results['hits']['hits'])) {
            $results = $results['hits']['hits'];
            foreach ($results as $result) {
                $hits[] = $result['_source'];
            }
        }

        return $hits;
    }

    /**
     * Send event to register identifiers that gave no search results.
     *
     * @param array $identifierInformation
     */
    private function registerSearchNoHits(array $identifierInformation): void
    {
        $noHits = $this->getNoHits($identifierInformation);

        if (!empty($noHits)) {
            $this->metricsService->counter('no_hits_total', 'Total number of no-hits', count($noHits), ['type' => 'soapRequest']);

            $this->noHitService->registerNoHits($noHits);
        }
    }

    /**
     * Get array of identifiers that were a no hit.
     *
     * @param array $identifierInformation
     *
     * @return array
     */
    private function getNoHits(array $identifierInformation): array
    {
        $noHits = [];

        if ($this->provideDefaultCover()) {
            foreach ($identifierInformation as $info) {
                foreach ($info->coverImage as $coverImage) {
                    if (self::FALLBACK_CODE === $coverImage->source) {
                        foreach ($info->identifier as $isType => $isIdentifier) {
                            if (!empty($isIdentifier)) {
                                $noHits[] = new NoHitItem($isType, $isIdentifier);
                            }
                        }
                        // We only set the source to be able to filter no hits
                        unset($coverImage->source);
                    }
                }
            }
        } else {
            foreach ($identifierInformation as $info) {
                if (!$info->identifierKnown) {
                    foreach ($info->identifier as $isType => $isIdentifier) {
                        if (!empty($isIdentifier)) {
                            $noHits[] = new NoHitItem($isType, $isIdentifier);
                        }
                    }
                }
            }
        }

        return $noHits;
    }

    /**
     * Validate SOAP request body authentication part.
     *
     * @param stdClass $body
     *
     * @throws MoreInfoException
     */
    private function validateRequestAuthentication(stdClass $body): void
    {
        // Check if authentication is set for usage logging.
        if (!property_exists($body->authentication, 'authenticationGroup')
            || empty($body->authentication->authenticationGroup)) {
            throw new MoreInfoException('authenticationGroup missing');
        }
    }

    /**
     * Get an array of search parameters from request body.
     *
     * @param stdClass $body
     *
     * @return array
     *
     * @throws MoreInfoException
     */
    public function getSearchParameters(stdClass $body): array
    {
        // Check for identifier on body
        if (!property_exists($body, 'identifier')) {
            throw new MoreInfoException('Request identifier missing');
        }

        // 'identifier' can be an array of stdClass objects or one single stdClass object.
        $identifiers = \is_array($body->identifier) ? $body->identifier : [$body->identifier];

        $searchParameters = [];

        foreach ($identifiers as $identifier) {
            if ('object' !== \gettype($identifier) && 'stdClass' !== \get_class($identifier)) {
                throw new MoreInfoException('Request identifier unknown type');
            }

            $identifierArray = get_object_vars($identifier);
            if (empty($identifierArray)) {
                throw new MoreInfoException('Request identifier unknown type');
            }

            foreach ($identifierArray as $isType => $isIdentifier) {
                if ('string' !== \gettype($isIdentifier)) {
                    throw new MoreInfoException('To many request identifiers');
                }

                if ('pidList' === $isType) {
                    $isType = 'pid';
                    $isIdentifiers = explode('|', $isIdentifier);
                } else {
                    $isIdentifiers = [$isIdentifier];
                }

                if (!array_key_exists($isType, $searchParameters)) {
                    $searchParameters[$isType] = [];
                }

                $this->mergeByReference($searchParameters[$isType], $isIdentifiers);
            }
        }

        return $searchParameters;
    }

    /**
     * Merge from one array into another by reference.
     *
     * PHPs array_merge() performance is not always optimal:
     * https://stackoverflow.com/questions/23348339/optimizing-array-merge-operation
     *
     * @param array $mergeTo
     * @param array $mergeFrom
     */
    private function mergeByReference(array &$mergeTo, array &$mergeFrom): void
    {
        foreach ($mergeFrom as $i) {
            $mergeTo[] = $i;
        }
    }

    /**
     * Build Elastic query from IS type and IS identifiers.
     *
     * @param array $searchParameters
     *
     * @return Query
     */
    private function buildElasticQuery(array $searchParameters): Query
    {
        $boolQuery = new Query\BoolQuery();

        $numberOfIdentifiers = 0;
        foreach ($searchParameters as $isType => $isIdentifiers) {
            foreach ($isIdentifiers as $identifier) {
                $identifierFieldTermQuery = new Query\Term();
                $identifierFieldTermQuery->setTerm('isIdentifier', $identifier);
                $boolQuery->addShould($identifierFieldTermQuery);
                ++$numberOfIdentifiers;
            }
        }

        $query = new Query();
        $query->setQuery($boolQuery);
        $query->setSize($numberOfIdentifiers);

        return $query;
    }

    /**
     * Build SOAP response object for 'moreInfo' request.
     *
     * @param array $searchParameters
     * @param array $results
     *
     * @return MoreInfoResponse
     *
     * @throws CoverStoreTransformationException
     */
    private function buildSoapResponse(array $searchParameters, array $results): MoreInfoResponse
    {
        $requestStatus = new RequestStatusType();
        $requestStatus->statusEnum = 'ok';
        $requestStatus->errorText = '';

        $identifierInformationList = [];
        foreach ($searchParameters as $isType => $isIdentifiers) {
            foreach ($isIdentifiers as $isIdentifier) {
                $identifierInformationList[$isIdentifier] = $this->getDefaultIdentifierInformation($isType, $isIdentifier);
            }
        }

        foreach ($results as $result) {
            $identifierInformation = $identifierInformationList[$result['isIdentifier']];
            $identifierInformation->identifierKnown = true;
            $identifierInformation->coverImage = [];

            $urls = $this->transformer->transformAll($result['imageUrl'], $result['width'], $result['height']);
            // Unset the original image size as it's not used in soap end-point.
            unset($urls['original']);
            foreach ($urls as $size => $url) {
                if (!is_null($url)) {
                    $image = new ImageType();
                    $image->_ = $url;
                    $image->imageSize = $this->imageSizeMapping($size);
                    // @TODO Implement format. Currently all formats return jpeg.
                    $image->imageFormat = $this->getImageFormat('jpeg');
                    $identifierInformation->coverImage[] = $image;
                }
            }
        }

        $response = new MoreInfoResponse();
        $response->requestStatus = $requestStatus;
        $response->identifierInformation = array_values($identifierInformationList);

        return $response;
    }

    /**
     * Mapping from transformation service image size to moreinfo size.
     *
     * This is an approximated image size mapping.
     *
     * @param string $size
     *   Transformation service image size
     *
     * @return string
     *   The approximated moreinfo image size
     */
    private function imageSizeMapping(string $size)
    {
        return [
            'default' => 'detail',
            'small' => 'detail_117',
            'medium' => 'detail_256',
            'large' => 'detail_500',
        ][$size];
    }

    /**
     * Get default default identifier with default image set.
     *
     * @param string $isType
     * @param string $isIdentifier
     *
     * @return IdentifierInformationType
     *
     * @throws CoverStoreTransformationException
     */
    private function getDefaultIdentifierInformation(string $isType, string $isIdentifier): IdentifierInformationType
    {
        $identifierInformation = new IdentifierInformationType();
        $identifierInformation->identifierKnown = false;

        $identifier = new IdentifierType();
        $identifier->{$isType} = $isIdentifier;

        $identifierInformation->identifier = $identifier;

        if ($this->provideDefaultCover()) {
            $image = new ImageType();
            // We do not allow upscaling so the image size here should be the size of the default image or at least the default image should be
            // bigger then the default transformation.
            $image->_ = $this->transformer->transform(self::FALLBACK_IMAGE_URL, self::FALLBACK_IMAGE_WIDTH, self::FALLBACK_IMAGE_HEIGHT, 'default');
            $image->imageSize = 'detail';
            $image->imageFormat = $this->getImageFormat(FormatType::JPEG);
            // Set source to fallback code to allow no hits filtering
            $image->source = self::FALLBACK_CODE;

            $identifierInformation->identifierKnown = true;
            $identifierInformation->coverImage[] = $image;
        }

        return $identifierInformation;
    }

    /**
     * Get image URLs from search result.
     *
     * @param array $results
     *   An array of result from an Elastica search
     *
     * @return array
     *   An array of image urls strings from the results
     */
    private function getImageUrls(array $results)
    {
        $urls = [];

        foreach ($results as $result) {
            if (isset($result['isIdentifier'])) {
                $urls[$result['isIdentifier']] = $result['imageUrl'];
            }
        }

        return $urls;
    }

    /**
     * Get the correct image format as defined in the XSD.
     *
     * @param string $format
     *
     * @return string
     */
    private function getImageFormat(string $format): string
    {
        $format = strtolower($format);

        switch ($format) {
            case 'gif':
                return FormatType::GIF;

            case 'pdf':
                return FormatType::PDF;

            // We default to 'jpeg' for compatibility reasons.
            // Format must match one of GIF/PDF/JPEG defined in XSD schema.
            default:
                return FormatType::JPEG;
        }
    }
}
