<?php

declare(strict_types = 1);

namespace Drupal\Tests\Core\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\EntityTestAccessControlHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests entity access control handler custom internal static cache ID.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityAccessControlHandler
 *
 * @group Entity
 */
class EntityCreateAccessCustomStaticCidTest extends TestCase {

  /**
   * Tests the entity access control handler custom internal static cache ID.
   *
   * @param int $account_id
   *   The user account ID.
   * @param array $context
   *   The context array.
   * @param string $cid
   *   The static cache ID.
   * @param bool $is_access_allowed
   *   If the access is allowed boolean.
   *
   * @covers ::buildCreateAccessCid
   * @dataProvider providerTestCustomCid
   */
  public function testCustomCid(int $account_id, array $context, string $cid, bool $is_access_allowed): void {
    $entity_type = $this->getMockBuilder(EntityTypeInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $handler = new EntityTestAccessControlHandler($entity_type);

    // Make \Drupal\Core\Entity\EntityAccessControlHandler::$accessCache
    // publicly accessible for the purpose of this test.
    $access_cache = new \ReflectionProperty($handler, 'accessCache');
    $access_cache->setAccessible(TRUE);

    $account = $this->getMockBuilder(AccountInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $account->expects($this->any())
      ->method('id')
      ->willReturn($account_id);

    // Prefill the internal statical cache.
    $access_cache->setValue($handler, [
      $account_id => [
        $cid => [
          $context['langcode'] => [
            'create' => AccessResult::allowedIf($is_access_allowed),
          ],
        ],
      ],
    ]);

    // As the internal static cache is already filled, we expect to retrieve the
    // create access result from cache.
    $this->assertSame($is_access_allowed, $handler->createAccess('some_bundle', $account, $context));
  }

  /**
   * Provides test cases for ::testCustomCid().
   *
   * @return array[]
   *   A list of test cases.
   */
  public function providerTestCustomCid(): array {
    $language_ids = array_keys(LanguageManager::getStandardLanguageList());
    return [
      'one context var' => [
        rand(),
        [
          'entity_type_id' => 'entity_test',
          'langcode' => $language_ids[array_rand($language_ids)],
          'context_var1' => 'val1',
        ],
        'create:some_bundle:val1',
        TRUE,
      ],
      'two context vars' => [
        rand(),
        [
          'entity_type_id' => 'entity_test',
          'langcode' => $language_ids[array_rand($language_ids)],
          'context_var1' => 'val1',
          'context_var2' => 'val2',
        ],
        'create:some_bundle:val1:val2',
        FALSE,
      ],
    ];
  }

}
