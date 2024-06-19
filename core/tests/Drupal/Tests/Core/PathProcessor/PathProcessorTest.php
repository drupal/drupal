<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PathProcessor;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\PathProcessorDecode;
use Drupal\Core\PathProcessor\PathProcessorFront;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\language\HttpKernel\PathProcessorLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\path_alias\AliasManager;
use Drupal\path_alias\PathProcessor\AliasPathProcessor;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

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
   * The language manager stub used to construct a PathProcessorLanguage object.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface|\PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up some languages to be used by the language-based path processor.
    $languages = [];
    foreach (['en', 'fr'] as $langcode) {
      $language = new Language(['id' => $langcode]);
      $languages[$langcode] = $language;
    }
    $this->languages = $languages;

    // Create a stub configuration.
    $language_prefixes = array_keys($this->languages);
    $config = [
      'url' => [
        'prefixes' => array_combine($language_prefixes, $language_prefixes),
      ],
    ];

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($languages['en']);
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->willReturn($this->languages);
    $language_manager->expects($this->any())
      ->method('getLanguageTypes')
      ->willReturn([LanguageInterface::TYPE_INTERFACE]);

    $this->languageManager = $language_manager;
  }

  /**
   * Tests resolving the inbound path to the system path.
   */
  public function testProcessInbound(): void {

    // Create an alias manager stub.
    $alias_manager = $this->getMockBuilder(AliasManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $system_path_map = [
      // Set up one proper alias that can be resolved to a system path.
      ['/foo', NULL, '/user/1'],
      // Passing in anything else should return the same string.
      ['/fr/foo', NULL, '/fr/foo'],
      ['/fr', NULL, '/fr'],
      ['/user/login', NULL, '/user/login'],
    ];

    $alias_manager->expects($this->any())
      ->method('getPathByAlias')
      ->willReturnMap($system_path_map);

    // Create a stub config factory with all config settings that will be checked
    // during this test.
    $config_factory_stub = $this->getConfigFactoryStub(
      [
        'system.site' => [
          'page.front' => '/user/login',
        ],
        'language.negotiation' => [
          'url' => [
            'prefixes' => ['fr' => 'fr'],
            'source' => LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          ],
        ],
      ]
    );

    // Create a language negotiator stub.
    $negotiator = $this->getMockBuilder('Drupal\language\LanguageNegotiatorInterface')
      ->getMock();
    $negotiator->expects($this->any())
      ->method('getNegotiationMethods')
      ->willReturn([
        LanguageNegotiationUrl::METHOD_ID => [
          'class' => 'Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl',
          'weight' => 9,
        ],
      ]);
    $method = new LanguageNegotiationUrl();
    $method->setConfig($config_factory_stub);
    $method->setLanguageManager($this->languageManager);
    $negotiator->expects($this->any())
      ->method('getNegotiationMethodInstance')
      ->willReturn($method);

    // Create a user stub.
    $current_user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    // Create a config event subscriber stub.
    $config_subscriber = $this->getMockBuilder('Drupal\language\EventSubscriber\ConfigSubscriber')
      ->disableOriginalConstructor()
      ->getMock();

    // Create the processors.
    $alias_processor = new AliasPathProcessor($alias_manager);
    $decode_processor = new PathProcessorDecode();
    $front_processor = new PathProcessorFront($config_factory_stub);
    $language_processor = new PathProcessorLanguage($config_factory_stub, $this->languageManager, $negotiator, $current_user, $config_subscriber);

    // First, test the processor manager with the processors in the incorrect
    // order. The alias processor will run before the language processor, meaning
    // aliases will not be found.
    $priorities = [
      1000 => $alias_processor,
      500 => $decode_processor,
      300 => $front_processor,
      200 => $language_processor,
    ];

    // Create the processor manager and add the processors.
    $processor_manager = new PathProcessorManager();
    foreach ($priorities as $priority => $processor) {
      $processor_manager->addInbound($processor, $priority);
    }

    // Test resolving the French homepage using the incorrect processor order.
    $test_path = '/fr';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('/', $processed, 'Processing in the incorrect order fails to resolve the system path from the empty path');

    // Test resolving an existing alias using the incorrect processor order.
    $test_path = '/fr/foo';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('/foo', $processed, 'Processing in the incorrect order fails to resolve the system path from an alias');

    // Now create a new processor manager and add the processors, this time in
    // the correct order.
    $processor_manager = new PathProcessorManager();
    $priorities = [
      1000 => $decode_processor,
      500 => $language_processor,
      300 => $front_processor,
      200 => $alias_processor,
    ];
    foreach ($priorities as $priority => $processor) {
      $processor_manager->addInbound($processor, $priority);
    }

    // Test resolving the French homepage using the correct processor order.
    $test_path = '/fr';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('/user/login', $processed, 'Processing in the correct order resolves the system path from the empty path.');

    // Test resolving an existing alias using the correct processor order.
    $test_path = '/fr/foo';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEquals('/user/1', $processed, 'Processing in the correct order resolves the system path from an alias.');
  }

}
