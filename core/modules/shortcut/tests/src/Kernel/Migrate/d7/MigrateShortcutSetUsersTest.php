<?php

namespace Drupal\Tests\shortcut\Kernel\Migrate\d7;

use Drupal\user\Entity\User;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test shortcut_set_users migration.
 *
 * @group shortcut
 */
class MigrateShortcutSetUsersTest extends MigrateDrupal7TestBase {

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
    $this->installSchema('shortcut', ['shortcut_set_users']);
    \Drupal::service('router.builder')->rebuild();
    $this->executeMigration('d7_user_role');
    $this->executeMigration('d7_user');
    $this->executeMigration('d7_shortcut_set');
    $this->executeMigration('menu');
    $this->executeMigration('menu_links');
    $this->executeMigration('d7_shortcut');
    $this->executeMigration('d7_shortcut_set_users');
  }

  /**
   * Test the shortcut set migration.
   */
  public function testShortcutSetUsersMigration() {
    // Check if migrated user has correct migrated shortcut set assigned.
    $account = User::load(2);
    $shortcut_set = shortcut_current_displayed_set($account);
    /** @var \Drupal\shortcut\ShortcutSetInterface $shortcut_set */
    $this->assertIdentical('shortcut_set_2', $shortcut_set->id());
  }

}
