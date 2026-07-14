<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl.
 */
#[CoversClass(LanguageNegotiationUrl::class)]
#[Group('language')]
class LanguageNegotiationUrlTest extends UnitTestCase {

  /**
   * The language manager.
   */
  protected LanguageManagerInterface&MockObject $languageManager;

  /**
   * The test user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * An array of languages.
   */
  protected array $languages;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->createStub(LanguageInterface::class);
    $language_de
      ->method('getId')
      ->willReturn('de');
    $language_en = $this->createStub(LanguageInterface::class);
    $language_en
      ->method('getId')
      ->willReturn('en');
    $languages = [
      'de' => $language_de,
      'en' => $language_en,
    ];
    $this->languages = $languages;

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->atLeastOnce())
      ->method('getLanguages')
      ->willReturn($languages);
    $this->languageManager = $language_manager;

    // Create a user stub.
    $this->user = $this->createStub(AccountInterface::class);

    $cache_contexts_manager = $this->createStub(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests path prefix language negotiation and outbound path processing.
   */
  #[DataProvider('providerTestPathPrefix')]
  public function testPathPrefix(string $prefix, $prefixes, $expected_langcode): void {
    $this->languageManager
      ->method('getCurrentLanguage')
      ->willReturn($this->languages[(in_array($expected_langcode, [
        'en',
        'de',
      ])) ? $expected_langcode : 'en']);

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          'prefixes' => $prefixes,
        ],
      ],
    ]);

    $request = Request::create('/' . $prefix . '/foo', 'GET');
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertEquals($expected_langcode, $method->getLangcode($request));

    $cacheability = new BubbleableMetadata();
    $options = [];
    $method->processOutbound('foo', $options, $request, $cacheability);
    $expected_cacheability = new BubbleableMetadata();
    if ($expected_langcode) {
      $this->assertSame($prefix . '/', $options['prefix']);
      $expected_cacheability->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
    }
    else {
      $this->assertFalse(isset($options['prefix']));
    }
    $this->assertEquals($expected_cacheability, $cacheability);
  }

  /**
   * Provides data for the path prefix test.
   *
   * @return array
   *   An array of data for checking path prefix negotiation.
   */
  public static function providerTestPathPrefix(): array {
    $path_prefix_configuration[] = [
      'prefix' => 'de',
      'prefixes' => [
        'de' => 'de',
        'en-uk' => 'en',
      ],
      'expected_langcode' => 'de',
    ];
    $path_prefix_configuration[] = [
      'prefix' => 'en-uk',
      'prefixes' => [
        'de' => 'de',
        'en' => 'en-uk',
      ],
      'expected_langcode' => 'en',
    ];
    // No configuration.
    $path_prefix_configuration[] = [
      'prefix' => 'de',
      'prefixes' => [],
      'expected_langcode' => FALSE,
    ];
    // Non-matching prefix.
    $path_prefix_configuration[] = [
      'prefix' => 'de',
      'prefixes' => [
        'en-uk' => 'en',
      ],
      'expected_langcode' => FALSE,
    ];
    // Non-existing language.
    $path_prefix_configuration[] = [
      'prefix' => 'it',
      'prefixes' => [
        'it' => 'it',
        'en-uk' => 'en',
      ],
      'expected_langcode' => FALSE,
    ];
    return $path_prefix_configuration;
  }

  /**
   * Tests outbound path processing for neutral languages.
   */
  #[DataProvider('providerNeutralLanguages')]
  public function testNeutralLanguages(string $langcode, ?string $expected_langcode): void {
    if ($expected_langcode) {
      $this->languageManager->expects($this->once())
        ->method('getCurrentLanguage')
        ->willReturn($this->languages['en']);
    }

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          'prefixes' => [
            'de' => 'de',
            'en' => 'en',
          ],
        ],
      ],
    ]);

    $request = Request::create('/foo', 'GET');
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertNull($method->getLangcode($request));

    $language = $this->createStub(LanguageInterface::class);
    $language
      ->method('getId')
      ->willReturn($langcode);
    $cacheability = new BubbleableMetadata();
    $options = [
      'language' => $language,
    ];
    $method->processOutbound('foo', $options, $request, $cacheability);
    $expected_cacheability = new BubbleableMetadata();
    if ($expected_langcode) {
      $this->assertSame($expected_langcode . '/', $options['prefix']);
      $expected_cacheability->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
    }
    else {
      $this->assertFalse(isset($options['prefix']));
    }
    $this->assertEquals($expected_cacheability, $cacheability);
  }

  /**
   * Provides data for the neutral language test.
   *
   * @return array
   *   An array of data for checking path prefix negotiation for neutral
   *   languages.
   */
  public static function providerNeutralLanguages(): array {
    return [
      [LanguageInterface::LANGCODE_NOT_APPLICABLE, NULL],
      [LanguageInterface::LANGCODE_NOT_SPECIFIED, 'en'],
    ];
  }

  /**
   * Tests domain language negotiation and outbound path processing.
   */
  #[DataProvider('providerTestDomain')]
  public function testDomain($http_host, $domains, $expected_langcode): void {
    $this->languageManager
      ->method('getCurrentLanguage')
      ->willReturn($this->languages['en']);

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => LanguageNegotiationUrl::CONFIG_DOMAIN,
          'domains' => $domains,
        ],
      ],
    ]);

    $request = Request::create('', 'GET', [], [], [], ['HTTP_HOST' => $http_host]);
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertEquals($expected_langcode, $method->getLangcode($request));

    $cacheability = new BubbleableMetadata();
    $options = [];
    $this->assertSame('foo', $method->processOutbound('foo', $options, $request, $cacheability));
    $expected_cacheability = new BubbleableMetadata();
    if ($expected_langcode !== FALSE && count($domains) > 1) {
      $expected_cacheability->setCacheMaxAge(
        Cache::PERMANENT)->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL, 'url.site']);
    }
    $this->assertEquals($expected_cacheability, $cacheability);
  }

  /**
   * Provides data for the domain test.
   *
   * @return array
   *   An array of data for checking domain negotiation.
   */
  public static function providerTestDomain(): array {

    $domain_configuration[] = [
      'http_host' => 'example.de',
      'domains' => [
        'de' => 'http://example.de',
      ],
      'expected_langcode' => 'de',
    ];
    // No configuration.
    $domain_configuration[] = [
      'http_host' => 'example.de',
      'domains' => [],
      'expected_langcode' => FALSE,
    ];
    // HTTP host with a port.
    $domain_configuration[] = [
      'http_host' => 'example.de:8080',
      'domains' => [
        'de' => 'http://example.de',
      ],
      'expected_langcode' => 'de',
    ];
    // Domain configuration with https://.
    $domain_configuration[] = [
      'http_host' => 'example.de',
      'domains' => [
        'de' => 'https://example.de',
      ],
      'expected_langcode' => 'de',
    ];
    // Non-matching HTTP host.
    $domain_configuration[] = [
      'http_host' => 'example.com',
      'domains' => [
        'de' => 'http://example.com',
      ],
      'expected_langcode' => 'de',
    ];
    // Testing a non-existing language.
    $domain_configuration[] = [
      'http_host' => 'example.com',
      'domains' => [
        'it' => 'http://example.it',
      ],
      'expected_langcode' => FALSE,
    ];
    // Multiple domain configurations.
    $domain_configuration[] = [
      'http_host' => 'example.com',
      'domains' => [
        'de' => 'http://example.de',
        'en' => 'http://example.com',
      ],
      'expected_langcode' => 'en',
    ];
    return $domain_configuration;
  }

  /**
   * Tests path outbound processing correctly setting relative/absolute paths.
   */
  public function testProcessOutboundOutputsRelativePathsForSameDomain(): void {
    $this->languageManager
      ->method('getCurrentLanguage')
      ->willReturn($this->languages['en']);

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => LanguageNegotiationUrl::CONFIG_DOMAIN,
          'domains' => [
            'de' => 'example.de',
            'en' => 'example.com',
          ],
        ],
      ],
    ]);

    $request = Request::create('', 'GET', [], [], [], ['HTTP_HOST' => 'example.com']);
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);

    // Check relative paths are used when the language
    // is the current language.
    $options = [
      'language' => $this->languages['en'],
    ];
    $method->processOutbound('foo', $options, $request);
    // $options['absolute'] not set or null equals to FALSE.
    $this->assertFalse($options['absolute'] ?? FALSE);

    // Check absolute paths are used when the language
    // is not the current language.
    $options = [
      'language' => $this->languages['de'],
    ];
    $method->processOutbound('foo', $options, $request);
    $this->assertTrue($options['absolute']);

  }

}

// @todo Remove as part of https://www.drupal.org/node/2481833.
namespace Drupal\language\Plugin\LanguageNegotiation;

if (!function_exists('base_path')) {

  /**
   * Returns the base path.
   */
  function base_path(): string {
    return '/';
  }

}
