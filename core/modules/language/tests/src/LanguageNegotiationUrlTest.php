<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageNegotiationUrlTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the URL and domain language negotiation.
 *
 * @group Language
 *
 * @see \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl
 */
class LanguageNegotiationUrlTest extends UnitTestCase {

  protected $languageManager;
  protected $user;

  public static function getInfo() {
    return array(
      'name' => 'Language negotiation URL',
      'description' => 'Tests the URL/domain Language negotiation plugin',
      'group' => 'Language',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    // Set up some languages to be used by the language-based path processor.
    $languages = array(
      'de' => (object) array(
        'id' => 'de',
      ),
      'en' => (object) array(
        'id' => 'en',
      ),
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
      'expected_langocde' => 'de',
    );
    // No configuration.
    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(),
      'expected_langocde' => FALSE,
    );
    // HTTP host with a port.
    $domain_configuration[] = array(
      'http_host' => 'example.de:8080',
      'domains' => array(
        'de' => 'http://example.de',
      ),
      'expected_langocde' => 'de',
    );
    // Domain configuration with https://.
    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(
        'de' => 'https://example.de',
      ),
      'expected_langocde' => 'de',
    );
    // Non-matching HTTP host.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'de' => 'http://example.com',
      ),
      'expected_langocde' => 'de',
    );
    // Testing a non-existing language.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'it' => 'http://example.it',
      ),
      'expected_langocde' => FALSE,
    );
    // Multiple domain configurations.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'de' => 'http://example.de',
        'en' => 'http://example.com',
      ),
      'expected_langocde' => 'en',
    );
    return $domain_configuration;
  }
}
