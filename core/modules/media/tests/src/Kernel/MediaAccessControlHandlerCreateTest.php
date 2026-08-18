<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\media\MediaAccessControlHandler;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the media access control handler.
 */
#[CoversClass(MediaAccessControlHandler::class)]
#[Group('media')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class MediaAccessControlHandlerCreateTest extends MediaKernelTestBase {

  use UserCreationTrait;

  /**
   * Tests create access.
   *
   * @param string[] $permissions
   *   User permissions.
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *   Expected result.
   * @param string[] $expected_cache_contexts
   *   Expected cache contexts.
   * @param string[] $expected_cache_tags
   *   Expected cache tags.
   *
   * @legacy-covers ::checkCreateAccess
   */
  #[DataProvider('providerCreateAccess')]
  public function testCreateAccess(array $permissions, AccessResultInterface $expected_result, array $expected_cache_contexts, array $expected_cache_tags): void {
    $user = $this->createUser($permissions);

    /** @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_handler */
    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('media');
    $this->assertAccess($expected_result, $expected_cache_contexts, $expected_cache_tags, $access_handler->createAccess('test', $user, [], TRUE));
  }

  /**
   * Asserts an access result.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $expected_access_result
   *   The expected access result.
   * @param string[] $expected_cache_contexts
   *   Expected contexts.
   * @param string[] $expected_cache_tags
   *   Expected cache tags.
   * @param \Drupal\Core\Access\AccessResultInterface $actual
   *   The actual access result.
   *
   * @internal
   */
  protected function assertAccess(AccessResultInterface $expected_access_result, array $expected_cache_contexts, array $expected_cache_tags, AccessResultInterface $actual): void {
    $this->assertSame($expected_access_result->isAllowed(), $actual->isAllowed());
    $this->assertSame($expected_access_result->isForbidden(), $actual->isForbidden());
    $this->assertSame($expected_access_result->isNeutral(), $actual->isNeutral());

    $actual_cache_contexts = $actual->getCacheContexts();
    sort($expected_cache_contexts);
    sort($actual_cache_contexts);
    $this->assertSame($expected_cache_contexts, $actual_cache_contexts);

    $actual_cache_tags = $actual->getCacheTags();
    sort($expected_cache_tags);
    sort($actual_cache_tags);
    $this->assertSame($expected_cache_tags, $actual_cache_tags);
  }

  /**
   * Data provider for testCreateAccess().
   *
   * @return array
   *   The data sets to test.
   */
  public static function providerCreateAccess(): array {
    $test_data = [];

    // Check create access for a user without permissions.
    $test_data['user, no permissions / create'] = [
      [],
      AccessResult::neutral()->setReason("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'view media' permission.
    $test_data['user, can view media / create'] = [
      [
        'view media',
      ],
      AccessResult::neutral("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'view media' and 'view own
    // unpublished media' permission.
    $test_data['user, can view own unpublished media / create'] = [
      [
        'view media',
        'view own unpublished media',
      ],
      AccessResult::neutral("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'view media', 'view own
    // unpublished media', 'update any media' and 'delete any media' permission.
    $test_data['user, can view own unpublished media and update or delete any media / create'] = [
      [
        'view media',
        'view own unpublished media',
        'update any media',
        'delete any media',
      ],
      AccessResult::neutral("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'view media', 'view own
    // unpublished media', 'update media' and 'delete media' permission.
    $test_data['user, can view own unpublished media and update or delete own media / create'] = [
      [
        'view media',
        'view own unpublished media',
        'update media',
        'delete media',
      ],
      AccessResult::neutral("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'view media', 'view own
    // unpublished media', 'update any media', 'delete any media', 'update
    // media' and 'delete media' permission.
    $test_data['user, can view own unpublished media and update or delete all media / create'] = [
      [
        'view media',
        'view own unpublished media',
        'update any media',
        'delete any media',
        'update media',
        'delete media',
      ],
      AccessResult::neutral("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with all media permissions except 'create
    // media' or 'administer media'.
    $test_data['user, can not create or administer media / create'] = [
      [
        'access media overview',
        'view media',
        'view own unpublished media',
        'update any media',
        'delete any media',
        'update media',
        'delete media',
      ],
      AccessResult::neutral("The following permissions are required: 'administer media' OR 'create media'."),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'create media' permission.
    $test_data['user, can create media / create'] = [
      [
        'create media',
      ],
      AccessResult::allowed(),
      ['user.permissions'],
      [],
    ];

    // Check create access for a user with the 'administer media' permission.
    $test_data['user, can administer media / create'] = [
      [
        'administer media',
      ],
      AccessResult::allowed(),
      ['user.permissions'],
      [],
    ];

    return $test_data;
  }

}
