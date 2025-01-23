<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests entity access control handler custom internal cache ID.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityAccessControlHandler
 *
 * @group Entity
 */
class EntityCreateAccessCustomCidTest extends UnitTestCase {

  /**
   * A mock entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected EntityTypeInterface $entityType;

  /**
   * A mock account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * A language code.
   *
   * @var string
   */
  protected string $langcode;

  /**
   * A mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityType = $this->getMockBuilder(EntityTypeInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityType->expects($this->any())
      ->method('id')
      ->willReturn($this->randomMachineName());

    $this->account = $this->getMockBuilder(AccountInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->account->expects($this->any())
      ->method('id')
      ->willReturn(rand());

    $language_ids = array_keys(LanguageManager::getStandardLanguageList());
    $this->langcode = $language_ids[array_rand($language_ids)];

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);
  }

  /**
   * Setup the access cache on the entity handler for testing.
   *
   * @param \Drupal\Core\Entity\EntityAccessControlHandler $handler
   *   The access control handler.
   * @param bool $in_cache
   *   Whether to prefill the handler's access cache.
   * @param string $cid
   *   The cache ID.
   *
   * @return \ReflectionProperty
   *   A reflection of the handler's accessCache property.
   *
   * @throws \ReflectionException
   */
  protected function setUpAccessCache(EntityAccessControlHandler $handler, bool $in_cache, string $cid): \ReflectionProperty {
    $access_cache = new \ReflectionProperty($handler, 'accessCache');
    $access_cache->setAccessible(TRUE);

    $cache = [];
    if ($in_cache) {
      // Prefill the handler's internal static cache.
      $cache = [
        $this->account->id() => [
          $cid => [
            $this->langcode => [
              'create' => AccessResult::allowed(),
            ],
          ],
        ],
      ];
    }
    $access_cache->setValue($handler, $cache);
    return $access_cache;
  }

  /**
   * Tests the entity access control handler caching with context.
   *
   * @param array $context
   *   The context array for the test createAccess() check.
   * @param bool $in_cache
   *   Whether there is already a cached createAccess() check for the cache ID.
   * @param bool $cacheable
   *   If the test createAccess() check should be cacheable.
   *
   * @covers ::buildCreateAccessCid
   * @dataProvider providerTestDefaultCid
   */
  public function testDefaultCid(array $context, bool $in_cache, bool $cacheable): void {
    $bundle = $this->randomMachineName();
    $cid = "create:{$bundle}";
    $context['langcode'] = $this->langcode;

    $handler = new EntityAccessControlHandler($this->entityType);
    $handler->setModuleHandler($this->moduleHandler);

    $access_cache = $this->setUpAccessCache($handler, $in_cache, $cid);
    $cache = $access_cache->getValue($handler);

    // The cached value is AccessResult::allowed() but default result is
    // neutral() so createAccess returns TRUE for a cache hit, FALSE otherwise.
    $should_get_from_cache = $in_cache && $cacheable;
    $this->assertSame($should_get_from_cache, $handler->createAccess($bundle, $this->account, $context));

    $should_add_to_cache = $cacheable && !$in_cache;
    $cache_is_changed = $cache !== $access_cache->getValue($handler);
    $this->assertSame($should_add_to_cache, $cache_is_changed);
  }

  /**
   * Provides test cases for ::testDefaultCid().
   *
   * @return array[]
   *   A list of test cases.
   */
  public static function providerTestDefaultCid(): array {
    return [
      'no context, cached' => [
        'context' => [],
        'in_cache' => TRUE,
        'cacheable' => TRUE,
      ],
      'no context, uncached' => [
        'context' => [],
        'in_cache' => FALSE,
        'cacheable' => TRUE,
      ],
      'one context var, cached' => [
        'context' => [
          'context_var1' => 'val1',
        ],
        'in_cache' => TRUE,
        'cacheable' => FALSE,
      ],
      'one context var, uncached' => [
        'context' => [
          'context_var1' => 'val1',
        ],
        'in_cache' => FALSE,
        'cacheable' => FALSE,
      ],
      'two context vars, cached' => [
        'context' => [
          'context_var1' => 'val1',
          'context_var2' => 'val2',
        ],
        'in_cache' => TRUE,
        'cacheable' => FALSE,
      ],
      'two context vars, uncached' => [
        'context' => [
          'context_var1' => 'val1',
          'context_var2' => 'val2',
        ],
        'in_cache' => FALSE,
        'cacheable' => FALSE,
      ],

    ];
  }

  /**
   * Tests the entity access control handler with a custom static cache ID.
   *
   * @param string $bundle
   *   The machine name of the entity bundle.
   * @param array $context
   *   The context array.
   * @param string $cid
   *   The static cache ID.
   * @param bool $in_cache
   *   Whether there is already a cached createAccess() check for the cache ID.
   *
   * @covers ::buildCreateAccessCid
   * @dataProvider providerTestCustomCid
   */
  public function testCustomCid(string $bundle, array $context, string $cid, bool $in_cache): void {
    $context['langcode'] = $this->langcode;

    // Drupal\Core\Cache is used when merging access results in
    // checkCreateAccess(), and it calls the cache context manager service.
    $cache_context_manager = $this->createMock(CacheContextsManager::class);
    $cache_context_manager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('cache_contexts_manager', $cache_context_manager);

    $handler = new EntityTestAccessControlHandler($this->entityType);
    $handler->setModuleHandler($this->moduleHandler);

    $this->setUpAccessCache($handler, $in_cache, $cid);

    // The prefilled cache is set to AccessResult::allowed(), but the default
    // for EntityTestAccessControlHandler() is neutral(); so createAccess() will
    // return TRUE for a cache hit and FALSE otherwise.
    $this->assertSame($in_cache, $handler->createAccess($bundle, $this->account, $context));
  }

  /**
   * Provides test cases for ::testCustomCid().
   *
   * @return array[]
   *   A list of test cases.
   */
  public static function providerTestCustomCid(): array {
    return [
      'no context var, in cache' => [
        'bundle' => 'bundle_1',
        'context' => [],
        'cid' => 'create:bundle_1',
        'in_cache' => TRUE,
      ],
      'no context var, not in cache' => [
        'bundle' => 'bundle_2',
        'context' => [],
        'cid' => 'create:bundle_2',
        'in_cache' => FALSE,
      ],
      'one context var, in cache' => [
        'bundle' => 'bundle_3',
        'context' => [
          'context_var1' => 'val1',
        ],
        'cid' => 'create:bundle_3:val1',
        'in_cache' => TRUE,
      ],
      'one context var, not in cache' => [
        'bundle' => 'bundle_4',
        'context' => [
          'context_var1' => 'val1',
        ],
        'cid' => 'create:bundle_4:val1',
        'in_cache' => FALSE,
      ],
      'two context vars, in cache' => [
        'bundle' => 'bundle_5',
        'context' => [
          'context_var1' => 'val1',
          'context_var2' => 'val2',
        ],
        'cid' => 'create:bundle_5:val1:val2',
        'in_cache' => TRUE,
      ],
      'two context vars, not in cache' => [
        'bundle' => 'bundle_6',
        'context' => [
          'context_var1' => 'val1',
          'context_var2' => 'val2',
        ],
        'cid' => 'create:bundle_6:val1:val2',
        'in_cache' => FALSE,
      ],
    ];
  }

}
