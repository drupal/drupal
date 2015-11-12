<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserStubTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;

/**
 * Test stub creation for user entities.
 *
 * @group user
 */
class MigrateUserStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests creation of user stubs.
   */
  public function testStub() {
    $this->performStubTest('user');
  }

}
