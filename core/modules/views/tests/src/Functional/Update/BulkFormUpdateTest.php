<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests Views image style dependencies update.
 *
 * @group views
 */
class BulkFormUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/legacy-bulk-form-update.php'
    ];
  }

  /**
   * Tests the updating of dependencies for Views using the bulk_form plugin.
   */
  public function testBulkFormDependencies() {
    $module_dependencies = View::load('legacy_bulk_form')->getDependencies()['module'];

    $this->assertTrue(in_array('system', $module_dependencies));

    $this->runUpdates();

    $module_dependencies = View::load('legacy_bulk_form')->getDependencies()['module'];

    $this->assertFalse(in_array('system', $module_dependencies));
  }

}
