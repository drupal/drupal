<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Menu;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\system\MenuAccessControlHandler
 * @group system
 */
class MenuAccessControlHandlerTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * The menu access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->accessControlHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('menu');
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkCreateAccess
   * @dataProvider providerTestAccess
   */
  public function testAccess($permissions, $which_entity, $view_label_access_result, $view_access_result, $update_access_result, $delete_access_result, $create_access_result): void {
    $user = $this->drupalCreateUser($permissions);

    $entity_values = ($which_entity === 'unlocked')
      ? ['locked' => FALSE]
      : ['locked' => TRUE];
    $entity_values['id'] = $entity_values['label'] = 'llama';
    $entity = Menu::create($entity_values);
    $entity->save();

    static::assertEquals($view_label_access_result, $this->accessControlHandler->access($entity, 'view label', $user, TRUE));
    static::assertEquals($view_access_result, $this->accessControlHandler->access($entity, 'view', $user, TRUE));
    static::assertEquals($update_access_result, $this->accessControlHandler->access($entity, 'update', $user, TRUE));
    static::assertEquals($delete_access_result, $this->accessControlHandler->access($entity, 'delete', $user, TRUE));
    static::assertEquals($create_access_result, $this->accessControlHandler->createAccess(NULL, $user, [], TRUE));
  }

  /**
   * Provides test cases for menu access control based on user permissions and menu lock status.
   *
   * @return array
   *   An array of test cases.
   */
  public static function providerTestAccess(): array {
    // RefinableCacheableDependencyTrait::addCacheContexts() only needs the
    // container to perform an assertion, but we can't use the container here,
    // so disable assertions for the purposes of this test.
    $assertions = ini_set('zend.assertions', 0);

    $data = [
      'no permission + unlocked' => [
        [],
        'unlocked',
        AccessResult::allowed(),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required."),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required."),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required.")->addCacheTags(['config:system.menu.llama']),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required."),
      ],
      'no permission + locked' => [
        [],
        'locked',
        AccessResult::allowed(),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required."),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required."),
        AccessResult::forbidden()->addCacheTags(['config:system.menu.llama'])->setReason("The Menu config entity is locked."),
        AccessResult::neutral()->addCacheContexts(['user.permissions'])->setReason("The 'administer menu' permission is required."),
      ],
      'admin + unlocked' => [
        ['administer menu'],
        'unlocked',
        AccessResult::allowed(),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        AccessResult::allowed()->addCacheContexts(['user.permissions'])->addCacheTags(['config:system.menu.llama']),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
      'admin + locked' => [
        ['administer menu'],
        'locked',
        AccessResult::allowed(),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        AccessResult::forbidden()->addCacheTags(['config:system.menu.llama'])->setReason("The Menu config entity is locked."),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
    ];

    if ($assertions !== FALSE) {
      ini_set('zend.assertions', $assertions);
    }

    return $data;
  }

}
