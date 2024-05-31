<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for converting numeric arguments to entity_target_id.
 *
 * @group Update
 *
 * @see views_post_update_views_data_argument_plugin_id()
 */
class EntityArgumentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/entity-id-argument.php',
    ];
  }

  /**
   * Tests that numeric argument plugins are updated properly.
   */
  public function testViewsFieldPluginConversion(): void {
    $view = View::load('test_entity_id_argument_update');
    $data = $view->toArray();
    $this->assertEquals('numeric', $data['display']['default']['display_options']['arguments']['field_tags_target_id']['plugin_id']);
    $this->assertArrayNotHasKey('target_entity_type_id', $data['display']['default']['display_options']['arguments']['field_tags_target_id']);

    $this->runUpdates();

    $view = View::load('test_entity_id_argument_update');
    $data = $view->toArray();
    $this->assertEquals('entity_target_id', $data['display']['default']['display_options']['arguments']['field_tags_target_id']['plugin_id']);
    $this->assertEquals('taxonomy_term', $data['display']['default']['display_options']['arguments']['field_tags_target_id']['target_entity_type_id']);

  }

}
