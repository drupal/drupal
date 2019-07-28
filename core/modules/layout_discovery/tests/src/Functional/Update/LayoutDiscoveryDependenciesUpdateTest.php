<?php

namespace Drupal\Tests\layout_discovery\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for updating the layout discovery dependencies.
 *
 * @see layout_discovery_post_update_recalculate_entity_form_display_dependencies()
 * @see layout_discovery_post_update_recalculate_entity_view_display_dependencies()
 *
 * @group layout_discovery
 * @group legacy
 */
class LayoutDiscoveryDependenciesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.theme-dependencies-in-module-key-2904550.php',
    ];
  }

  /**
   * Tests updating the dependencies for layout discovery based entity displays.
   */
  public function testUpdatedLayoutDiscoveryDependencies() {
    $entities = [
      EntityFormDisplay::load('node.page.default'),
      EntityViewDisplay::load('node.page.default'),
    ];
    foreach ($entities as $entity) {
      $dependencies = $entity->getDependencies();
      $this->assertTrue(in_array('test_layout_theme', $dependencies['module']));
      $this->assertFalse(isset($dependencies['theme']));
    }

    $this->runUpdates();

    $updated_entities = [
      EntityFormDisplay::load('node.page.default'),
      EntityViewDisplay::load('node.page.default'),
    ];
    foreach ($updated_entities as $updated_entity) {
      $dependencies = $updated_entity->getDependencies();
      $this->assertFalse(in_array('test_layout_theme', $dependencies['module']));
      $this->assertTrue(in_array('test_layout_theme', $dependencies['theme']));
    }
  }

}
