<?php

namespace Drupal\Tests\block_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests the permissions of content blocks.
 *
 * @coversDefaultClass \Drupal\block_content\BlockContentPermissions
 *
 * @group block_content
 */
class BlockContentPermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'block_content_test',
    'system',
    'user',
  ];

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');

    $this->permissionHandler = $this->container->get('user.permissions');
  }

  /**
   * @covers ::blockTypePermissions
   */
  public function testDynamicPermissions() {
    $permissions = $this->permissionHandler->getPermissions();
    $this->assertArrayNotHasKey('edit any basic block content', $permissions, 'The per-block-type permission does not exist.');
    $this->assertArrayNotHasKey('edit any square block content', $permissions, 'The per-block-type permission does not exist.');

    // Create a basic block content type.
    BlockContentType::create([
      'id'          => 'basic',
      'label'       => 'A basic block type',
      'description' => 'Provides a basic block type',
    ])->save();

    // Create a square block content type.
    BlockContentType::create([
      'id'          => 'square',
      'label'       => 'A square block type',
      'description' => 'Provides a block type that is square',
    ])->save();

    $permissions = $this->permissionHandler->getPermissions();

    // Assert the basic permission has been created.
    $this->assertArrayHasKey('edit any basic block content', $permissions, 'The per-block-type permission exists.');
    $this->assertEquals(
      '<em class="placeholder">A basic block type</em>: Edit content block',
      $permissions['edit any basic block content']['title']->render()
    );

    // Assert the square permission has been created.
    $this->assertArrayHasKey('edit any square block content', $permissions, 'The per-block-type permission exists.');
    $this->assertEquals(
      '<em class="placeholder">A square block type</em>: Edit content block',
      $permissions['edit any square block content']['title']->render()
    );
  }

}
