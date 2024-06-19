<?php

declare(strict_types=1);

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
    $this->installSchema('shortcut', ['shortcut_set_users']);
    $this->migrateUsers(FALSE);
    $this->executeMigration('d7_shortcut_set');
    $this->executeMigration('d7_menu');
    $this->executeMigration('d7_shortcut');
    $this->executeMigration('d7_shortcut_set_users');
  }

  /**
   * Tests the shortcut set migration.
   */
  public function testShortcutSetUsersMigration(): void {
    // Check if migrated user has correct migrated shortcut set assigned.
    $account = User::load(2);
    $shortcut_set_storage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
    $shortcut_set = $shortcut_set_storage->getDisplayedToUser($account);
    /** @var \Drupal\shortcut\ShortcutSetInterface $shortcut_set */
    $this->assertSame('shortcut-set-2', $shortcut_set->id());
  }

}
