<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base as BaseProvider;

final class VendorProvider extends BaseProvider
{
    private const VENDOR_PROVIDER = ['Bogportalen'];
    private const DATA_SERVER_URL_PROVIDER = [
        'Bogportalen' => 'ftp://products.bogportalen.dk/',
    ];
    private const IMAGE_SERVER_URL_PROVIDER = [
        'Bogportalen' => 'https://images.bogportalen.dk/images/',
    ];

    public function name()
    {
        return self::randomElement(self::VENDOR_PROVIDER);
    }

    public function dataServerURI($name)
    {
        return self::randomElement(self::VENDOR_PROVIDER);
    }

    public function imageServerURI($name)
    {
        return self::randomElement(self::IMAGE_SERVER_URL_PROVIDER);
    }

    public function dataServerUser()
    {
        return $this->generator->username();
    }

    public function dataServerPassword()
    {
        return $this->generator->password();
    }
}
