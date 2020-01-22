<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for Layout Builder section dependencies.
 *
 * @group layout_builder
 * @group legacy
 */
class SectionDependenciesUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/section-dependencies.php',
    ];
  }

  /**
   * Tests the upgrade path for Layout Builder section dependencies.
   */
  public function testRunUpdates() {
    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertNotContains('system.menu.myothermenu', $data['dependencies']['config']);
    $this->assertNotContains('layout_builder', $data['dependencies']['module']);
    $this->assertNotContains('layout_test', $data['dependencies']['module']);

    $this->runUpdates();

    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertContains('system.menu.myothermenu', $data['dependencies']['config']);
    $this->assertContains('layout_builder', $data['dependencies']['module']);
    $this->assertContains('layout_test', $data['dependencies']['module']);
  }

}
