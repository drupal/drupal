<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests system_post_update_recalculate_dependencies_for_installed_config_entities().
 *
 * @group Update
 * @group legacy
 */
class RecalculatedDependencyTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that the entities are resaved so they have the new dependency.
   */
  public function testUpdate() {
    // Test the configuration pre update.
    $data = \Drupal::config('field.field.node.article.field_tags')->get();
    $this->assertEqual(['entity_reference'], $data['dependencies']['module']);
    $this->assertEqual([
      'field.storage.node.field_tags',
      'node.type.article',
    ], $data['dependencies']['config']);

    $data = \Drupal::config('field.field.user.user.user_picture')->get();
    $this->assertFalse(isset($data['dependencies']['module']));

    $data = \Drupal::config('field.storage.node.field_image')->get();
    $this->assertEqual(['node', 'image'], $data['dependencies']['module']);

    // Explicitly break an optional configuration dependencies to ensure it is
    // recalculated. Use active configuration storage directly so that no events
    // are fired.
    $config_storage = \Drupal::service('config.storage');
    $data = $config_storage->read('search.page.node_search');
    unset($data['dependencies']);
    $config_storage->write('search.page.node_search', $data);
    // Ensure the update is successful.
    $data = \Drupal::config('search.page.node_search')->get();
    $this->assertFalse(isset($data['dependencies']['module']));

    // Run the updates.
    $this->runUpdates();

    // Test the configuration post update.
    $data = \Drupal::config('field.field.node.article.field_tags')->get();
    $this->assertFalse(isset($data['dependencies']['module']));
    $this->assertEqual([
      'field.storage.node.field_tags',
      'node.type.article',
      'taxonomy.vocabulary.tags',
    ], $data['dependencies']['config']);

    $data = \Drupal::config('field.field.user.user.user_picture')->get();
    $this->assertEqual(['image', 'user'], $data['dependencies']['module']);

    $data = \Drupal::config('field.storage.node.field_image')->get();
    $this->assertEqual(['file', 'image', 'node'], $data['dependencies']['module']);

    $data = \Drupal::config('search.page.node_search')->get();
    $this->assertEqual(['node'], $data['dependencies']['module']);
  }

}
