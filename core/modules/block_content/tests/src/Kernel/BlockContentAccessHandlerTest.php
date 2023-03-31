<?php

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block_content\BlockContentAccessControlHandler;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Access\AccessResultReasonInterface;
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
   * @param bool $isLatest
   *   Whether the block content should be the latest revision when checking
   *   access. If FALSE, multiple revisions will be created, and an older
   *   revision will be loaded before checking access.
   * @param string|null $parent_access
   *   Whether the test user has access to the parent entity, valid values are
   *   class names of classes implementing AccessResultInterface. Set to NULL to
   *   assert parent will not be called.
   * @param string $expected_access
   *   The expected access for the user and block content. Valid values are
   *   class names of classes implementing AccessResultInterface
   * @param string|null $expected_access_message
   *   The expected access message.
   *
   * @covers ::checkAccess
   *
   * @dataProvider providerTestAccess
   *
   * @phpstan-param class-string<\Drupal\Core\Access\AccessResultInterface>|null $parent_access
   * @phpstan-param class-string<\Drupal\Core\Access\AccessResultInterface> $expected_access
   */
  public function testAccess(string $operation, bool $published, bool $reusable, array $permissions, bool $isLatest, ?string $parent_access, string $expected_access, ?string $expected_access_message = NULL) {
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

    if ($parent_access !== NULL) {
      $parent_entity = $this->prophesize(AccessibleInterface::class);
      $expected_parent_result = new ($parent_access)();
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
    $this->assertInstanceOf($expected_access, $result);
    if ($expected_access_message !== NULL) {
      $this->assertInstanceOf(AccessResultReasonInterface::class, $result);
      $this->assertEquals($expected_access_message, $result->getReason());
    }
  }

  /**
   * Data provider for testAccess().
   */
  public function providerTestAccess(): array {
    $cases = [
      'view:published:reusable' => [
        'view',
        TRUE,
        TRUE,
        [],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'view:unpublished:reusable' => [
        'view',
        FALSE,
        TRUE,
        [],
        TRUE,
        NULL,
        AccessResultNeutral::class,
      ],
      'view:unpublished:reusable:admin' => [
        'view',
        FALSE,
        TRUE,
        ['access block library'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'view:unpublished:reusable:per-block-editor:basic' => [
        'view',
        FALSE,
        TRUE,
        ['edit any basic block content'],
        TRUE,
        NULL,
        AccessResultNeutral::class,
      ],
      'view:unpublished:reusable:per-block-editor:square' => [
        'view',
        FALSE,
        TRUE,
        ['access block library', 'edit any basic block content'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'view:published:reusable:admin' => [
        'view',
        TRUE,
        TRUE,
        ['access block library'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'view:published:reusable:per-block-editor:basic' => [
        'view',
        TRUE,
        TRUE,
        ['access block library', 'edit any basic block content'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'view:published:reusable:per-block-editor:square' => [
        'view',
        TRUE,
        TRUE,
        ['access block library', 'edit any square block content'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'view:published:non_reusable' => [
        'view',
        TRUE,
        FALSE,
        [],
        TRUE,
        NULL,
        AccessResultForbidden::class,
      ],
      'view:published:non_reusable:parent_allowed' => [
        'view',
        TRUE,
        FALSE,
        [],
        TRUE,
        AccessResultAllowed::class,
        AccessResultAllowed::class,
      ],
      'view:published:non_reusable:parent_neutral' => [
        'view',
        TRUE,
        FALSE,
        [],
        TRUE,
        AccessResultNeutral::class,
        AccessResultNeutral::class,
      ],
      'view:published:non_reusable:parent_forbidden' => [
        'view',
        TRUE,
        FALSE,
        [],
        TRUE,
        AccessResultForbidden::class,
        AccessResultForbidden::class,
      ],
    ];
    foreach (['update', 'delete'] as $operation) {
      $label = $operation === 'update' ? 'edit' : 'delete';
      $cases += [
        $operation . ':published:reusable' => [
          $operation,
          TRUE,
          TRUE,
          [],
          TRUE,
          NULL,
          AccessResultNeutral::class,
        ],
        $operation . ':unpublished:reusable' => [
          $operation,
          FALSE,
          TRUE,
          [],
          TRUE,
          NULL,
          AccessResultNeutral::class,
        ],
        $operation . ':unpublished:reusable:admin' => [
          $operation,
          FALSE,
          TRUE,
          ['access block library', $label . ' any square block content'],
          TRUE,
          NULL,
          AccessResultAllowed::class,
        ],
        $operation . ':published:reusable:admin' => [
          $operation,
          TRUE,
          TRUE,
          ['access block library', $label . ' any square block content'],
          TRUE,
          NULL,
          AccessResultAllowed::class,
        ],
        $operation . ':published:non_reusable' => [
          $operation,
          TRUE,
          FALSE,
          [],
          TRUE,
          NULL,
          AccessResultForbidden::class,
        ],
        $operation . ':published:non_reusable:parent_allowed' => [
          $operation,
          TRUE,
          FALSE,
          [],
          TRUE,
          AccessResultAllowed::class,
          AccessResultNeutral::class,
        ],
        $operation . ':published:non_reusable:parent_neutral' => [
          $operation,
          TRUE,
          FALSE,
          [],
          TRUE,
          AccessResultNeutral::class,
          AccessResultNeutral::class,
        ],
        $operation . ':published:non_reusable:parent_forbidden' => [
          $operation,
          TRUE,
          FALSE,
          [],
          TRUE,
          AccessResultForbidden::class,
          AccessResultForbidden::class,
        ],
        $operation . ':unpublished:reusable:per-block-editor:basic' => [
          $operation,
          FALSE,
          TRUE,
          ['access block library', 'edit any basic block content'],
          TRUE,
          NULL,
          AccessResultNeutral::class,
        ],
        $operation . ':published:reusable:per-block-editor:basic' => [
          $operation,
          TRUE,
          TRUE,
          ['access block library', 'edit any basic block content'],
          TRUE,
          NULL,
          AccessResultNeutral::class,
        ],
      ];
    }

    $cases += [
      'update:unpublished:reusable:per-block-editor:square' => [
        'update',
        FALSE,
        TRUE,
        ['access block library', 'edit any square block content'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
      'update:published:reusable:per-block-editor:square' => [
        'update',
        TRUE,
        TRUE,
        ['access block library', 'edit any square block content'],
        TRUE,
        NULL,
        AccessResultAllowed::class,
      ],
    ];

    $cases += [
      'delete:unpublished:reusable:per-block-editor:square' => [
        'delete',
        FALSE,
        TRUE,
        ['access block library', 'edit any square block content'],
        TRUE,
        NULL,
        AccessResultNeutral::class,
      ],
      'delete:published:reusable:per-block-editor:square' => [
        'delete',
        TRUE,
        TRUE,
        ['access block library', 'edit any square block content'],
        TRUE,
        NULL,
        AccessResultNeutral::class,
      ],
    ];

    // View all revisions:
    $cases['view all revisions:none'] = [
      'view all revisions',
      TRUE,
      TRUE,
      [],
      TRUE,
      NULL,
      AccessResultNeutral::class,
    ];
    $cases['view all revisions:administer blocks'] = [
      'view all revisions',
      TRUE,
      TRUE,
      ['access block library', 'view any square block content history'],
      TRUE,
      NULL,
      AccessResultAllowed::class,
    ];
    $cases['view all revisions:view bundle'] = [
      'view all revisions',
      TRUE,
      TRUE,
      ['access block library', 'view any square block content history'],
      TRUE,
      NULL,
      AccessResultAllowed::class,
    ];

    // Revert revisions:
    $cases['revert:none:latest'] = [
      'revert',
      TRUE,
      TRUE,
      [],
      TRUE,
      NULL,
      AccessResultForbidden::class,
    ];
    $cases['revert:none:historical'] = [
      'revert',
      TRUE,
      TRUE,
      [],
      FALSE,
      NULL,
      AccessResultNeutral::class,
    ];
    $cases['revert:administer blocks:latest'] = [
      'revert',
      TRUE,
      TRUE,
      ['access block library'],
      TRUE,
      NULL,
      AccessResultForbidden::class,
    ];
    $cases['revert:administer blocks:historical'] = [
      'revert',
      TRUE,
      TRUE,
      ['access block library', 'revert any square block content revisions'],
      FALSE,
      NULL,
      AccessResultAllowed::class,
    ];
    $cases['revert:revert bundle:latest'] = [
      'revert',
      TRUE,
      TRUE,
      ['administer blocks'],
      TRUE,
      NULL,
      AccessResultForbidden::class,
    ];
    $cases['revert:revert bundle:historical'] = [
      'revert',
      TRUE,
      TRUE,
      ['access block library', 'revert any square block content revisions'],
      FALSE,
      NULL,
      AccessResultAllowed::class,
    ];
    $cases['revert:revert bundle:historical:non reusable'] = [
      'revert',
      TRUE,
      FALSE,
      ['revert any square block content revisions'],
      FALSE,
      NULL,
      AccessResultForbidden::class,
      'Block content must be reusable to use `revert` operation',
    ];

    // Delete revisions:
    $cases['delete revision:none:latest'] = [
      'delete revision',
      TRUE,
      TRUE,
      [],
      TRUE,
      NULL,
      AccessResultForbidden::class,
    ];
    $cases['delete revision:none:historical'] = [
      'delete revision',
      TRUE,
      TRUE,
      [],
      FALSE,
      NULL,
      AccessResultNeutral::class,
    ];
    $cases['delete revision:administer blocks:latest'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['administer blocks'],
      TRUE,
      NULL,
      AccessResultForbidden::class,
    ];
    $cases['delete revision:administer blocks:historical'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['access block library', 'delete any square block content revisions'],
      FALSE,
      NULL,
      AccessResultAllowed::class,
    ];
    $cases['delete revision:delete bundle:latest'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['administer blocks'],
      TRUE,
      NULL,
      AccessResultForbidden::class,
    ];
    $cases['delete revision:delete bundle:historical'] = [
      'delete revision',
      TRUE,
      TRUE,
      ['access block library', 'delete any square block content revisions'],
      FALSE,
      NULL,
      AccessResultAllowed::class,
    ];
    $cases['delete revision:delete bundle:historical:non reusable'] = [
      'delete revision',
      TRUE,
      FALSE,
      ['access block library', 'delete any square block content revisions'],
      FALSE,
      NULL,
      AccessResultForbidden::class,
      'Block content must be reusable to use `delete revision` operation',
    ];

    return $cases;
  }

}
