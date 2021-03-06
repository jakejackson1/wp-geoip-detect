<?php

function ipTestServiceProvider() {
	return array(GEOIP_DETECT_TEST_IP_SERIVCE_PROVIDER);	
}

function ipTestServiceInvalidProvider() {
	return array('http://aaa.example.org/test');
}

class ExternalIpTest extends WP_UnitTestCase_GeoIP_Detect {
	
	function setUp()
	{
		parent::setUp();
	}
	
	function tearDown()
	{
		parent::tearDown();
		remove_filter('geiop_detect_ipservices','ipTestServiceProvider', 101);
		remove_filter('geiop_detect_ipservices', array($this, 'externalIpProvidersFilter'), 101);
		remove_filter('geiop_detect_ipservices', 'ipTestServiceInvalidProvider', 101);
	}
	
	
	function testPublicIpFilter() {
		$this->assertSame(true, geoip_detect_is_public_ip(GEOIP_DETECT_TEST_IP));
		$this->assertSame(false, geoip_detect_is_public_ip('10.0.0.2'));
		$this->assertSame(false, geoip_detect_is_public_ip('169.254.1.1'));
	}
	
	function testLoopbackFilter() {
		$this->assertSame(false, geoip_detect_is_public_ip('::1'));	
		$this->assertSame(false, geoip_detect_is_public_ip('127.0.0.1'));
		$this->assertSame(false, geoip_detect_is_public_ip('127.0.1.1'));
	}

	function testInvalidIpFilter() {
		$this->assertSame(false, geoip_detect_is_public_ip('999.0.0.1'));
		$this->assertSame(false, geoip_detect_is_public_ip('asdfasfasdf'));
		$this->assertSame(false, geoip_detect_is_public_ip(':::'));
		$this->assertSame(false, geoip_detect_is_public_ip(''));
	}
	
	function testExternalIp() {
		add_filter('geiop_detect_ipservices', 'ipTestServiceProvider', 101);
		
		$ip = _geoip_detect_get_external_ip_adress_without_cache();
		$this->assertNotEquals('0.0.0.0', $ip);
	}
	
	function testInvalidIp() {
		add_filter('geiop_detect_ipservices', 'ipTestServiceInvalidProvider', 101);
		
		try {
			_geoip_detect_get_external_ip_adress_without_cache();
		} catch (Exception $e) {
			$this->assertSame('', '');
			return;
		}
		$this->fail('Invalid IP provider did not provoke an error');
	}
	
	function testCurrentIpCli() {
		$ret = geoip_detect2_get_info_from_current_ip();
		$this->assertValidGeoIP2Record($ret, 'current');
	}
	
	/**
	 * @group external-http
	 */
	function testExternalIpProviders() {
		$this->markTestSkipped('This test should not be executed by Travis.');
		
		add_filter('geiop_detect_ipservices', array($this, 'externalIpProvidersFilter'), 101);
		
		$this->providers = null;
		
		do {
			$ip = _geoip_detect_get_external_ip_adress_without_cache();
			$this->assertNotEquals('0.0.0.0', $ip, 'Provider did not work: ' . $this->currentProvider);	
		} while (count($this->providers));
	}
	
	protected $providers;
	protected $currentProvider;
	
	function externalIpProvidersFilter($providers) {
		if (is_null($this->providers)) {
			$this->providers = $providers; 
		}
		$this->currentProvider = array_pop($this->providers);
		
		return array($this->currentProvider);
	}
}
