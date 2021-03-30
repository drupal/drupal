<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for Layout Builder layout context mappings.
 *
 * @see layout_builder_post_update_section_storage_context_mapping()
 *
 * @group layout_builder
 */
class LayoutBuilderContextMappingUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-context-mapping.php',
    ];
  }

  /**
   * Tests the upgrade path for Layout Builder layout context mappings.
   */
  public function testRunUpdates() {
    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertSame(TRUE, $data['third_party_settings']['layout_builder']['enabled']);
    $this->assertArrayNotHasKey('context_mapping', $data['third_party_settings']['layout_builder']['sections'][0]->toArray()['layout_settings']);

    $this->runUpdates();

    $data = EntityViewDisplay::load('node.article.teaser')->toArray();
    $this->assertSame(TRUE, $data['third_party_settings']['layout_builder']['enabled']);
    $this->assertSame([], $data['third_party_settings']['layout_builder']['sections'][0]->toArray()['layout_settings']['context_mapping']);
  }

}
