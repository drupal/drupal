<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests Views image style dependencies update.
 *
 * @group views
 */
class ImageStyleDependencyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal8.views-image-style-dependency-2649914.php',
    ];
  }

  /**
   * Tests the updating of views dependencies to image styles.
   */
  public function testUpdateImageStyleDependencies() {
    $config_dependencies = View::load('foo')->getDependencies()['config'];

    // Checks that 'thumbnail' image style is not a dependency of view 'foo'.
    $this->assertFalse(in_array('image.style.thumbnail', $config_dependencies));

    // We test the case the the field formatter image style doesn't exist.
    // Checks that 'nonexistent' image style is not a dependency of view 'foo'.
    $this->assertFalse(in_array('image.style.nonexistent', $config_dependencies));

    // Run updates.
    $this->runUpdates();

    $config_dependencies = View::load('foo')->getDependencies()['config'];

    // Checks that 'thumbnail' image style is a dependency of view 'foo'.
    $this->assertTrue(in_array('image.style.thumbnail', $config_dependencies));

    // The 'nonexistent' style doesn't exist, thus is not a dependency. Checks
    // that 'nonexistent' image style is a not dependency of view 'foo'.
    $this->assertFalse(in_array('image.style.nonexistent', $config_dependencies));
  }

}
