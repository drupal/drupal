<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\media\Entity\Media;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the media access control handler.
 *
 * @group media
 *
 * @coversDefaultClass \Drupal\media\MediaAccessControlHandler
 */
class MediaAccessControlHandlerTest extends MediaKernelTestBase {

  use UserCreationTrait;

  /**
   * Tests the media access control handler.
   *
   * @param string[] $permissions
   *   The permissions that the user should be given.
   * @param array $entity_values
   *   Initial values from which to create the media entity.
   * @param string $operation
   *   The operation, one of 'view', 'update' or 'delete'.
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *   Expected result.
   * @param string[] $expected_cache_contexts
   *   Expected cache contexts.
   * @param string[] $expected_cache_tags
   *   Expected cache tags.
   *
   * @covers ::checkAccess
   * @dataProvider providerAccess
   */
  public function testAccess(array $permissions, array $entity_values, $operation, AccessResultInterface $expected_result, array $expected_cache_contexts, array $expected_cache_tags) {
    // Set a fixed ID so the type specific permissions match.
    $media_type = $this->createMediaType('test', [
      'id' => 'test',
    ]);

    $user = $this->createUser($permissions);

    $entity_values += [
      'status' => FALSE,
      'uid' => $user->id(),
      'bundle' => $media_type->id(),
    ];

    $entity = Media::create($entity_values);
    $entity->save();
    /** @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_handler */
    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('media');
    $this->assertAccess($expected_result, $expected_cache_contexts, $expected_cache_tags, $access_handler->access($entity, $operation, $user, TRUE));
  }

  /**
   * @param string[] $permissions
   *   User permissions.
   * @param \Drupal\Core\Access\AccessResultInterface $expected_result
   *   Expected result.
   * @param string[] $expected_cache_contexts
   *   Expected cache contexts.
   * @param string[] $expected_cache_tags
   *   Expected cache tags.
   *
   * @covers ::checkCreateAccess
   * @dataProvider providerCreateAccess
   */
  public function testCreateAccess(array $permissions, AccessResultInterface $expected_result, array $expected_cache_contexts, array $expected_cache_tags) {
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
   */
  protected function assertAccess(AccessResultInterface $expected_access_result, array $expected_cache_contexts, array $expected_cache_tags, AccessResultInterface $actual) {
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
   * Data provider for testAccess().
   *
   * @return array
   *   The data sets to test.
   */
  public function providerAccess() {
    $test_data = [];

    // Check published / unpublished media access for a user owning the media
    // item without permissions.
    $test_data['owner, no permissions / published / view'] = [
      [],
      ['status' => TRUE],
      'view',
      AccessResult::neutral(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['owner, no permissions / published / update'] = [
      [],
      ['status' => TRUE],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, no permissions / published / delete'] = [
      [],
      ['status' => TRUE],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, no permissions / unpublished / view'] = [
      [],
      [],
      'view',
      AccessResult::neutral(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['owner, no permissions / unpublished / update'] = [
      [],
      [],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, no permissions / unpublished / delete'] = [
      [],
      [],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];

    // Check published / unpublished media access for a user not owning the
    // media item without permissions.
    $test_data['not owner, no permissions / published / view'] = [
      [],
      ['uid' => 0, 'status' => TRUE],
      'view',
      AccessResult::neutral(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['not owner, no permissions / published / update'] = [
      [],
      ['uid' => 0, 'status' => TRUE],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, no permissions / published / delete'] = [
      [],
      ['uid' => 0, 'status' => TRUE],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, no permissions / unpublished / view'] = [
      [],
      ['uid' => 0],
      'view',
      AccessResult::neutral(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['not owner, no permissions / unpublished / update'] = [
      [],
      ['uid' => 0],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, no permissions / unpublished / delete'] = [
      [],
      ['uid' => 0],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];

    // Check published / unpublished media access for a user owning the media
    // item with only the 'view media' permission.
    $test_data['owner, can view media / published / view'] = [
      ['view media'],
      ['status' => TRUE],
      'view',
      AccessResult::allowed(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['owner, can view media / published / update'] = [
      ['view media'],
      ['status' => TRUE],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, can view media / published / delete'] = [
      ['view media'],
      ['status' => TRUE],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, can view media / unpublished / view'] = [
      ['view media'],
      [],
      'view',
      AccessResult::neutral(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['owner, can view media / unpublished / update'] = [
      ['view media'],
      [],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, can view media / unpublished / delete'] = [
      ['view media'],
      [],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];

    // Check published / unpublished media access for a user not owning the
    // media item with only the 'view media' permission.
    $test_data['not owner, can view media / published / view'] = [
      ['view media'],
      ['uid' => 0, 'status' => TRUE],
      'view',
      AccessResult::allowed(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['not owner, can view media / published / update'] = [
      ['view media'],
      ['uid' => 0, 'status' => TRUE],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, can view media / published / delete'] = [
      ['view media'],
      ['uid' => 0, 'status' => TRUE],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, can view media / unpublished / view'] = [
      ['view media'],
      ['uid' => 0],
      'view',
      AccessResult::neutral(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['not owner, can view media / unpublished / update'] = [
      ['view media'],
      ['uid' => 0],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, can view media / unpublished / delete'] = [
      ['view media'],
      ['uid' => 0],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];

    // Check published / unpublished media access for a user owning the media
    // item with the 'view media' and 'view own unpublished' permission.
    $test_data['owner, can view own unpublished media / published / view'] = [
      ['view media', 'view own unpublished media'],
      ['status' => TRUE],
      'view',
      AccessResult::allowed(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['owner, can view own unpublished media / published / update'] = [
      ['view media', 'view own unpublished media'],
      ['status' => TRUE],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, can view own unpublished media / published / delete'] = [
      ['view media', 'view own unpublished media'],
      ['status' => TRUE],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, can view own unpublished media / unpublished / view'] = [
      ['view media', 'view own unpublished media'],
      [],
      'view',
      AccessResult::allowed(),
      ['user.permissions', 'user'],
      ['media:1'],
    ];
    $test_data['owner, can view own unpublished media / unpublished / update'] = [
      ['view media', 'view own unpublished media'],
      [],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['owner, can view own unpublished media / unpublished / delete'] = [
      ['view media', 'view own unpublished media'],
      [],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];

    // Check published / unpublished media access for a user not owning the
    // media item with the 'view media' and 'view own unpublished' permission.
    $test_data['not owner, can view own unpublished media / published / view'] = [
      ['view media', 'view own unpublished media'],
      ['uid' => 0, 'status' => TRUE],
      'view',
      AccessResult::allowed(),
      ['user.permissions'],
      ['media:1'],
    ];
    $test_data['not owner, can view own unpublished media / published / update'] = [
      ['view media', 'view own unpublished media'],
      ['uid' => 0, 'status' => TRUE],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, can view own unpublished media / published / delete'] = [
      ['view media', 'view own unpublished media'],
      ['uid' => 0, 'status' => TRUE],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, can view own unpublished media / unpublished / view'] = [
      ['view media', 'view own unpublished media'],
      ['uid' => 0],
      'view',
      AccessResult::neutral(),
      ['user.permissions', 'user'],
      ['media:1'],
    ];
    $test_data['not owner, can view own unpublished media / unpublished / update'] = [
      ['view media', 'view own unpublished media'],
      ['uid' => 0],
      'update',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];
    $test_data['not owner, can view own unpublished media / unpublished / delete'] = [
      ['view media', 'view own unpublished media'],
      ['uid' => 0],
      'delete',
      AccessResult::neutral(),
      ['user.permissions'],
      [],
    ];

    return $test_data;
  }

  /**
   * Data provider for testCreateAccess().
   *
   * @return array
   *   The data sets to test.
   */
  public function providerCreateAccess() {
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
