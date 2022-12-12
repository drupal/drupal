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

    // Create a basic block content type.
    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'A basic block type',
      'description' => "Provides a block type that is basic.",
    ]);
    $block_content_type->save();

    // Create a square block content type.
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
   * Test block content entity access.
   *
   * @param string $operation
   *   The entity operation to test.
   * @param bool $published
   *   Whether the latest revision should be published.
   * @param bool $reusable
   *   Whether the block content should be reusable. Non-reusable blocks are
   *   typically used in Layout Builder.
   * @param array $permissions
   *   Permissions to grant to the test user.
   * @param string|null $parent_access
   *   Whether the test user has access to the parent entity, valid values are
   *   'allowed', 'forbidden', or 'neutral'. Set to NULL to assert parent will
   *   not be called.
   * @param string $expected_access
   *   The expected access for the user and block content. Valid values are
   *   'allowed', 'forbidden', or 'neutral'.
   * @param bool $isLatest
   *   Whether the block content should be the latest revision when checking
   *   access. If FALSE, multiple revisions will be created, and an older
   *   revision will be loaded before checking access.
   *
   * @covers ::checkAccess
   *
   * @dataProvider providerTestAccess
   */
  public function testAccess(string $operation, bool $published, bool $reusable, array $permissions, ?string $parent_access, string $expected_access, bool $isLatest = TRUE) {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $entityStorage */
    $entityStorage = \Drupal::entityTypeManager()->getStorage('block_content');

    $loadRevisionId = NULL;
    if (!$isLatest) {
      // Save a historical revision, then setup for a new revision to be saved.
      $this->blockEntity->save();
      $loadRevisionId = $this->blockEntity->getRevisionId();
      $this->blockEntity = $entityStorage->createRevision($this->blockEntity);
    }

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

    // Reload a previous revision.
    if ($loadRevisionId !== NULL) {
      $this->blockEntity = $entityStorage->loadRevision($loadRevisionId);
    }

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
      'view:unpublished:reusable:per-block-editor:basic' => [
        'view',
        FALSE,
        TRUE,
        ['edit any basic block content'],
        NULL,
        'neutral',
      ],
      'view:unpublished:reusable:per-block-editor:square' => [
        'view',
        FALSE,
        TRUE,
        ['edit any square block content'],
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
      'view:published:reusable:per-block-editor:basic' => [
        'view',
        TRUE,
        TRUE,
        ['edit any basic block content'],
        NULL,
        'allowed',
      ],
      'view:published:reusable:per-block-editor:square' => [
        'view',
        TRUE,
        TRUE,
        ['edit any square block content'],
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
        $operation . ':unpublished:reusable:per-block-editor:basic' => [
          $operation,
          FALSE,
          TRUE,
          ['edit any basic block content'],
          NULL,
          'neutral',
        ],
        $operation . ':published:reusable:per-block-editor:basic' => [
          $operation,
          TRUE,
          TRUE,
          ['edit any basic block content'],
          NULL,
          'neutral',
        ],
      ];
    }

    $cases += [
      'update:unpublished:reusable:per-block-editor:square' => [
        'update',
        FALSE,
        TRUE,
        ['edit any square block content'],
        NULL,
        'allowed',
      ],
      'update:published:reusable:per-block-editor:square' => [
        'update',
        TRUE,
        TRUE,
        ['edit any square block content'],
        NULL,
        'allowed',
      ],
    ];

    $cases += [
      'delete:unpublished:reusable:per-block-editor:square' => [
        'delete',
        FALSE,
        TRUE,
        ['edit any square block content'],
        NULL,
        'neutral',
      ],
      'delete:published:reusable:per-block-editor:square' => [
        'delete',
        TRUE,
        TRUE,
        ['edit any square block content'],
        NULL,
        'neutral',
      ],
    ];

    // View all revisions:
    $cases['view all revisions:none'] = [
      'view all revisions',
      TRUE,
      TRUE,
      [],
      NULL,
      'neutral',
    ];
    $cases['view all revisions:administer blocks'] = [
      'view all revisions',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'allowed',
    ];
    $cases['view all revisions:view bundle'] = [
      'view all revisions',
      TRUE,
      TRUE,
      ['view any square block content history'],
      NULL,
      'allowed',
    ];

    // Revert revisions:
    $cases['revert:none:latest'] = [
      'revert',
      TRUE,
      TRUE,
      [],
      NULL,
      'forbidden',
      TRUE,
    ];
    $cases['revert:none:historical'] = [
      'revert',
      TRUE,
      TRUE,
      [],
      NULL,
      'neutral',
      FALSE,
    ];
    $cases['revert:administer blocks:latest'] = [
      'revert',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'forbidden',
      TRUE,
    ];
    $cases['revert:administer blocks:historical'] = [
      'revert',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'allowed',
      FALSE,
    ];
    $cases['revert:revert bundle:latest'] = [
      'revert',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'forbidden',
      TRUE,
    ];
    $cases['revert:revert bundle:historical'] = [
      'revert',
      TRUE,
      TRUE,
      ['revert any square block content revisions'],
      NULL,
      'allowed',
      FALSE,
    ];

    // Delete revisions:
    $cases['delete revision:none:latest'] = [
      'delete revision',
      TRUE,
      TRUE,
      [],
      NULL,
      'forbidden',
      TRUE,
    ];
    $cases['delete revision:none:historical'] = [
      'delete revision',
      TRUE,
      TRUE,
      [],
      NULL,
      'neutral',
      FALSE,
    ];
    $cases['delete revision:administer blocks:latest'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'forbidden',
      TRUE,
    ];
    $cases['delete revision:administer blocks:historical'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'allowed',
      FALSE,
    ];
    $cases['delete revision:delete bundle:latest'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['administer blocks'],
      NULL,
      'forbidden',
      TRUE,
    ];
    $cases['delete revision:delete bundle:historical'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['delete any square block content revisions'],
      NULL,
      'allowed',
      FALSE,
    ];

    return $cases;
  }

}
