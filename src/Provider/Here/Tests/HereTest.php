<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author Sébastien Barré <sebastien@sheub.eu>
 */

namespace Geocoder\Provider\Here\Tests;

use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\IntegrationTest\CachedResponseClient;
use Geocoder\Location;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Provider\Here\Here;
use Http\Client\Curl\Client as HttplugClient;

class HereTest extends BaseTestCase
{
    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    /**
     * Get a real HTTP client. If a cache dir is set to a path it will use cached responses.
     *
     * @return HttpClient
     */
    protected function getHttpClient($apiKey = null, $appCode = null)
    {
        if (null !== $cacheDir = $this->getCacheDir()) {
            return new CachedResponseClient(new HttplugClient(), $cacheDir, $apiKey, $appCode);
        } else {
            return new HttplugClient();
        }
    }

    public function testReverseReturnsSeveralResults()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(45.84136, 1.24614));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(5, $results);
    }

    // testGeocodeQuery()

    public function testGeocodeWithRealAddress()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $results = $provider->geocodeQuery(GeocodeQuery::create('10 avenue Gambetta, Paris, France')->withLocale('fr-FR'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(48.8653, $result->getCoordinates()->getLatitude(), '', 0.01);
        $this->assertEquals(2.39844, $result->getCoordinates()->getLongitude(), '', 0.01);
        $this->assertNotNull($result->getBounds());
        $this->assertEquals(48.8664242, $result->getBounds()->getSouth(), '', 0.01);
        $this->assertEquals(2.3967311, $result->getBounds()->getWest(), '', 0.01);
        $this->assertEquals(48.8641758, $result->getBounds()->getNorth(), '', 0.01);
        $this->assertEquals(2.4001489, $result->getBounds()->getEast(), '', 0.01);
        $this->assertEquals(10, $result->getStreetNumber());

        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals(75020, $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
        $this->assertEquals('France', $result->getCountry()->getName());
        $this->assertEquals('FRA', $result->getCountry()->getCode());
    }

    /**
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithDefaultAdditionalData()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $results = $provider->geocodeQuery(GeocodeQuery::create('Sant Roc, Santa Coloma de Cervelló, Espanya')->withLocale('ca'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();

        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(41.37854, $result->getCoordinates()->getLatitude(), '', 0.01);
        $this->assertEquals(2.01196, $result->getCoordinates()->getLongitude(), '', 0.01);
        $this->assertNotNull($result->getBounds());
        $this->assertEquals(41.36505, $result->getBounds()->getSouth(), '', 0.01);
        $this->assertEquals(1.99398, $result->getBounds()->getWest(), '', 0.01);
        $this->assertEquals(41.39203, $result->getBounds()->getNorth(), '', 0.01);
        $this->assertEquals(2.02994, $result->getBounds()->getEast(), '', 0.01);

        $this->assertEquals('08690', $result->getPostalCode());
        $this->assertEquals('Sant Roc', $result->getSubLocality());
        $this->assertEquals('Santa Coloma de Cervelló', $result->getLocality());
        $this->assertEquals('Espanya', $result->getCountry()->getName());
        $this->assertEquals('ESP', $result->getCountry()->getCode());

        $this->assertEquals('Espanya', $result->getAdditionalDataValue('CountryName'));
        $this->assertEquals('Catalunya', $result->getAdditionalDataValue('StateName'));
        $this->assertEquals('Barcelona', $result->getAdditionalDataValue('CountyName'));
    }

    /**
     * Validation of some AdditionalData filters.
     * https://developer.here.com/documentation/geocoder/topics/resource-params-additional.html
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithAdditionalData()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $results = $provider->geocodeQuery(GeocodeQuery::create('Sant Roc, Santa Coloma de Cervelló, Espanya')
            ->withData('Country2', 'true')
            ->withData('IncludeShapeLevel', 'country')
            ->withData('IncludeRoutingInformation', 'true')
            ->withLocale('ca'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(41.37854, $result->getCoordinates()->getLatitude(), '', 0.01);
        $this->assertEquals(2.01196, $result->getCoordinates()->getLongitude(), '', 0.01);
        $this->assertNotNull($result->getBounds());
        $this->assertEquals(41.36505, $result->getBounds()->getSouth(), '', 0.01);
        $this->assertEquals(1.99398, $result->getBounds()->getWest(), '', 0.01);
        $this->assertEquals(41.39203, $result->getBounds()->getNorth(), '', 0.01);
        $this->assertEquals(2.02994, $result->getBounds()->getEast(), '', 0.01);

        $this->assertEquals('08690', $result->getPostalCode());
        $this->assertEquals('Sant Roc', $result->getSubLocality());
        $this->assertEquals('Santa Coloma de Cervelló', $result->getLocality());
        $this->assertEquals('Espanya', $result->getCountry()->getName());
        $this->assertEquals('ESP', $result->getCountry()->getCode());

        $this->assertEquals('ES', $result->getAdditionalDataValue('Country2'));
        $this->assertEquals('Espanya', $result->getAdditionalDataValue('CountryName'));
        $this->assertEquals('Catalunya', $result->getAdditionalDataValue('StateName'));
        $this->assertEquals('Barcelona', $result->getAdditionalDataValue('CountyName'));
        $this->assertEquals('district', $result->getAdditionalDataValue('routing_address_matchLevel'));
        $this->assertEquals('NT_TzyupfxmTFN0Rh1TXEMqSA', $result->getAdditionalDataValue('routing_locationId'));
        $this->assertEquals('address', $result->getAdditionalDataValue('routing_result_type'));
        $this->assertEquals('WKTShapeType', $result->getShapeValue('_type'));
        $this->assertRegexp('/^MULTIPOLYGON/', $result->getShapeValue('Value'));
    }

    /**
     * Search for a specific city in a different country.
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithExtraFilterCountry()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);

        $queryBarcelonaFromSpain = GeocodeQuery::create('Barcelona')->withData('country', 'ES')->withLocale('ca');
        $queryBarcelonaFromVenezuela = GeocodeQuery::create('Barcelona')->withData('country', 'VE')->withLocale('ca');

        $resultsSpain = $provider->geocodeQuery($queryBarcelonaFromSpain);
        $resultsVenezuela = $provider->geocodeQuery($queryBarcelonaFromVenezuela);

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsSpain);
        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsVenezuela);
        $this->assertCount(1, $resultsSpain);
        $this->assertCount(1, $resultsVenezuela);

        $resultSpain = $resultsSpain->first();
        $resultVenezuela = $resultsVenezuela->first();

        $this->assertEquals('Barcelona', $resultSpain->getLocality());
        $this->assertEquals('Barcelona', $resultVenezuela->getLocality());
        $this->assertEquals('Espanya', $resultSpain->getCountry()->getName());
        $this->assertEquals('República Bolivariana De Venezuela', $resultVenezuela->getCountry()->getName());
        $this->assertEquals('ESP', $resultSpain->getCountry()->getCode());
        $this->assertEquals('VEN', $resultVenezuela->getCountry()->getCode());
    }

    /**
     * Search for a specific street in different towns in the same country.
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithExtraFilterCity()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);

        $queryStreetCity1 = GeocodeQuery::create('Carrer de Barcelona')->withData('city', 'Sant Vicenç dels Horts')->withLocale('ca')->withLimit(1);
        $queryStreetCity2 = GeocodeQuery::create('Carrer de Barcelona')->withData('city', 'Girona')->withLocale('ca')->withLimit(1);
        $queryStreetCity3 = GeocodeQuery::create('Carrer de Barcelona')->withData('city', 'Pallejà')->withLocale('ca')->withLimit(1);

        $resultsCity1 = $provider->geocodeQuery($queryStreetCity1);
        $resultsCity2 = $provider->geocodeQuery($queryStreetCity2);
        $resultsCity3 = $provider->geocodeQuery($queryStreetCity3);

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsCity1);
        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsCity2);
        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsCity3);

        $resultCity1 = $resultsCity1->first();
        $resultCity2 = $resultsCity2->first();
        $resultCity3 = $resultsCity3->first();

        $this->assertEquals('Carrer de Barcelona', $resultCity1->getStreetName());
        $this->assertEquals('Carrer de Barcelona', $resultCity2->getStreetName());
        $this->assertEquals('Carrer de Barcelona', $resultCity3->getStreetName());
        $this->assertEquals('Sant Vicenç dels Horts', $resultCity1->getLocality());
        $this->assertEquals('Girona', $resultCity2->getLocality());
        $this->assertEquals('Pallejà', $resultCity3->getLocality());
        $this->assertEquals('Espanya', $resultCity1->getCountry()->getName());
        $this->assertEquals('Espanya', $resultCity2->getCountry()->getName());
        $this->assertEquals('Espanya', $resultCity3->getCountry()->getName());
        $this->assertEquals('ESP', $resultCity1->getCountry()->getCode());
        $this->assertEquals('ESP', $resultCity2->getCountry()->getCode());
        $this->assertEquals('ESP', $resultCity3->getCountry()->getCode());
    }

    public function testGeocodeWithExtraFilterCounty()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);

        $queryCityRegion1 = GeocodeQuery::create('Cabanes')->withData('county', 'Girona')->withLocale('ca')->withLimit(1);
        $queryCityRegion2 = GeocodeQuery::create('Cabanes')->withData('county', 'Castelló')->withLocale('ca')->withLimit(1);

        $resultsRegion1 = $provider->geocodeQuery($queryCityRegion1);
        $resultsRegion2 = $provider->geocodeQuery($queryCityRegion2);

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsRegion1);
        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $resultsRegion2);

        $resultRegion1 = $resultsRegion1->first();
        $resultRegion2 = $resultsRegion2->first();

        $this->assertEquals('Cabanes', $resultRegion1->getLocality());
        $this->assertEquals('Cabanes', $resultRegion2->getLocality());
        $this->assertEquals('Girona', $resultRegion1->getAdditionalDataValue('CountyName'));
        $this->assertEquals('Castelló', $resultRegion2->getAdditionalDataValue('CountyName'));
        $this->assertEquals('Catalunya', $resultRegion1->getAdditionalDataValue('StateName'));
        $this->assertEquals('Comunitat Valenciana', $resultRegion2->getAdditionalDataValue('StateName'));
        $this->assertEquals('Espanya', $resultRegion1->getCountry()->getName());
        $this->assertEquals('Espanya', $resultRegion2->getCountry()->getName());
        $this->assertEquals('ESP', $resultRegion1->getCountry()->getCode());
        $this->assertEquals('ESP', $resultRegion2->getCountry()->getCode());
    }

    public function testReverseWithRealCoordinates()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient($_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(48.8632156, 2.3887722));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(5, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(48.8632156, $result->getCoordinates()->getLatitude(), '', 0.0001);
        $this->assertEquals(2.3887722, $result->getCoordinates()->getLongitude(), '', 0.0001);
        $this->assertNotNull($result->getBounds());
        $this->assertEquals(48.8621758, $result->getBounds()->getSouth(), '', 0.0001);
        $this->assertEquals(2.3870312, $result->getBounds()->getWest(), '', 0.0001);
        $this->assertEquals(48.8644242, $result->getBounds()->getNorth(), '', 0.0001);
        $this->assertEquals(2.3904488, $result->getBounds()->getEast(), '', 0.0001);
        $this->assertEquals(1, $result->getStreetNumber());
        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals(75020, $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
        $this->assertEquals('France', $result->getCountry()->getName());
        $this->assertEquals('FRA', $result->getCountry()->getCode());
    }

    public function testGetName()
    {
        $provider = new Here($this->getMockedHttpClient(), 'appId', 'appCode');
        $this->assertEquals('Here', $provider->getName());
    }

    /**
     * @expectedException \Geocoder\Exception\InvalidServerResponse
     */
    public function testGeocodeWithInvalidData()
    {
        $provider = new Here($this->getMockedHttpClient(), 'appId', 'appCode');
        $provider->geocodeQuery(GeocodeQuery::create('foobar'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The Here provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeIpv4()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient(), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The Here provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithLocalhostIPv6()
    {
        $provider = new Here($this->getMockedHttpClient(), 'appId', 'appCode');
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    /**
     * @expectedException \Geocoder\Exception\InvalidCredentials
     * @expectedExceptionMessage Invalid or missing api key.
     */
    public function testGeocodeInvalidApiKey()
    {
        $provider = new Here(
            $this->getMockedHttpClient(
                '{
					"type": {
						"subtype": "InvalidCredentials"
					}
                }'
            ),
            'appId',
            'appCode'
        );
        $provider->geocodeQuery(GeocodeQuery::create('New York'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The Here provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithRealIPv6()
    {
        if (!isset($_SERVER['HERE_APP_ID']) || !isset($_SERVER['HERE_APP_CODE'])) {
            $this->markTestSkipped('You need to configure the HERE_APP_ID and HERE_APP_CODE value in phpunit.xml');
        }

        $provider = new Here($this->getHttpClient(), $_SERVER['HERE_APP_ID'], $_SERVER['HERE_APP_CODE']);
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }
}
