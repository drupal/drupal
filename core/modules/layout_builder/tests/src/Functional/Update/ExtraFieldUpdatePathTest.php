<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for Layout Builder extra fields.
 *
 * @group layout_builder
 * @group legacy
 */
class ExtraFieldUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-extra.php',
    ];
  }

  /**
   * Tests the upgrade path for Layout Builder extra fields.
   */
  public function testRunUpdates() {
    // The default view mode has Layout Builder enabled.
    $data = EntityViewDisplay::load('node.article.default')->toArray();
    $this->assertArrayHasKey('third_party_settings', $data);
    $this->assertArrayNotHasKey('sections', $data['third_party_settings']['layout_builder']);

    // The teaser view mode does not have Layout Builder enabled.
    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertArrayNotHasKey('third_party_settings', $data);

    $this->runUpdates();

    // The extra links have been added.
    $data = EntityViewDisplay::load('node.article.default')->toArray();
    $components = $data['third_party_settings']['layout_builder']['sections'][0]->getComponents();
    $component = reset($components);
    $this->assertSame('extra_field_block:node:article:links', $component->getPluginId());

    // No extra links have been added.
    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertArrayNotHasKey('third_party_settings', $data);
  }

}
