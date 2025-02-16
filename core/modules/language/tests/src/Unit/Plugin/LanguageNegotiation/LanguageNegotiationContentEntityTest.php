<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Unit\Plugin\LanguageNegotiation;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\Routing\Route;

/**
 * Tests the LanguageNegotiationContentEntity plugin class.
 *
 * @group language
 * @coversDefaultClass \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity
 * @see \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity
 */
class LanguageNegotiationContentEntityTest extends LanguageNegotiationTestBase {

  /**
   * An array of mock LanguageInterface objects.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected array $languages;

  /**
   * A mock LanguageManager object.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function getPluginClass(): string {
    return LanguageNegotiationContentEntity::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->createMock(LanguageInterface::class);
    $language_de->expects($this->any())
      ->method('getId')
      ->willReturn('de');
    $language_de->expects($this->any())
      ->method('getName')
      ->willReturn('German');
    $language_en = $this->createMock(LanguageInterface::class);
    $language_en->expects($this->any())
      ->method('getId')
      ->willReturn('en');
    $language_en->expects($this->any())
      ->method('getName')
      ->willReturn('English');
    $this->languages = [
      'de' => $language_de,
      'en' => $language_en,
    ];

    $language_manager = $this->createMock(ConfigurableLanguageManagerInterface::class);
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->willReturn($this->languages);
    $language_manager->expects($this->any())
      ->method('getNativeLanguages')
      ->willReturn($this->languages);
    $this->languageManager = $language_manager;

    $container = new ContainerBuilder();

    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container->set('cache_contexts_manager', $cache_contexts_manager);

    $entityTypeManager = $this->createMock(EntityTypeManager::class);
    $container->set('entity_type.manager', $entityTypeManager);

    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getLangcode
   */
  public function testGetLangcode(): void {
    $languageNegotiationContentEntity = $this->createLanguageNegotiationPlugin();

    // Case 1: Empty request.
    $this->assertEquals(NULL, $languageNegotiationContentEntity->getLangcode());

    // Case 2: A request is available, but the languageManager is not set and
    // the static::QUERY_PARAMETER is not provided as a named parameter.
    $request = Request::create('/de/foo', 'GET');
    $request->query = new InputBag();
    $this->assertEquals(NULL, $languageNegotiationContentEntity->getLangcode($request));

    // Case 3: A request is available, the languageManager is set, but the
    // static::QUERY_PARAMETER is not provided as a named parameter.
    $languageNegotiationContentEntity->setLanguageManager($this->languageManager);
    $this->assertEquals(NULL, $languageNegotiationContentEntity->getLangcode($request));

    // Case 4: A request is available, the languageManager is set and the
    // static::QUERY_PARAMETER is provided as a named parameter.
    $expectedLangcode = 'de';
    $request->query->set(LanguageNegotiationContentEntity::QUERY_PARAMETER, $expectedLangcode);
    $this->assertEquals($expectedLangcode, $languageNegotiationContentEntity->getLangcode($request));

    // Case 5: A request is available, the languageManager is set and the
    // static::QUERY_PARAMETER is provided as a named parameter with a given
    // langcode that is not one of the system supported ones.
    $unknownLangcode = 'xx';
    $request->query->set(LanguageNegotiationContentEntity::QUERY_PARAMETER, $unknownLangcode);
    $this->assertNull($languageNegotiationContentEntity->getLangcode($request));
  }

  /**
   * @covers ::processOutbound
   */
  public function testProcessOutbound(): void {

    // Case 1: Not all processing conditions are met.
    $languageNegotiationContentEntityMock = $this->createPartialMock($this->getPluginClass(),
      ['hasLowerLanguageNegotiationWeight', 'meetsContentEntityRoutesCondition']);
    $languageNegotiationContentEntityMock->expects($this->exactly(2))
      ->method('hasLowerLanguageNegotiationWeight')
      ->willReturnOnConsecutiveCalls(
        FALSE,
        TRUE
      );
    $languageNegotiationContentEntityMock->expects($this->once())
      ->method('meetsContentEntityRoutesCondition')
      ->willReturnOnConsecutiveCalls(
        FALSE
      );
    $options = [];
    $path = $this->randomMachineName();

    // Case 1a: Empty request.
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path));
    $request = Request::create('/foo', 'GET');
    $request->server = new ServerBag();
    // Case 1b: Missing the route key in $options.
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request));
    $options = ['route' => $this->createMock(Route::class)];
    // Case 1c: hasLowerLanguageNegotiationWeight() returns FALSE.
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request));
    // Case 1d: meetsContentEntityRoutesCondition() returns FALSE.
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request));

    // Case 2: Cannot figure out the langcode.
    $languageNegotiationContentEntityMock = $this->createPartialMock($this->getPluginClass(),
      ['hasLowerLanguageNegotiationWeight', 'meetsContentEntityRoutesCondition', 'getLangcode']);
    $languageNegotiationContentEntityMock->expects($this->any())
      ->method('hasLowerLanguageNegotiationWeight')
      ->willReturn(TRUE);
    $languageNegotiationContentEntityMock->expects($this->any())
      ->method('meetsContentEntityRoutesCondition')
      ->willReturn(TRUE);
    $languageNegotiationContentEntityMock->expects($this->exactly(2))
      ->method('getLangcode')
      ->willReturnOnConsecutiveCalls(
        NULL,
        'de'
      );
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request));

    // Case 3: Can figure out the langcode.
    // Case 3a: via $options['language'].
    $options['language'] = $this->languages['en'];
    $options['query'] = NULL;
    $bubbleableMetadataMock = $this->createMock(BubbleableMetadata::class);
    $bubbleableMetadataMock->expects($this->exactly(3))
      ->method('addCacheContexts')
      ->with(['url.query_args:' . LanguageNegotiationContentEntity::QUERY_PARAMETER]);
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request, $bubbleableMetadataMock));
    $this->assertFalse(isset($options['language']));
    $this->assertTrue(isset($options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER]));
    $this->assertEquals('en', $options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER]);

    // Case 3a1: via $options['language'] with an additional
    // $options['query'][static::QUERY_PARAMETER].
    $options['language'] = $this->languages['en'];
    $options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER] = 'xx';
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request, $bubbleableMetadataMock));
    $this->assertFalse(isset($options['language']));
    $this->assertEquals('xx', $options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER]);

    // Case 3b: via getLangcode().
    unset($options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER]);
    $this->assertEquals($path, $languageNegotiationContentEntityMock->processOutbound($path, $options, $request, $bubbleableMetadataMock));
    $this->assertEquals('de', $options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER]);
  }

  /**
   * @covers ::getLanguageSwitchLinks
   */
  public function testGetLanguageSwitchLinks(): void {
    $languageNegotiationContentEntity = $this->createLanguageNegotiationPlugin();
    $languageNegotiationContentEntity->setLanguageManager($this->languageManager);

    $request = Request::create('/foo', 'GET', ['param1' => 'xyz']);
    $url = Url::fromUri('base:' . $this->randomMachineName());

    $expectedLanguageSwitchLinksArray = [
      'de' => [
        'url' => $url,
        'title' => $this->languages['de']->getName(),
        'attributes' => ['class' => ['language-link']],
        'query' => [
          LanguageNegotiationContentEntity::QUERY_PARAMETER => 'de',
          'param1' => 'xyz',
        ],
      ],
      'en' => [
        'url' => $url,
        'title' => $this->languages['en']->getName(),
        'attributes' => ['class' => ['language-link']],
        'query' => [
          LanguageNegotiationContentEntity::QUERY_PARAMETER => 'en',
          'param1' => 'xyz',
        ],
      ],
    ];
    $providedLanguageSwitchLinksArray = $languageNegotiationContentEntity->getLanguageSwitchLinks($request, $this->randomMachineName(), $url);
    $this->assertEquals(
      $expectedLanguageSwitchLinksArray,
      $providedLanguageSwitchLinksArray
    );
  }

}
