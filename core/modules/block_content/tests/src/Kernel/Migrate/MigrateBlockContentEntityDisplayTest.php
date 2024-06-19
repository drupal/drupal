<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel\Migrate;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of block content body field display configuration.
 *
 * @group block_content
 */
class MigrateBlockContentEntityDisplayTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content', 'filter', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installConfig(static::$modules);
    $this->executeMigrations([
      'block_content_type',
      'block_content_body_field',
      'block_content_entity_display',
    ]);
  }

  /**
   * Asserts a display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component_id
   *   The ID of the display component.
   *
   * @internal
   */
  protected function assertDisplay(string $id, string $component_id): void {
    $component = EntityViewDisplay::load($id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertSame('hidden', $component['label']);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration(): void {
    $this->assertDisplay('block_content.basic.default', 'body');
  }

}
