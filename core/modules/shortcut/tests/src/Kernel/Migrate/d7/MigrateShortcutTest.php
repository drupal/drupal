<?php

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
  public static $modules = array(
    'link',
    'field',
    'shortcut',
    'menu_link_content',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('shortcut');
    $this->installEntitySchema('menu_link_content');
    \Drupal::service('router.builder')->rebuild();
    $this->executeMigration('d7_shortcut_set');
    $this->executeMigration('menu');
    $this->executeMigration('menu_links');
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
   */
  protected function assertEntity($id, $title, $weight, $url) {
    $shortcut = Shortcut::load($id);
    $this->assertTrue($shortcut instanceof ShortcutInterface);
    /** @var \Drupal\shortcut\ShortcutInterface $shortcut */
    $this->assertIdentical($title, $shortcut->getTitle());
    $this->assertIdentical($weight, $shortcut->getWeight());
    $this->assertIdentical($url, $shortcut->getUrl()->toString());
  }

  /**
   * Test the shortcut migration.
   */
  public function testShortcutMigration() {
    // Check if the 4 shortcuts were migrated correctly.
    $this->assertEntity(1, 'Add content', '-20', '/node/add');
    $this->assertEntity(2, 'Find content', '-19', '/admin/content');
    $this->assertEntity(3, 'Help', '-49', '/admin/help');
    $this->assertEntity(4, 'People', '-50', '/admin/people');
  }

}
