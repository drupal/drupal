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
    ];
  }

  /**
   * Tests the upgrade path for Layout Builder extra fields.
   */
  public function testRunUpdates() {
    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertArrayNotHasKey('third_party_settings', $data);

    $this->runUpdates();

    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $components = $data['third_party_settings']['layout_builder']['sections'][0]->getComponents();
    $component = reset($components);
    $this->assertSame('extra_field_block:node:article:links', $component->getPluginId());
  }

}
