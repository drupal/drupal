<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the upgrade path for Layout Builder permissions.
 *
 * @see layout_builder_post_update_update_permissions()
 *
 * @group layout_builder
 * @group legacy
 */
class LayoutBuilderPermissionsUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-permissions.php',
    ];
  }

  /**
   * Tests the upgrade path for Layout Builder permissions.
   */
  public function testRunUpdates() {
    $this->assertFalse(Role::load(Role::AUTHENTICATED_ID)->hasPermission('create and edit custom blocks'));

    $this->runUpdates();

    $this->assertTrue(Role::load(Role::AUTHENTICATED_ID)->hasPermission('create and edit custom blocks'));
  }

}
