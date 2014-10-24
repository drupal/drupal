<?php

/**
 * @file
 * Contains Drupal\Tests\Core\PathProcessor\PathProcessorTest.
 */

namespace Drupal\Tests\Core\PathProcessor;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\PathProcessor\PathProcessorDecode;
use Drupal\Core\PathProcessor\PathProcessorFront;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Site\Settings;
use Drupal\language\HttpKernel\PathProcessorLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Tests\UnitTestCase;

/**
 * Tests processing of the inbound path.
 *
 * @group PathProcessor
 */
class PathProcessorTest extends UnitTestCase {

  /**
   * Configuration for the languageManager stub.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $languages;

  /**
   *  The language manager stub used to construct a PathProcessorLanguage object.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface|\PHPUnit_Framework_MockObject_MockBuilder
   */
  protected $languageManager;

  protected function setUp() {

    // Set up some languages to be used by the language-based path processor.
    $languages = array();
    foreach (array('en', 'fr') as $langcode) {
      $language = new Language(array('id' => $langcode));
      $languages[$langcode] = $language;
    }
    $this->languages = $languages;

    // Create a stub configuration.
    $language_prefixes = array_keys($this->languages);
    $config = array(
      'url' => array(
        'prefixes' => array_combine($language_prefixes, $language_prefixes)
      )
    );

    // Create a URL-based language negotiation method definition.
    $method_definitions = array(
      LanguageNegotiationUrl::METHOD_ID => array(
        'class' => '\Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl',
      ),
    );

    // Create a URL-based language negotiation method.
    $method_instance = new LanguageNegotiationUrl($config);

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($languages['en']));
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue($this->languages));
    $language_manager->expects($this->any())
      ->method('getLanguageTypes')
      ->will($this->returnValue(array(LanguageInterface::TYPE_INTERFACE)));
    $language_manager->expects($this->any())
      ->method('getNegotiationMethods')
      ->will($this->returnValue($method_definitions));
    $language_manager->expects($this->any())
      ->method('getNegotiationMethodInstance')
      ->will($this->returnValue($method_instance));

    $method_instance->setLanguageManager($language_manager);
    $this->languageManager = $language_manager;
  }

  /**
   * Tests resolving the inbound path to the system path.
   */
  function testProcessInbound() {

    // Create an alias manager stub.
    $alias_manager = $this->getMockBuilder('Drupal\Core\Path\AliasManager')
      ->disableOriginalConstructor()
      ->getMock();

    $system_path_map = array(
      // Set up one proper alias that can be resolved to a system path.
      array('foo', NULL, 'user/1'),
      // Passing in anything else should return the same string.
      array('fr/foo', NULL, 'fr/foo'),
      array('fr', NULL, 'fr'),
      array('user/login', NULL, 'user/login'),
    );

    $alias_manager->expects($this->any())
      ->method('getPathByAlias')
      ->will($this->returnValueMap($system_path_map));

    // Create a stub config factory with all config settings that will be checked
    // during this test.
    $config_factory_stub = $this->getConfigFactoryStub(
      array(
        'system.site' => array(
          'page.front' => 'user/login'
        ),
        'language.negotiation' => array(
          'url' => array(
            'prefixes' => array('fr' => 'fr'),
          ),
        ),
      )
    );

    // Create a language negotiator stub.
    $negotiator = $this->getMockBuilder('Drupal\language\LanguageNegotiatorInterface')
      ->getMock();
    $negotiator->expects($this->any())
      ->method('getNegotiationMethods')
      ->will($this->returnValue(array(LanguageNegotiationUrl::METHOD_ID => array('class' => 'Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl'))));
    $method = new LanguageNegotiationUrl();
    $method->setConfig($config_factory_stub);
    $method->setLanguageManager($this->languageManager);
    $negotiator->expects($this->any())
      ->method('getNegotiationMethodInstance')
      ->will($this->returnValue($method));

    // Create a user stub.
    $current_user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    // Create the processors.
    $alias_processor = new PathProcessorAlias($alias_manager);
    $decode_processor = new PathProcessorDecode();
    $front_processor = new PathProcessorFront($config_factory_stub);
    $language_processor = new PathProcessorLanguage($config_factory_stub, new Settings(array()), $this->languageManager, $negotiator, $current_user);

    // First, test the processor manager with the processors in the incorrect
    // order. The alias processor will run before the language processor, meaning
    // aliases will not be found.
    $priorities = array(
      1000 => $alias_processor,
      500 => $decode_processor,
      300 => $front_processor,
      200 => $language_processor,
    );

    // Create the processor manager and add the processors.
    $processor_manager = new PathProcessorManager();
    foreach ($priorities as $priority => $processor) {
      $processor_manager->addInbound($processor, $priority);
    }

    // Test resolving the French homepage using the incorrect processor order.
    $test_path = 'fr';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('', $processed, 'Processing in the incorrect order fails to resolve the system path from the empty path');

    // Test resolving an existing alias using the incorrect processor order.
    $test_path = 'fr/foo';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('foo', $processed, 'Processing in the incorrect order fails to resolve the system path from an alias');

    // Now create a new processor manager and add the processors, this time in
    // the correct order.
    $processor_manager = new PathProcessorManager();
    $priorities = array(
      1000 => $decode_processor,
      500 => $language_processor,
      300 => $front_processor,
      200 => $alias_processor,
    );
    foreach ($priorities as $priority => $processor) {
      $processor_manager->addInbound($processor, $priority);
    }

    // Test resolving the French homepage using the correct processor order.
    $test_path = 'fr';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('user/login', $processed, 'Processing in the correct order resolves the system path from the empty path.');

    // Test resolving an existing alias using the correct processor order.
    $test_path = 'fr/foo';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('user/1', $processed, 'Processing in the correct order resolves the system path from an alias.');
  }
}
