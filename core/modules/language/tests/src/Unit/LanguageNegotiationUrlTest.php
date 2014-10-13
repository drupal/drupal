<?php

/**
 * @file
 * Contains \Drupal\Tests\language\Unit\LanguageNegotiationUrlTest.
 */

namespace Drupal\Tests\language\Unit;

use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl
 * @group language
 */
class LanguageNegotiationUrlTest extends UnitTestCase {

  protected $languageManager;
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language_de->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('de'));
    $language_en = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language_en->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $languages = array(
      'de' => $language_de,
      'en' => $language_en,
    );

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($languages['en']));
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue($languages));
    $this->languageManager = $language_manager;

    // Create a user stub.
    $this->user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();
  }

  /**
   * Test domain language negotiation.
   *
   * @dataProvider providerTestDomain
   */
  public function testDomain($http_host, $domains, $expected_langcode) {
    $config_data = array(
      'source' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domains' => $domains,
    );

    $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config_object->expects($this->any())
      ->method('get')
      ->with('url')
      ->will($this->returnValue($config_data));

    $config = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config->expects($this->any())
      ->method('get')
      ->with('language.negotiation')
      ->will($this->returnValue($config_object));

    $request = Request::create('', 'GET', array(), array(), array(), array('HTTP_HOST' => $http_host));
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertEquals($expected_langcode, $method->getLangcode($request));
  }

  /**
   * Provides data for the domain test.
   *
   * @return array
   *   An array of data for checking domain negotation.
   */
  public function providerTestDomain() {

    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(
        'de' => 'http://example.de',
      ),
      'expected_langcode' => 'de',
    );
    // No configuration.
    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(),
      'expected_langcode' => FALSE,
    );
    // HTTP host with a port.
    $domain_configuration[] = array(
      'http_host' => 'example.de:8080',
      'domains' => array(
        'de' => 'http://example.de',
      ),
      'expected_langcode' => 'de',
    );
    // Domain configuration with https://.
    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(
        'de' => 'https://example.de',
      ),
      'expected_langcode' => 'de',
    );
    // Non-matching HTTP host.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'de' => 'http://example.com',
      ),
      'expected_langcode' => 'de',
    );
    // Testing a non-existing language.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'it' => 'http://example.it',
      ),
      'expected_langcode' => FALSE,
    );
    // Multiple domain configurations.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'de' => 'http://example.de',
        'en' => 'http://example.com',
      ),
      'expected_langcode' => 'en',
    );
    return $domain_configuration;
  }
}
