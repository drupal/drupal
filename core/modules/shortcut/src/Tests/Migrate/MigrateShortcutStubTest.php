<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\Migrate\MigrateShortcutStubTest.
 */

namespace Drupal\shortcut\Tests\Migrate;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;

/**
 * Test stub creation for shortcut entities.
 *
 * @group shortcut
 */
class MigrateShortcutStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['shortcut', 'link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('shortcut');
    // Make sure the 'default' shortcut_set is installed.
    $this->installConfig(['shortcut']);
  }

  /**
   * Tests creation of shortcut stubs.
   */
  public function testStub() {
    $this->performStubTest('shortcut');
  }

}
