<?php

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block_content\BlockContentAccessControlHandler;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the block content entity access handler.
 *
 * @coversDefaultClass \Drupal\block_content\BlockContentAccessControlHandler
 *
 * @group block_content
 */
class BlockContentAccessHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'system',
    'user',
  ];

  /**
   * The BlockContent access controller to test.
   *
   * @var \Drupal\block_content\BlockContentAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * The BlockContent entity used for testing.
   *
   * @var \Drupal\block_content\Entity\BlockContent
   */
  protected $blockEntity;

  /**
   * The test role.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $role;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');

    // Create a block content type.
    $block_content_type = BlockContentType::create([
      'id' => 'square',
      'label' => 'A square block type',
      'description' => "Provides a block type that is square.",
    ]);
    $block_content_type->save();

    $this->blockEntity = BlockContent::create([
      'info' => 'The Block',
      'type' => 'square',
    ]);
    $this->blockEntity->save();

    // Create user 1 test does not have all permissions.
    User::create([
      'name' => 'admin',
    ])->save();

    $this->role = Role::create([
      'id' => 'roly',
      'label' => 'roly poly',
    ]);
    $this->role->save();
    $this->accessControlHandler = new BlockContentAccessControlHandler(\Drupal::entityTypeManager()->getDefinition('block_content'), \Drupal::service('event_dispatcher'));
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess($operation, $published, $reusable, $permissions, $parent_access, $expected_access) {
    $published ? $this->blockEntity->setPublished() : $this->blockEntity->setUnpublished();
    $reusable ? $this->blockEntity->setReusable() : $this->blockEntity->setNonReusable();

    $user = User::create([
      'name' => 'Someone',
      'mail' => 'hi@example.com',
    ]);

    if ($permissions) {
      foreach ($permissions as $permission) {
        $this->role->grantPermission($permission);
      }
      $this->role->save();
    }
    $user->addRole($this->role->id());
    $user->save();

    if ($parent_access) {
      $parent_entity = $this->prophesize(AccessibleInterface::class);
      $expected_parent_result = NULL;
      switch ($parent_access) {
        case 'allowed':
          $expected_parent_result = AccessResult::allowed();
          break;

        case 'neutral':
          $expected_parent_result = AccessResult::neutral();
          break;

        case 'forbidden':
          $expected_parent_result = AccessResult::forbidden();
          break;
      }
      $parent_entity->access($operation, $user, TRUE)
        ->willReturn($expected_parent_result)
        ->shouldBeCalled();

      $this->blockEntity->setAccessDependency($parent_entity->reveal());

    }
    $this->blockEntity->save();

    $result = $this->accessControlHandler->access($this->blockEntity, $operation, $user, TRUE);
    switch ($expected_access) {
      case 'allowed':
        $this->assertTrue($result->isAllowed());
        break;

      case 'forbidden':
        $this->assertTrue($result->isForbidden());
        break;

      case  'neutral':
        $this->assertTrue($result->isNeutral());
        break;

      default:
        $this->fail('Unexpected access type');
    }
  }

  /**
   * Data provider for testAccess().
   */
  public function providerTestAccess() {
    $cases = [
      'view:published:reusable' => [
        'view',
        TRUE,
        TRUE,
        [],
        NULL,
        'allowed',
      ],
      'view:unpublished:reusable' => [
        'view',
        FALSE,
        TRUE,
        [],
        NULL,
        'neutral',
      ],
      'view:unpublished:reusable:admin' => [
        'view',
        FALSE,
        TRUE,
        ['administer blocks'],
        NULL,
        'allowed',
      ],
      'view:published:reusable:admin' => [
        'view',
        TRUE,
        TRUE,
        ['administer blocks'],
        NULL,
        'allowed',
      ],
      'view:published:non_reusable' => [
        'view',
        TRUE,
        FALSE,
        [],
        NULL,
        'forbidden',
      ],
      'view:published:non_reusable:parent_allowed' => [
        'view',
        TRUE,
        FALSE,
        [],
        'allowed',
        'allowed',
      ],
      'view:published:non_reusable:parent_neutral' => [
        'view',
        TRUE,
        FALSE,
        [],
        'neutral',
        'neutral',
      ],
      'view:published:non_reusable:parent_forbidden' => [
        'view',
        TRUE,
        FALSE,
        [],
        'forbidden',
        'forbidden',
      ],
    ];
    foreach (['update', 'delete'] as $operation) {
      $cases += [
        $operation . ':published:reusable' => [
          $operation,
          TRUE,
          TRUE,
          [],
          NULL,
          'neutral',
        ],
        $operation . ':unpublished:reusable' => [
          $operation,
          FALSE,
          TRUE,
          [],
          NULL,
          'neutral',
        ],
        $operation . ':unpublished:reusable:admin' => [
          $operation,
          FALSE,
          TRUE,
          ['administer blocks'],
          NULL,
          'allowed',
        ],
        $operation . ':published:reusable:admin' => [
          $operation,
          TRUE,
          TRUE,
          ['administer blocks'],
          NULL,
          'allowed',
        ],
        $operation . ':published:non_reusable' => [
          $operation,
          TRUE,
          FALSE,
          [],
          NULL,
          'forbidden',
        ],
        $operation . ':published:non_reusable:parent_allowed' => [
          $operation,
          TRUE,
          FALSE,
          [],
          'allowed',
          'neutral',
        ],
        $operation . ':published:non_reusable:parent_neutral' => [
          $operation,
          TRUE,
          FALSE,
          [],
          'neutral',
          'neutral',
        ],
        $operation . ':published:non_reusable:parent_forbidden' => [
          $operation,
          TRUE,
          FALSE,
          [],
          'forbidden',
          'forbidden',
        ],
      ];
    }
    return $cases;
  }

}
