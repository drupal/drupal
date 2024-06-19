<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel\Migrate\d7;

use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\ShortcutInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test shortcut menu links migration to Shortcut entities.
 *
 * @group shortcut
 */
class MigrateShortcutTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'link',
    'field',
    'shortcut',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('shortcut');
    $this->installEntitySchema('menu_link_content');
    $this->executeMigration('d7_shortcut_set');
    $this->executeMigration('d7_menu');
    $this->executeMigration('d7_shortcut');
  }

  /**
   * Asserts various aspects of a shortcut entity.
   *
   * @param int $id
   *   The shortcut ID.
   * @param string $title
   *   The expected title of the shortcut.
   * @param int $weight
   *   The expected weight of the shortcut.
   * @param string $url
   *   The expected URL of the shortcut.
   *
   * @internal
   */
  protected function assertEntity(int $id, string $title, int $weight, string $url): void {
    $shortcut = Shortcut::load($id);
    $this->assertInstanceOf(ShortcutInterface::class, $shortcut);
    /** @var \Drupal\shortcut\ShortcutInterface $shortcut */
    $this->assertSame($title, $shortcut->getTitle());
    $this->assertSame($weight, (int) $shortcut->getWeight());
    $this->assertSame($url, $shortcut->getUrl()->toString());
  }

  /**
   * Tests the shortcut migration.
   */
  public function testShortcutMigration(): void {
    // Check if the 4 shortcuts were migrated correctly.
    $this->assertEntity(1, 'Add content', -20, '/node/add');
    $this->assertEntity(2, 'Find content', -19, '/admin/content');
    $this->assertEntity(3, 'Help', -49, '/admin/help');
    $this->assertEntity(4, 'People', -50, '/admin/people');
  }

}
