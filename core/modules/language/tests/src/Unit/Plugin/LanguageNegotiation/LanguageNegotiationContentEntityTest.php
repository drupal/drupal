<?php

namespace Drupal\Tests\language\Unit\Plugin\LanguageNegotiation;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity;

/**
 * Tests the LanguageNegotiationContentEntity plugin class.
 *
 * @group language
 * @coversDefaultClass \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity
 * @see \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity
 */
class LanguageNegotiationContentEntityTest extends UnitTestCase {

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
   * A mock object implementing the AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->createMock(LanguageInterface::class);
    $language_de->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('de'));
    $language_en = $this->createMock(LanguageInterface::class);
    $language_en->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $languages = [
      'de' => $language_de,
      'en' => $language_en,
    ];
    $this->languages = $languages;

    $language_manager = $this->getMockBuilder(ConfigurableLanguageManagerInterface::class)
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue($languages));
    $this->languageManager = $language_manager;

    $this->user = $this->getMockBuilder(AccountInterface::class)
      ->getMock();

    $cache_contexts_manager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getLangcode
   */
  public function testGetLangcode() {
    $entityTypeManagerMock = $this->getMockBuilder(EntityTypeManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $languageNegotiationContentEntity = new LanguageNegotiationContentEntity($entityTypeManagerMock);
    $languageNegotiationContentEntity->setLanguageManager($this->languageManager);

    // Case 1: NULL request object argument.
    $this->assertSame(NULL, $languageNegotiationContentEntity->getLangcode());

    // Case 2: A request object is available, but the languageManager is not
    // set.
    $request = Request::create('/foo', 'GET');
    $this->assertSame(NULL, $languageNegotiationContentEntity->getLangcode($request));

    // Case 3: A request object is available, but static::QUERY_PARAMETER is
    // set to a non-enabled language.
    $request = Request::create('/foo', 'GET',
      [LanguageNegotiationContentEntity::QUERY_PARAMETER => 'it']);
    $this->assertSame(NULL, $languageNegotiationContentEntity->getLangcode($request));

    // Case 4: A request object is available and static::QUERY_PARAMETER is
    // set to an enabled language.
    $request = Request::create('/foo', 'GET',
      [LanguageNegotiationContentEntity::QUERY_PARAMETER => 'de']);
    $this->assertSame('de', $languageNegotiationContentEntity->getLangcode($request));

  }

}
