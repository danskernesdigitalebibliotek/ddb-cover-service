<?php

namespace App\DataFixtures;

use App\DataFixtures\Faker\Provider\ImageProvider;
use App\DataFixtures\Faker\Provider\SearchProvider;
use App\DataFixtures\Faker\Provider\SourceProvider;
use App\DataFixtures\Faker\Provider\VendorProvider;
use App\Entity\Image;
use App\Entity\Search;
use App\Entity\Source;
use App\Entity\Vendor;
use App\Utils\Types\IdentifierType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Provider\Barcode;
use Faker\Provider\Internet;
use Faker\Provider\Miscellaneous;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $faker->addProvider(new Miscellaneous($faker));
        $faker->addProvider(new Internet($faker));
        $faker->addProvider(new Barcode($faker));
        $faker->addProvider(new SourceProvider($faker));
        $faker->addProvider(new ImageProvider($faker));
        $faker->addProvider(new VendorProvider($faker));
        $faker->addProvider(new SearchProvider($faker));

        $vendor = new Vendor();
        $vendor->setName($faker->name);
        $vendor->setImageServerURI($faker->imageServerURI($vendor->getName()));
        $vendor->setDataServerURI($faker->dataServerURI($vendor->getName()));
        $vendor->setDataServerUser($faker->dataServerUser);
        $vendor->setDataServerPassword($faker->dataServerPassword);
        $vendor->setClass($faker->name);
        $vendor->setId(0);
        $vendor->setRank(1);

        $manager->persist($vendor);

        for ($i = 0; $i < 1000; ++$i) {
            $source = new Source();
            $source->setVendor($vendor);
            $source->setDate($faker->date);
            $source->setMatchId($faker->matchId);
            $source->setMatchType($faker->matchType);

            $image = new Image();
            $image->setSource($source);
            $image->setImageFormat($faker->originalImageFormat);
            $image->setHeight($faker->height);
            $image->setWidth($faker->width);
            $image->setSize($image->getHeight() * $image->getWidth());
            $image->setCoverStoreURL($faker->coverStoreURL);

            $search1 = new Search();
            $search1->setIsType(IdentifierType::ISBN);
            $search1->setIsIdentifier($source->getMatchId());
            $search1->setImageFormat($image->getImageFormat());
            $search1->setImageUrl($image->getCoverStoreURL());
            $search1->setWidth($image->getWidth());
            $search1->setHeight($image->getHeight());
            $search1->setSource($source);

            $search2 = new Search();
            $search2->setIsType('PID');
            $search2->setIsIdentifier($faker->pid);
            $search2->setImageFormat($image->getImageFormat());
            $search2->setImageUrl($image->getCoverStoreURL());
            $search2->setWidth($image->getWidth());
            $search2->setHeight($image->getHeight());
            $search2->setSource($source);

            $search3 = new Search();
            $search3->setIsType('FAUST');
            $search3->setIsIdentifier($faker->faust);
            $search3->setImageFormat($image->getImageFormat());
            $search3->setImageUrl($image->getCoverStoreURL());
            $search3->setWidth($image->getWidth());
            $search3->setHeight($image->getHeight());
            $search3->setSource($source);

            $manager->persist($source);
            $manager->persist($image);
            $manager->persist($search1);
            $manager->persist($search2);
            $manager->persist($search3);
        }

        $manager->flush();
    }
}
